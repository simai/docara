<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use JsonException;
use Simai\Docara\Content\SourceBoundaryValidator;
use Simai\Docara\Portable\PortableConfigurationException;

final class ContentLanguageRepository
{
    private string $projectRoot;

    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    public function __construct(string $projectRoot)
    {
        $resolved = realpath($projectRoot);
        if ($resolved === false || ! is_dir($resolved) || is_link($projectRoot)) {
            throw new PortableConfigurationException(
                'CONTENT_LANGUAGE_ROOT_INVALID',
                "Content-language project root [$projectRoot] is not a safe directory.",
            );
        }
        $this->projectRoot = rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    /** @return array<string, string> */
    public function messages(LocaleDefinition $locale): array
    {
        $tag = $locale->tag->value();
        if (isset($this->loaded[$tag])) {
            return $this->loaded[$tag];
        }
        $relative = trim($locale->contentRoot, '/') . '/lang.json';
        $path = $this->projectRoot . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_file($path)) {
            return $this->loaded[$tag] = [];
        }
        $contentRoot = realpath($this->projectRoot . DIRECTORY_SEPARATOR . trim($locale->contentRoot, '/'));
        $real = realpath($path);
        if ($contentRoot === false || $real === false || is_link($path)
            || ! str_starts_with($real, rtrim($contentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
        ) {
            throw new PortableConfigurationException(
                'CONTENT_LANGUAGE_SOURCE_INVALID',
                "Content language [$relative] is not a safe file.",
            );
        }
        try {
            $language = json_decode((string) file_get_contents($real), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'CONTENT_LANGUAGE_INVALID',
                "Content language [$relative] is not valid JSON.",
                $exception,
            );
        }
        if (! is_array($language)) {
            throw new PortableConfigurationException(
                'CONTENT_LANGUAGE_INVALID',
                "Content language [$relative] must be an object.",
            );
        }
        (new SourceBoundaryValidator)->assertLanguage($language);
        $messages = [];
        foreach ($language as $namespace => $values) {
            if (in_array($namespace, ['schema', 'version'], true) || ! is_array($values)) {
                continue;
            }
            $this->flatten($values, (string) $namespace, $messages);
        }

        return $this->loaded[$tag] = $messages;
    }

    /** @param array<string, mixed> $values @param array<string, string> $messages */
    private function flatten(array $values, string $prefix, array &$messages): void
    {
        foreach ($values as $key => $value) {
            $id = $prefix . '.' . $key;
            if (is_array($value)) {
                $this->flatten($value, $id, $messages);

                continue;
            }
            $messages[$id] = (string) $value;
        }
    }
}
