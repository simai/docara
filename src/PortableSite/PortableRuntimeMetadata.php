<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Composer\InstalledVersions;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\Portable\CanonicalJson;

final readonly class PortableRuntimeMetadata
{
    public function __construct(private string $packageRoot) {}

    /** @return array<string, mixed> */
    public function package(): array
    {
        $version = InstalledVersions::isInstalled('simai/docara')
            ? (InstalledVersions::getPrettyVersion('simai/docara') ?? 'dev')
            : 'dev';
        $reference = InstalledVersions::isInstalled('simai/docara')
            ? InstalledVersions::getReference('simai/docara')
            : null;
        $tree = $this->packageTreeHash();

        return [
            'schema' => 'docara.package_revision.v1',
            'name' => 'simai/docara',
            'version' => $version,
            'source_revision' => is_string($reference) && preg_match('/^[a-f0-9]{40}$/', $reference) === 1
                ? $reference
                : 'sha256:' . $tree,
            'tree_sha256' => $tree,
            'immutable' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function dependencies(): array
    {
        $composer = $this->json($this->packageRoot . '/composer.json');
        $requires = is_array($composer['require'] ?? null) ? $composer['require'] : [];
        $packages = [];
        foreach (array_keys($requires) as $name) {
            if ($name === 'php' || ! InstalledVersions::isInstalled($name)) {
                continue;
            }
            $version = InstalledVersions::getPrettyVersion($name) ?? InstalledVersions::getVersion($name);
            $reference = InstalledVersions::getReference($name);
            if (! is_string($version) || ! is_string($reference) || preg_match('/^[a-f0-9]{40}$/', $reference) !== 1) {
                throw new RuntimeException("Runtime dependency [{$name}] has no immutable installed version and revision.");
            }
            $packages[$name] = ['version' => $version, 'reference' => $reference];
        }
        ksort($packages, SORT_STRING);

        $root = InstalledVersions::getRootPackage();
        $rootPath = is_string($root['install_path'] ?? null) ? $root['install_path'] : $this->packageRoot;
        $lockPath = rtrim($rootPath, '/\\') . '/composer.lock';
        $lockHash = is_file($lockPath) && ! is_link($lockPath) ? hash_file('sha256', $lockPath) : null;
        $mode = is_string($lockHash) ? 'consumer_composer_lock' : 'source_tree_fingerprint';
        $tupleHash = hash('sha256', CanonicalJson::encode($packages));

        return [
            'schema' => 'docara.dependency_lock.v1',
            'owner' => 'consumer',
            'mode' => $mode,
            'composer_lock_sha256' => $lockHash,
            'runtime_tuple_sha256' => $tupleHash,
            'packages' => $packages,
            'moving_references_allowed' => false,
        ];
    }

    private function packageTreeHash(): string
    {
        $records = [];
        foreach (['src', 'resources', 'stubs'] as $directory) {
            $root = $this->packageRoot . '/' . $directory;
            if (! is_dir($root) || is_link($root)) {
                throw new RuntimeException("Package source directory [{$directory}] is missing or unsafe.");
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->isLink()) {
                    continue;
                }
                $path = str_replace('\\', '/', substr($file->getPathname(), strlen($this->packageRoot) + 1));
                $records[$path] = hash_file('sha256', $file->getPathname());
            }
        }
        foreach (['composer.json', 'docara'] as $relative) {
            $path = $this->packageRoot . '/' . $relative;
            if (! is_file($path) || is_link($path)) {
                throw new RuntimeException("Package file [{$relative}] is missing or unsafe.");
            }
            $records[$relative] = hash_file('sha256', $path);
        }
        ksort($records, SORT_STRING);

        return hash('sha256', CanonicalJson::encode($records));
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($value)) {
            throw new RuntimeException("JSON file [{$path}] must contain an object.");
        }

        return $value;
    }
}
