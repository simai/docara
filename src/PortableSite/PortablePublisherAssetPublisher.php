<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class PortablePublisherAssetPublisher
{
    public function __construct(
        private Filesystem $files,
        private SmartRegistry $smarts = new SmartRegistry([]),
    ) {}

    public function publish(string $destination): void
    {
        foreach ($this->assetNames() as $name) {
            $this->publishAsset(
                dirname(__DIR__, 2) . '/resources/portable/' . $name,
                rtrim($destination, '/\\') . '/_docara/' . $name,
                'DECLARATIVE_PUBLISHER_ASSET',
                $name,
            );
        }

        $smarts = $this->smarts->keys() === [] ? SmartRegistry::bundled() : $this->smarts;
        foreach ($smarts->assets() as $key => $asset) {
            $this->publishAsset(
                (is_string($asset['root'] ?? null) ? $asset['root'] : dirname(__DIR__, 2) . '/resources') . '/' . $asset['path'],
                rtrim($destination, '/\\') . '/_docara/' . $asset['public'],
                'DECLARATIVE_SMART_ASSET',
                (string) $key,
            );
        }
    }

    private function publishAsset(string $source, string $target, string $errorPrefix, string $label): void
    {
        if (! is_file($source) || is_link($source)) {
            throw new PortableConfigurationException(
                $errorPrefix . '_MISSING',
                "Asset [$label] is missing or unsafe.",
            );
        }
        $bytes = file_get_contents($source);
        if (! is_string($bytes) || $bytes === '') {
            throw new PortableConfigurationException($errorPrefix . '_INVALID', "Asset [$label] is invalid.");
        }
        $this->files->ensureDirectoryExists(dirname($target));
        if ($this->files->put($target, $bytes) === false
            || ! hash_equals(hash('sha256', $bytes), (string) hash_file('sha256', $target))
        ) {
            throw new PortableConfigurationException($errorPrefix . '_PUBLICATION_FAILED', $label);
        }
    }

    /** @return list<string> */
    private function assetNames(): array
    {
        $root = dirname(__DIR__, 2) . '/resources/portable';
        $names = ['declarative-shell.css', 'declarative-shell.js'];
        $vendor = $root . '/vendor';
        if (! is_dir($vendor) || is_link($vendor)) {
            return $names;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($vendor, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if ($relative === '' || str_contains($relative, '..')) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PUBLISHER_ASSET_INVALID',
                    'A portable vendor asset has an unsafe path.',
                );
            }
            $names[] = $relative;
        }
        sort($names, SORT_STRING);

        return array_values(array_unique($names));
    }
}
