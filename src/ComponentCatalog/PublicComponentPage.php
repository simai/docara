<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use Simai\Docara\Portable\PortableConfigurationException;

final class PublicComponentPage
{
    /** @param array<string, mixed> $entry */
    public static function id(array $entry): string
    {
        $id = $entry['id'] ?? null;
        if (! is_string($id)
            || preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $id) !== 1
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_ID_INVALID',
                is_scalar($id) ? (string) $id : '',
            );
        }

        return $id;
    }

    /** @param array<string, mixed> $entry */
    public static function slug(array $entry): string
    {
        $id = self::id($entry);
        if ($id === 'docara.code') {
            return 'code-from-file';
        }

        $parts = explode('.', $id, 2);
        $slug = str_replace('_', '-', $parts[1] ?? $parts[0]);
        if (preg_match('/^[a-z][a-z0-9-]*$/D', $slug) !== 1) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PUBLIC_SLUG_INVALID',
                $id,
            );
        }

        return $slug;
    }

    /** @param array<string, mixed> $entry */
    public static function output(string $prefix, array $entry): string
    {
        return implode('/', array_filter([
            trim($prefix, '/'),
            'components',
            self::slug($entry),
            'index.html',
        ], static fn (string $part): bool => $part !== ''));
    }
}
