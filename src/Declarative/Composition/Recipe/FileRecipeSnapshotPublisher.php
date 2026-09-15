<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition\Recipe;

use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class FileRecipeSnapshotPublisher
{
    public function __construct(
        private FileRecipeCompiler $compiler,
        private ProjectFilesystemGuard $writes = new ProjectFilesystemGuard,
    ) {}

    /** @return array<string, mixed> */
    public function compileAndActivate(
        string $projectRoot,
        string $pageKey,
        string $descriptor,
        ?int $expectedActivationRevision,
    ): array {
        $compiled = $this->compiler->compile($projectRoot, $descriptor);

        return $this->activate($projectRoot, $pageKey, $descriptor, $compiled, $expectedActivationRevision);
    }

    /** @return array<string, mixed>|null */
    public function active(string $projectRoot, string $pageKey): ?array
    {
        return $this->locked($projectRoot, $pageKey, function (string $base) use ($projectRoot, $pageKey): ?array {
            $headPath = $this->writes->writablePath($projectRoot, $base . '/active.json');
            if (! is_file($headPath)) {
                return null;
            }
            $head = $this->decode($this->writes->regularFile($projectRoot, $base . '/active.json'), 'COMPOSITION_RECIPE_HEAD_INVALID');

            return $this->hydrate($projectRoot, $base, $pageKey, $head);
        }, false);
    }

    /** @return array<string, mixed> */
    public function rollback(
        string $projectRoot,
        string $pageKey,
        string $targetSnapshotDigest,
        int $expectedActivationRevision,
    ): array {
        if ($expectedActivationRevision < 1) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_ACTIVATION_REVISION_INVALID', 'The expected activation revision must be positive.');
        }
        $hex = $this->digestHex($targetSnapshotDigest);

        return $this->locked($projectRoot, $pageKey, function (string $base) use ($projectRoot, $pageKey, $targetSnapshotDigest, $expectedActivationRevision, $hex): array {
            $head = $this->readHead($projectRoot, $base);
            $this->assertExpectedRevision($head, $expectedActivationRevision);
            $snapshot = $this->decode(
                $this->writes->regularFile($projectRoot, $base . '/snapshots/' . $hex . '.json'),
                'COMPOSITION_RECIPE_SNAPSHOT_UNKNOWN',
            );
            $this->assertSnapshot($snapshot, $pageKey, $targetSnapshotDigest);

            return $this->activateSnapshot($projectRoot, $base, $snapshot, $expectedActivationRevision + 1);
        });
    }

    /** @param array<string, mixed> $compiled @return array<string, mixed> */
    private function activate(
        string $projectRoot,
        string $pageKey,
        string $descriptor,
        array $compiled,
        ?int $expectedActivationRevision,
    ): array {
        if ($descriptor === '' || strlen($descriptor) > 500
            || ! is_array($compiled['document'] ?? null)
            || ! is_string($compiled['html'] ?? null)
            || ! is_array($compiled['dependencyReceipt'] ?? null)
            || ! is_string($compiled['dependencyReceipt']['documentDigest'] ?? null)
            || ($compiled['diagnostics'] ?? null) !== []
        ) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_RESULT_INVALID', 'The compiled Recipe result cannot be published.');
        }
        $payload = [
            'schema' => 'docara.composition_recipe_snapshot.v1',
            'page_key' => $pageKey,
            'descriptor' => $descriptor,
            'document' => $compiled['document'],
            'html' => $compiled['html'],
            'dependency_receipt' => $compiled['dependencyReceipt'],
        ];

        return $this->locked($projectRoot, $pageKey, function (string $base) use ($projectRoot, $payload, $expectedActivationRevision): array {
            $head = $this->readHead($projectRoot, $base);
            $this->assertExpectedRevision($head, $expectedActivationRevision);
            $digest = 'sha256:' . hash('sha256', CanonicalJson::encode($payload));
            $snapshot = $payload + ['snapshot_digest' => $digest];
            $this->writes->putNewOrIdentical(
                $projectRoot,
                $base . '/snapshots/' . $this->digestHex($digest) . '.json',
                CanonicalJson::encodePretty($snapshot),
                'COMPOSITION_RECIPE_SNAPSHOT_COLLISION',
            );

            return $this->activateSnapshot($projectRoot, $base, $snapshot, ($head['activation_revision'] ?? 0) + 1);
        });
    }

    /** @param array<string, mixed> $snapshot @return array<string, mixed> */
    private function activateSnapshot(string $projectRoot, string $base, array $snapshot, int $revision): array
    {
        $head = [
            'schema' => 'docara.composition_recipe_activation.v1',
            'activation_revision' => $revision,
            'snapshot_digest' => $snapshot['snapshot_digest'],
        ];
        $candidate = $base . '/.active-' . bin2hex(random_bytes(12)) . '.json';
        $this->writes->putNew($projectRoot, $candidate, CanonicalJson::encodePretty($head), 'COMPOSITION_RECIPE_HEAD_COLLISION');
        $candidatePath = $this->writes->regularFile($projectRoot, $candidate);
        $activePath = $this->writes->writablePath($projectRoot, $base . '/active.json');
        if (file_exists($activePath)) {
            $activePath = $this->writes->regularFile($projectRoot, $base . '/active.json');
        }
        if (! @rename($candidatePath, $activePath)) {
            @unlink($candidatePath);
            throw new PortableConfigurationException('COMPOSITION_RECIPE_ACTIVATION_FAILED', 'The verified Recipe snapshot could not be activated.');
        }

        return $snapshot + ['activation_revision' => $revision];
    }

    /** @param array<string, mixed>|null $head */
    private function assertExpectedRevision(?array $head, ?int $expected): void
    {
        $actual = $head['activation_revision'] ?? null;
        if (($actual === null && $expected !== null) || ($actual !== null && $actual !== $expected)) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_ACTIVATION_CONFLICT', 'The active Recipe snapshot changed before publication.');
        }
    }

    /** @return array<string, mixed>|null */
    private function readHead(string $projectRoot, string $base): ?array
    {
        $path = $this->writes->writablePath($projectRoot, $base . '/active.json');
        if (! is_file($path)) {
            return null;
        }

        $head = $this->decode($this->writes->regularFile($projectRoot, $base . '/active.json'), 'COMPOSITION_RECIPE_HEAD_INVALID');
        $this->assertHead($head);

        return $head;
    }

    /** @param array<string, mixed> $head @return array<string, mixed> */
    private function hydrate(string $projectRoot, string $base, string $pageKey, array $head): array
    {
        $this->assertHead($head);
        $snapshot = $this->decode(
            $this->writes->regularFile($projectRoot, $base . '/snapshots/' . $this->digestHex($head['snapshot_digest']) . '.json'),
            'COMPOSITION_RECIPE_SNAPSHOT_MISSING',
        );
        $this->assertSnapshot($snapshot, $pageKey, $head['snapshot_digest']);

        return $snapshot + ['activation_revision' => $head['activation_revision']];
    }

    /** @param array<string, mixed> $head */
    private function assertHead(array $head): void
    {
        if (($head['schema'] ?? null) !== 'docara.composition_recipe_activation.v1'
            || ! is_int($head['activation_revision'] ?? null)
            || $head['activation_revision'] < 1
            || ! is_string($head['snapshot_digest'] ?? null)
        ) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_HEAD_INVALID', 'The active Recipe snapshot pointer is invalid.');
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function assertSnapshot(array $snapshot, string $pageKey, string $digest): void
    {
        $stored = $snapshot['snapshot_digest'] ?? null;
        unset($snapshot['snapshot_digest']);
        if ($stored !== $digest
            || ($snapshot['schema'] ?? null) !== 'docara.composition_recipe_snapshot.v1'
            || ($snapshot['page_key'] ?? null) !== $pageKey
            || ! hash_equals($digest, 'sha256:' . hash('sha256', CanonicalJson::encode($snapshot)))
        ) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_SNAPSHOT_INTEGRITY_FAILED', 'The stored Recipe snapshot failed its digest check.');
        }
    }

    /** @return array<string, mixed> */
    private function decode(string $path, string $code): array
    {
        try {
            $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new PortableConfigurationException($code, 'Stored Recipe state is not valid JSON.', $exception);
        }
        if (! is_array($value) || array_is_list($value)) {
            throw new PortableConfigurationException($code, 'Stored Recipe state must be a JSON object.');
        }

        return $value;
    }

    /** @template T @param callable(string):T $callback @return T */
    private function locked(string $projectRoot, string $pageKey, callable $callback, bool $exclusive = true): mixed
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/D', $pageKey) !== 1) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_PAGE_KEY_INVALID', 'The Recipe page key is invalid.');
        }
        $base = '.docara/composition-recipe/' . hash('sha256', $pageKey);
        $this->writes->ensureDirectory($projectRoot, $base . '/snapshots');
        $lockPath = $this->writes->writablePath($projectRoot, $base . '/.lock');
        $handle = fopen($lockPath, 'c+b');
        if ($handle !== false) {
            try {
                $this->writes->regularFile($projectRoot, $base . '/.lock');
            } catch (PortableConfigurationException $exception) {
                fclose($handle);
                throw $exception;
            }
        }
        if ($handle === false || ! flock($handle, $exclusive ? LOCK_EX : LOCK_SH)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new PortableConfigurationException('COMPOSITION_RECIPE_LOCK_FAILED', 'The Recipe snapshot lock is unavailable.');
        }
        try {
            return $callback($base);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function digestHex(string $digest): string
    {
        if (preg_match('/^sha256:([a-f0-9]{64})$/D', $digest, $match) !== 1) {
            throw new PortableConfigurationException('COMPOSITION_RECIPE_DIGEST_INVALID', 'The Recipe snapshot digest is invalid.');
        }

        return $match[1];
    }
}
