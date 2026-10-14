<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use Simai\Docara\Portable\PortableConfigurationException;

final class PreviewWatcher
{
    /** @var array<string, string> */
    private array $fingerprints = [];

    private string $packageRoot;

    public function __construct(?string $packageRoot = null)
    {
        $this->packageRoot = rtrim($packageRoot ?? dirname(__DIR__, 2), '/\\');
    }

    public function prime(string $root, PreviewArtifact $artifact): void
    {
        $this->fingerprints = $this->fingerprints($root, $artifact->dependencies);
    }

    /** @param callable():PreviewArtifact $rebuild @return list<PreviewArtifact> */
    public function run(string $root, PreviewArtifact $artifact, callable $rebuild, int $intervalMilliseconds = 250, int $maxCycles = 0): array
    {
        if ($intervalMilliseconds < 50 || $intervalMilliseconds > 5000 || $maxCycles < 0) {
            throw new PortableConfigurationException('PREVIEW_WATCH_OPTIONS_INVALID', 'Preview watch interval or cycle bound is invalid.');
        }
        if ($this->fingerprints === []) {
            $this->prime($root, $artifact);
        }
        $rebuilt = [];
        $cycles = 0;
        while ($maxCycles === 0 || $cycles < $maxCycles) {
            usleep($intervalMilliseconds * 1000);
            $cycles++;
            if ($this->fingerprints($root, $artifact->dependencies) === $this->fingerprints) {
                continue;
            }
            $artifact = $rebuild();
            $this->prime($root, $artifact);
            $rebuilt[] = $artifact;
        }

        return $rebuilt;
    }

    /** @param list<string> $dependencies @return array<string, string> */
    private function fingerprints(string $root, array $dependencies): array
    {
        $root = rtrim((string) realpath($root), '/\\');
        $fingerprints = [];
        foreach ($dependencies as $dependency) {
            [$base, $relative, $tree] = $this->resolve($root, $dependency);
            $path = $base . '/' . $relative;
            $fingerprints[$dependency] = $tree
                ? $this->treeFingerprint($path)
                : (is_link($path) || ! is_file($path) ? 'missing' : (string) hash_file('sha256', $path));
        }
        ksort($fingerprints, SORT_STRING);

        return $fingerprints;
    }

    /** @return array{0:string,1:string,2:bool} */
    private function resolve(string $projectRoot, string $dependency): array
    {
        $base = $projectRoot;
        $tree = false;
        $relative = $dependency;
        foreach ([
            '@package-tree:' => [$this->packageRoot, true],
            '@package-file:' => [$this->packageRoot, false],
            '@project-tree:' => [$projectRoot, true],
        ] as $prefix => [$candidateBase, $candidateTree]) {
            if (str_starts_with($dependency, $prefix)) {
                $base = $candidateBase;
                $tree = $candidateTree;
                $relative = substr($dependency, strlen($prefix));
                break;
            }
        }
        if ($relative === ''
            || str_starts_with($relative, '/')
            || str_contains($relative, '\\')
            || in_array('..', explode('/', $relative), true)
        ) {
            throw new PortableConfigurationException('PREVIEW_DEPENDENCY_PATH_INVALID', "Preview dependency [$dependency] is unsafe.");
        }

        return [$base, $relative, $tree];
    }

    private function treeFingerprint(string $path): string
    {
        if (is_link($path) || ! is_dir($path)) {
            return 'missing';
        }
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            if ($file->isLink() || ! $file->isFile()) {
                return 'unsafe';
            }
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($filePath, strlen($path) + 1));
            $records[$relative] = hash_file('sha256', $filePath);
        }
        ksort($records, SORT_STRING);

        return hash('sha256', json_encode($records, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
