<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition\Recipe;

use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

final class FileRecipeCompiler
{
    private ?Process $process = null;

    private ?InputStream $input = null;

    private string $outputBuffer = '';

    private ?string $sessionRoot = null;

    public static function fromFrameworkDistribution(string $nodeBinary, string $frameworkRoot): self
    {
        $entry = rtrim($frameworkRoot, '/\\\\') . '/distr/core/js/composition/index.mjs';
        if (! is_file($entry) || is_link($entry)) {
            throw new RuntimeException('docara_composition_recipe_distribution_invalid');
        }

        $lock = json_decode((string) file_get_contents(dirname(__DIR__, 4) . '/resources/contracts/composition/runtime-lock.json'), true, 512, JSON_THROW_ON_ERROR);
        if (($lock['schema'] ?? null) !== 'docara.composition_runtime_lock.v1'
            || ! is_array($lock['files'] ?? null)
            || ! is_string($lock['contract_digest'] ?? null)
        ) {
            throw new RuntimeException('docara_composition_recipe_runtime_lock_invalid');
        }
        $files = [];
        foreach ($lock['files'] as $path => $digest) {
            $file = rtrim($frameworkRoot, '/\\\\') . '/' . $path;
            if (! is_file($file) || is_link($file) || 'sha256:' . hash_file('sha256', $file) !== $digest) {
                throw new RuntimeException('docara_composition_recipe_framework_digest_mismatch');
            }
            $files[$file] = $digest;
        }

        return new self($nodeBinary, $entry, $lock['files']['distr/core/js/composition/index.mjs'], expectedFrameworkFiles: $files, recipeContractDigest: $lock['contract_digest']);
    }

    public function __construct(
        private readonly string $nodeBinary,
        private readonly string $frameworkEntry,
        private readonly string $expectedFrameworkEntryDigest,
        private readonly ?string $runner = null,
        private readonly array $expectedFrameworkFiles = [],
        private readonly string $recipeContractDigest = '',
    ) {}

    /** @return array<string, mixed> */
    public function compile(string $projectRoot, string $descriptor): array
    {
        [$root, $entry, $runner] = $this->runtime($projectRoot);
        $output = $this->request($root, $entry, $runner, [
            'operation' => 'file',
            'descriptor' => $descriptor,
        ]);

        return $this->decodeResult($output, true);
    }

    /**
     * @param  array<string, mixed>  $recipe
     * @param  array<string, mixed>  $inputs
     * @param  list<array<string, mixed>>  $manifests
     * @return array<string, mixed>
     */
    public function resolve(string $projectRoot, array $recipe, array $inputs, array $manifests, string $rendererDigest): array
    {
        [$root, $entry, $runner] = $this->runtime($projectRoot);
        $output = $this->request($root, $entry, $runner, [
            'operation' => 'resolve',
            'recipe' => $recipe,
            'inputs' => $inputs,
            'manifests' => $manifests,
            'executionContract' => [
                'contractDigest' => $this->recipeContractDigest,
                'rendererDigest' => $rendererDigest,
            ],
        ]);

        return $this->decodeResult($output, false);
    }

    /** @return array{string, string, string} */
    private function runtime(string $projectRoot): array
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
        foreach ($this->expectedFrameworkFiles as $file => $digest) {
            if (! is_file($file) || is_link($file) || 'sha256:' . hash_file('sha256', $file) !== $digest) {
                throw new RuntimeException('docara_composition_recipe_framework_digest_mismatch');
            }
        }

        return [$root, $entry, $runner];
    }

    /** @param array<string, mixed> $request */
    private function request(string $root, string $entry, string $runner, array $request): string
    {
        $this->startSession($root, $entry, $runner);
        $this->input?->write(json_encode($request, JSON_THROW_ON_ERROR) . "\n");

        return $this->readResult();
    }

    /** @return array<string, mixed> */
    private function decodeResult(string $output, bool $requireHtml): array
    {
        try {
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('docara_composition_recipe_result_invalid', previous: $exception);
        }
        if (is_array($result['sessionError'] ?? null)) {
            throw new RuntimeException('docara_composition_recipe_failed:' . (string) ($result['sessionError']['message'] ?? $result['sessionError']['code'] ?? 'unknown'));
        }
        if (! is_array($result)
            || ! is_array($result['document'] ?? null)
            || ! is_array($result['dependencyReceipt'] ?? null)
            || ($result['diagnostics'] ?? null) !== []
            || ($requireHtml && (! is_string($result['html'] ?? null) || $result['html'] === ''))
        ) {
            $diagnostics = is_array($result['diagnostics'] ?? null)
                ? json_encode($result['diagnostics'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : 'result_shape_invalid';
            throw new RuntimeException('docara_composition_recipe_rejected:' . $diagnostics);
        }

        return $result;
    }

    public function close(): void
    {
        if ($this->input instanceof InputStream) {
            $this->input->close();
        }
        if ($this->process instanceof Process && $this->process->isRunning()) {
            $this->process->stop(1);
        }
        $this->process = null;
        $this->input = null;
        $this->outputBuffer = '';
        $this->sessionRoot = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    private function startSession(string $root, string $entry, string $runner): void
    {
        if ($this->process instanceof Process && $this->process->isRunning() && $this->sessionRoot === $root) {
            return;
        }

        $this->close();
        $this->input = new InputStream;
        $this->process = new Process([$this->nodeBinary, $runner, $root, $entry, '--session'], $root);
        $this->process->setInput($this->input);
        $this->process->setTimeout(null);
        $this->process->start();
        $this->sessionRoot = $root;
    }

    private function readResult(): string
    {
        $startedAt = microtime(true);
        while (true) {
            if (! $this->process instanceof Process) {
                throw new RuntimeException('docara_composition_recipe_runtime_unavailable');
            }
            $this->outputBuffer .= $this->process->getIncrementalOutput();
            $this->process->clearOutput();
            $lineEnd = strpos($this->outputBuffer, "\n");
            if ($lineEnd !== false) {
                $line = substr($this->outputBuffer, 0, $lineEnd);
                $this->outputBuffer = substr($this->outputBuffer, $lineEnd + 1);

                return $line;
            }
            if (! $this->process->isRunning()) {
                $error = trim($this->process->getIncrementalErrorOutput() ?: $this->process->getErrorOutput());
                $this->close();
                throw new RuntimeException('docara_composition_recipe_failed:' . ($error !== '' ? $error : 'runtime_stopped'));
            }
            if (microtime(true) - $startedAt > 30) {
                $this->close();
                throw new RuntimeException('docara_composition_recipe_failed:runtime_timeout');
            }
            usleep(1000);
        }
    }
}
