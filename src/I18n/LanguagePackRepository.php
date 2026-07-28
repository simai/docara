<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class LanguagePackRepository
{
    private string $projectRoot;

    /** @var array<string, LanguagePack> */
    private array $loaded = [];

    public function __construct(
        string $projectRoot,
        private readonly string $bundledRoot = __DIR__ . '/../../resources/language-packs',
        private readonly SchemaRepository $schemas = new SchemaRepository,
    ) {
        $resolved = realpath($projectRoot);
        if ($resolved === false || ! is_dir($resolved) || is_link($projectRoot)) {
            throw new PortableConfigurationException(
                'LANGUAGE_PACK_ROOT_INVALID',
                "Language-pack project root [$projectRoot] is not a safe directory.",
            );
        }
        $this->projectRoot = rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    public function load(LocaleDefinition $locale): LanguagePack
    {
        $key = $locale->tag->value() . "\0" . $locale->languagePack;
        if (isset($this->loaded[$key])) {
            return $this->loaded[$key];
        }

        [$path, $source] = $this->resolve($locale->languagePack);
        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            throw new PortableConfigurationException(
                'LANGUAGE_PACK_READ_FAILED',
                "Language pack [$source] could not be read.",
            );
        }
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'LANGUAGE_PACK_INVALID',
                "Language pack [$source] is not valid JSON.",
                $exception,
            );
        }
        $this->schemas->assertValid($decoded, 'language-pack.schema.json');
        $packLocale = LocaleTag::from((string) $decoded['locale']);
        if ($packLocale->value() !== $locale->tag->value()) {
            throw new PortableConfigurationException(
                'LANGUAGE_PACK_LOCALE_MISMATCH',
                "Language pack [$source] declares [{$packLocale->value()}], expected [{$locale->tag->value()}].",
            );
        }

        return $this->loaded[$key] = new LanguagePack(
            $packLocale,
            $decoded['messages'],
            $decoded['components'] ?? [],
            $source,
        );
    }

    /** @return array{0:string,1:string} */
    private function resolve(string $reference): array
    {
        if (preg_match('~^@docara/([A-Za-z0-9-]+)$~D', $reference, $match) === 1) {
            $tag = LocaleTag::from($match[1])->value();
            $path = rtrim($this->bundledRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tag . '.json';

            return [$this->safeFile($path, realpath($this->bundledRoot) ?: $this->bundledRoot), $reference];
        }
        if ($reference === '' || str_contains($reference, "\0") || str_contains($reference, '\\')) {
            throw new PortableConfigurationException('LANGUAGE_PACK_REFERENCE_INVALID', 'Language-pack reference is invalid.');
        }
        $segments = explode('/', $reference);
        if (str_starts_with($reference, '/') || in_array('', $segments, true)
            || in_array('.', $segments, true) || in_array('..', $segments, true)
        ) {
            throw new PortableConfigurationException(
                'LANGUAGE_PACK_REFERENCE_INVALID',
                "Language-pack reference [$reference] must be a confined relative path.",
            );
        }
        $path = $this->projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $reference);

        return [$this->safeFile($path, $this->projectRoot), $reference];
    }

    private function safeFile(string $path, string $root): string
    {
        $realRoot = realpath($root);
        $real = realpath($path);
        if ($realRoot === false || $real === false || ! is_file($real) || is_link($path)
            || ! str_starts_with($real, rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
        ) {
            throw new PortableConfigurationException(
                'LANGUAGE_PACK_NOT_FOUND',
                "Language-pack file [$path] is missing or unsafe.",
            );
        }

        return $real;
    }
}
