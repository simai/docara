<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use Simai\Docara\Portable\PortableConfigurationException;

final class PreviewWatcher
{
    /** @var array<string, string> */
    private array $fingerprints = [];

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
            $path = $root . '/' . $dependency;
            $fingerprints[$dependency] = is_link($path) || ! is_file($path)
                ? 'missing'
                : (string) hash_file('sha256', $path);
        }
        ksort($fingerprints, SORT_STRING);

        return $fingerprints;
    }
}
