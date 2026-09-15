<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition\Recipe;

use RuntimeException;
use Symfony\Component\Process\Process;

final readonly class FileRecipeCompiler
{
    public static function fromFrameworkDistribution(string $nodeBinary, string $frameworkRoot): self
    {
        $entry = rtrim($frameworkRoot, '/\\\\') . '/distr/core/js/composition/index.mjs';
        if (! is_file($entry) || is_link($entry)) {
            throw new RuntimeException('docara_composition_recipe_distribution_invalid');
        }

        return new self($nodeBinary, $entry, 'sha256:' . hash_file('sha256', $entry));
    }

    public function __construct(
        private string $nodeBinary,
        private string $frameworkEntry,
        private string $expectedFrameworkEntryDigest,
        private ?string $runner = null,
    ) {}

    /** @return array<string, mixed> */
    public function compile(string $projectRoot, string $descriptor): array
    {
        $root = realpath($projectRoot);
        $entry = realpath($this->frameworkEntry);
        $runner = realpath($this->runner ?? dirname(__DIR__, 4) . '/resources/runtime/composition-recipe-file-adapter.mjs');
        if (! is_string($root) || ! is_dir($root) || ! is_string($entry) || ! is_file($entry) || ! is_string($runner) || ! is_file($runner)) {
            throw new RuntimeException('docara_composition_recipe_runtime_unavailable');
        }
        if ($this->expectedFrameworkEntryDigest !== 'sha256:' . hash_file('sha256', $entry)) {
            throw new RuntimeException('docara_composition_recipe_framework_digest_mismatch');
        }

        $process = new Process([$this->nodeBinary, $runner, $root, $entry, $descriptor], $root);
        $process->setTimeout(30);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('docara_composition_recipe_failed:' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        try {
            $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('docara_composition_recipe_result_invalid', previous: $exception);
        }
        if (! is_array($result) || ! is_array($result['document'] ?? null) || ! is_array($result['dependencyReceipt'] ?? null) || ($result['diagnostics'] ?? null) !== []) {
            throw new RuntimeException('docara_composition_recipe_rejected');
        }

        return $result;
    }
}
