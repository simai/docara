<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class PortablePublisherAssetPublisher
{
    public function __construct(
        private Filesystem $files,
        private SmartRegistry $smarts = new SmartRegistry([]),
    ) {}

    /** @param list<string>|null $requiredSmartAssets */
    public function publish(
        string $destination,
        ?array $requiredSmartAssets = null,
        ?FrameworkAssetPlan $frameworkAssets = null,
    ): void {
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
            if (str_starts_with((string) $key, 'framework.portable.')
                && $requiredSmartAssets !== null
                && ! in_array($key, $requiredSmartAssets, true)
            ) {
                continue;
            }
            $this->publishAsset(
                (is_string($asset['root'] ?? null) ? $asset['root'] : dirname(__DIR__, 2) . '/resources') . '/' . $asset['path'],
                rtrim($destination, '/\\') . '/_docara/' . $asset['public'],
                'DECLARATIVE_SMART_ASSET',
                (string) $key,
            );
        }

        if ($frameworkAssets instanceof FrameworkAssetPlan) {
            $this->publishGeneratedFrameworkAssets($destination, $frameworkAssets);
        }
    }

    /** @param list<FrameworkAssetPlan> $plans */
    public function publishFrameworkAssetPlans(string $destination, array $plans): void
    {
        $assets = [];
        foreach ($plans as $plan) {
            if (! $plan instanceof FrameworkAssetPlan) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_GENERATED_ASSET_INVALID',
                    'A generated Framework asset plan is invalid.',
                );
            }
            foreach ($plan->generatedAssets as $asset) {
                $filename = is_array($asset) ? ($asset['filename'] ?? null) : null;
                $sha256 = is_array($asset) ? ($asset['sha256'] ?? null) : null;
                if (! is_string($filename) || ! is_string($sha256)) {
                    throw new PortableConfigurationException(
                        'DECLARATIVE_GENERATED_ASSET_INVALID',
                        'A generated Framework asset is missing its immutable identity.',
                    );
                }
                if (isset($assets[$filename]) && ! hash_equals((string) $assets[$filename]['sha256'], $sha256)) {
                    throw new PortableConfigurationException(
                        'DECLARATIVE_GENERATED_ASSET_INVALID',
                        'Generated Framework assets conflict by filename.',
                    );
                }
                $assets[$filename] = $asset;
            }
        }
        if ($assets === []) {
            return;
        }
        ksort($assets, SORT_STRING);
        $first = $plans[0];
        $this->publishGeneratedFrameworkAssets(
            $destination,
            new FrameworkAssetPlan($first->runtimePair, [], array_values($assets)),
        );
    }

    private function publishGeneratedFrameworkAssets(
        string $destination,
        FrameworkAssetPlan $frameworkAssets,
    ): void {
        $seen = [];
        foreach ($frameworkAssets->generatedAssets as $asset) {
            $filename = $asset['filename'] ?? null;
            $content = $asset['content'] ?? null;
            $sha256 = $asset['sha256'] ?? null;
            if (($asset['kind'] ?? null) !== 'shell_css'
                || ! is_string($filename)
                || preg_match('/^docara-shell\.[a-f0-9]{64}\.css$/D', $filename) !== 1
                || ! is_string($content)
                || $content === ''
                || ! is_string($sha256)
                || ! hash_equals($sha256, hash('sha256', $content))
                || isset($seen[strtolower($filename)])
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_GENERATED_ASSET_INVALID',
                    'A generated Framework shell asset is invalid or conflicts by case.',
                );
            }
            $seen[strtolower($filename)] = true;
            $target = rtrim($destination, '/\\') . '/_docara/' . $filename;
            $this->files->ensureDirectoryExists(dirname($target));
            if ($this->files->put($target, $content) === false
                || ! hash_equals($sha256, (string) hash_file('sha256', $target))
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_GENERATED_ASSET_PUBLICATION_FAILED',
                    $filename,
                );
            }
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
