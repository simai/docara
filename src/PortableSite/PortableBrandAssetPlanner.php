<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PortableBrandAssetPlanner
{
    private const MAX_BYTES = 2097152;

    private const ALLOWED_EXTENSIONS = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'ico'];

    public function __construct(private Filesystem $files) {}

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array{pages: array<int, array<string, string|null>>, assets: array<string, string>}
     */
    public function plan(string $root, array $pages, string $baseUrl, string $siteTitle): array
    {
        $resolvedPages = [];
        $assets = [];

        foreach ($pages as $index => $page) {
            $configuration = $page['plan']->configuration;
            $branding = is_array($configuration['branding'] ?? null) ? $configuration['branding'] : [];
            if (is_string($branding['logo_dark'] ?? null) && ! is_string($branding['logo'] ?? null)) {
                throw new PortableConfigurationException(
                    'BRAND_DARK_LOGO_REQUIRES_LOGO',
                    'branding.logo_dark requires a default branding.logo asset.',
                );
            }
            $resolved = [
                'title' => (string) ($branding['title'] ?? $siteTitle),
                'label' => is_string($branding['label'] ?? null) ? $branding['label'] : null,
                'logo' => null,
                'logo_dark' => null,
                'favicon' => null,
                'favicon_type' => null,
            ];

            foreach (['logo', 'logo_dark', 'favicon'] as $field) {
                if (! is_string($branding[$field] ?? null)) {
                    continue;
                }
                $asset = $this->asset($root, $branding[$field], $baseUrl);
                $resolved[$field] = $asset['url'];
                if ($field === 'favicon') {
                    $resolved['favicon_type'] = $asset['mime'];
                }
                $assets[$asset['output']] = $asset['bytes'];
            }

            $resolvedPages[$index] = $resolved;
        }

        ksort($assets, SORT_STRING);

        return ['pages' => $resolvedPages, 'assets' => $assets];
    }

    /** @param array<string, string> $assets */
    public function publish(array $assets, string $destination): void
    {
        foreach ($assets as $relative => $bytes) {
            $target = rtrim($destination, '/\\') . '/' . $relative;
            $this->files->ensureDirectoryExists(dirname($target));
            if ($this->files->put($target, $bytes) === false
                || ! hash_equals(hash('sha256', $bytes), hash_file('sha256', $target))
            ) {
                throw new PortableConfigurationException(
                    'BRAND_ASSET_PUBLICATION_FAILED',
                    "Brand asset [$relative] could not be published deterministically.",
                );
            }
        }
    }

    /** @return array{url: string, output: string, bytes: string, mime: string} */
    private function asset(string $root, string $relative, string $baseUrl): array
    {
        $segments = explode('/', $relative);
        $first = strtolower($segments[0] ?? '');
        if ($relative === ''
            || str_contains($relative, "\0")
            || str_starts_with($relative, '/')
            || str_contains($relative, '\\')
            || in_array('', $segments, true)
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
            || in_array($first, ['_docara', '.docara'], true)
            || preg_match('/^build(?:_[A-Za-z0-9._-]+)?$/', $first) === 1
        ) {
            throw new PortableConfigurationException('BRAND_ASSET_PATH_INVALID', "Brand asset [$relative] has an unsafe path.");
        }

        $candidate = $root;
        foreach ($segments as $segment) {
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($candidate)) {
                throw new PortableConfigurationException(
                    'BRAND_ASSET_SYMLINK_FORBIDDEN',
                    "Brand asset [$relative] traverses a symbolic link.",
                );
            }
        }
        $real = realpath($candidate);
        if ($real === false || ! is_file($real) || ! FilesystemPath::isWithin($real, $root, false)) {
            throw new PortableConfigurationException('BRAND_ASSET_NOT_FOUND', "Brand asset [$relative] was not found.");
        }

        $extension = strtolower((string) pathinfo($real, PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new PortableConfigurationException(
                'BRAND_ASSET_TYPE_FORBIDDEN',
                "Brand asset [$relative] uses an unsupported file type.",
            );
        }
        $size = filesize($real);
        if (! is_int($size) || $size > self::MAX_BYTES) {
            throw new PortableConfigurationException('BRAND_ASSET_TOO_LARGE', "Brand asset [$relative] exceeds 2 MiB.");
        }
        $bytes = file_get_contents($real);
        if (! is_string($bytes)) {
            throw new PortableConfigurationException('BRAND_ASSET_READ_FAILED', "Brand asset [$relative] could not be read.");
        }

        $output = '_docara/brand/' . hash('sha256', $bytes) . '.' . $extension;
        $base = trim($baseUrl, '/');
        $url = '/' . ($base === '' ? '' : $base . '/') . $output;

        return [
            'url' => $url,
            'output' => $output,
            'bytes' => $bytes,
            'mime' => $this->mime($extension),
        ];
    }

    private function mime(string $extension): string
    {
        return match ($extension) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
        };
    }
}
