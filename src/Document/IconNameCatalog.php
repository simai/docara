<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The Material Symbols icon names Docara can draw: the names held by the
 * bundled icon catalogue (scripts/build-icon-catalog.cjs). Every catalogue
 * instance holds the same names, and the catalogue index records their count
 * and the SHA-256 of the names joined by newlines, so the list is checked
 * against the index before it is trusted.
 *
 * An `:icon[...]` whose name is not in this list would show the name as
 * plain letters, so the build refuses it (CONTENT_COMPONENT_ICON_UNKNOWN).
 */
final class IconNameCatalog
{
    /** The catalogue index, relative to resources/portable/. */
    public const INDEX = 'vendor/docara/icon-catalog/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/icon-catalog.json';

    /** @var array<string, array<string, true>> index path => names keyed by name */
    private static array $loaded = [];

    /** @param array<string, true> $names */
    private function __construct(private readonly array $names) {}

    public static function bundled(): self
    {
        return self::fromIndex(dirname(__DIR__, 2) . '/resources/portable/' . self::INDEX);
    }

    public static function fromIndex(string $indexPath): self
    {
        if (! isset(self::$loaded[$indexPath])) {
            self::$loaded[$indexPath] = self::load($indexPath);
        }

        return new self(self::$loaded[$indexPath]);
    }

    public function contains(string $name): bool
    {
        return isset($this->names[$name]);
    }

    /** @return list<string> every name, sorted */
    public function names(): array
    {
        return array_map('strval', array_keys($this->names));
    }

    /** @return array<string, true> */
    private static function load(string $indexPath): array
    {
        $index = self::json($indexPath);
        $manifestPath = $index['instances'][0]['manifest']['path'] ?? null;
        $manifestSha256 = $index['instances'][0]['manifest']['sha256'] ?? null;
        $count = $index['icons']['count'] ?? null;
        $namesSha256 = $index['icons']['sha256'] ?? null;
        if (($index['schema'] ?? null) !== 'docara.icon_catalog.v1'
            || ! is_string($manifestPath)
            || preg_match('/\A[a-z0-9.-]+\.manifest\.json\z/D', $manifestPath) !== 1
            || ! is_string($manifestSha256)
            || ! is_int($count)
            || ! is_string($namesSha256)
        ) {
            throw self::invalid('The icon catalogue index has no usable icon list.');
        }
        $path = dirname($indexPath) . '/' . $manifestPath;
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || ! hash_equals($manifestSha256, hash('sha256', $bytes))) {
            throw self::invalid("The icon catalogue manifest [$manifestPath] is missing or does not match the index.");
        }
        $names = self::decode($bytes, $manifestPath)['icons'] ?? null;
        if (! is_array($names)
            || ! array_is_list($names)
            || count($names) !== $count
            || array_filter($names, 'is_string') !== $names
            || ! hash_equals($namesSha256, hash('sha256', implode("\n", $names)))
        ) {
            throw self::invalid('The icon names of the catalogue do not match the index.');
        }
        sort($names, SORT_STRING);

        return array_fill_keys($names, true);
    }

    /** @return array<string, mixed> */
    private static function json(string $path): array
    {
        $bytes = @file_get_contents($path);
        if (! is_string($bytes)) {
            throw self::invalid('The icon catalogue index is missing.');
        }

        return self::decode($bytes, basename($path));
    }

    /** @return array<string, mixed> */
    private static function decode(string $bytes, string $name): array
    {
        try {
            $decoded = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw self::invalid("[$name] is not valid JSON.", $exception);
        }
        if (! is_array($decoded)) {
            throw self::invalid("[$name] is not a JSON object.");
        }

        return $decoded;
    }

    private static function invalid(string $message, ?\Throwable $previous = null): PortableConfigurationException
    {
        return new PortableConfigurationException('CONTENT_COMPONENT_ICON_CATALOG_INVALID', $message, $previous);
    }
}
