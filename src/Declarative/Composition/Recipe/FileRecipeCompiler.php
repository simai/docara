<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition\Recipe;

use JsonException;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

final class FileRecipeCompiler
{
    private ?Process $process = null;

    private ?InputStream $input = null;

    private string $outputBuffer = '';

    private ?string $sessionRoot = null;

    /**
     * Accept a Framework distribution that implements the Recipe specification
     * this Docara was built against.
     *
     * What is verified is the specification, not the runtime bytes. The runtime
     * grows with every Framework pair — regions, routing, field kinds, editor
     * surfaces — while the specification stays at its edition, so pinning the
     * bytes made every pair unusable until Docara was released again. The
     * distribution's own contract lock is read, its declared edition and digest
     * must be the expected ones, and that digest is recomputed here from the
     * normative files the distribution carries, so a lock claiming an edition it
     * does not implement is rejected. Runtime modules only have to be real files.
     */
    public static function fromFrameworkDistribution(string $nodeBinary, string $frameworkRoot): self
    {
        $root = rtrim($frameworkRoot, '/\\\\');
        $lock = json_decode((string) file_get_contents(dirname(__DIR__, 4) . '/resources/contracts/composition/runtime-lock.json'), true, 512, JSON_THROW_ON_ERROR);
        $contract = $lock['contract'] ?? null;
        $runtime = $lock['runtime'] ?? null;
        if (($lock['schema'] ?? null) !== 'docara.composition_runtime_lock.v2'
            || ! is_array($contract)
            || ! is_string($contract['lock_path'] ?? null)
            || ! is_string($contract['lock_schema'] ?? null)
            || ! is_string($contract['edition'] ?? null)
            || ! is_string($contract['contract_digest'] ?? null)
            || ! is_array($runtime)
            || ! is_string($runtime['entry'] ?? null)
            || ! is_array($runtime['modules'] ?? null)
        ) {
            throw new RuntimeException('docara_composition_recipe_runtime_lock_invalid');
        }

        $entry = $root . '/' . $runtime['entry'];
        if (! is_file($entry) || is_link($entry)) {
            throw new RuntimeException('docara_composition_recipe_distribution_invalid');
        }
        foreach ($runtime['modules'] as $module) {
            $file = $root . '/' . $module;
            if (! is_file($file) || is_link($file)) {
                throw new RuntimeException('docara_composition_recipe_distribution_invalid');
            }
        }

        self::verifySpecification($root, $contract);

        return new self(
            $nodeBinary,
            $entry,
            frameworkRoot: $root,
            recipeContract: $contract,
            recipeContractDigest: $contract['contract_digest'],
        );
    }

    /**
     * The distribution must implement the expected specification, and prove it.
     *
     * @param  array<string, mixed>  $contract
     */
    private static function verifySpecification(string $frameworkRoot, array $contract): void
    {
        $path = $frameworkRoot . '/' . $contract['lock_path'];
        if (! is_file($path) || is_link($path)) {
            throw new RuntimeException('docara_composition_recipe_specification_missing');
        }
        try {
            $declared = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('docara_composition_recipe_specification_invalid');
        }
        if (! is_array($declared)
            || ($declared['schema'] ?? null) !== $contract['lock_schema']
            || ! is_array($declared['normativeFiles'] ?? null)
        ) {
            throw new RuntimeException('docara_composition_recipe_specification_invalid');
        }
        if (($declared['version'] ?? null) !== $contract['edition']
            || ($declared['contractDigest'] ?? null) !== $contract['contract_digest']
        ) {
            throw new RuntimeException('docara_composition_recipe_specification_edition_mismatch');
        }
        if (self::normativeDigest($frameworkRoot, $contract['lock_path'], $declared['normativeFiles']) !== $contract['contract_digest']) {
            throw new RuntimeException('docara_composition_recipe_specification_digest_mismatch');
        }
    }

    /**
     * The Recipe digest: sha256 of the canonical map of normative file paths to
     * the sha256 of their bytes, as the Framework computes it. Recomputing it
     * here is what turns the distribution's claim into a verified fact.
     *
     * @param  array<string, mixed>  $normativeFiles
     */
    private static function normativeDigest(string $frameworkRoot, string $lockPath, array $normativeFiles): string
    {
        $base = dirname($frameworkRoot . '/' . $lockPath);
        $map = [];
        foreach ($normativeFiles as $relative => $expected) {
            if (! is_string($relative) || ! is_string($expected)) {
                throw new RuntimeException('docara_composition_recipe_specification_invalid');
            }
            $file = $base . '/' . $relative;
            if (! is_file($file) || is_link($file)) {
                throw new RuntimeException('docara_composition_recipe_specification_incomplete');
            }
            $map[$relative] = 'sha256:' . hash_file('sha256', $file);
        }
        ksort($map);

        return 'sha256:' . hash('sha256', (string) json_encode($map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $recipeContract */
    public function __construct(
        private readonly string $nodeBinary,
        private readonly string $frameworkEntry,
        private readonly ?string $runner = null,
        private readonly string $frameworkRoot = '',
        private readonly array $recipeContract = [],
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
        // The specification is re-verified before every session, so a
        // distribution swapped under a running build cannot slip through.
        if ($this->frameworkRoot !== '' && $this->recipeContract !== []) {
            self::verifySpecification($this->frameworkRoot, $this->recipeContract);
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
        } catch (JsonException $exception) {
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
