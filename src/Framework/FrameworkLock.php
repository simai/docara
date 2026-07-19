<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class FrameworkLock
{
    /** @param array<string, mixed> $data */
    private function __construct(private array $data)
    {
        $this->assertValid();
        try {
            (new SchemaRepository)->assertValid($data, 'framework-lock.schema.json');
        } catch (PortableConfigurationException $exception) {
            throw new FrameworkComponentException('FRAMEWORK_LOCK_SCHEMA_INVALID', $exception->getMessage());
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromJsonFile(string $path): self
    {
        $json = @file_get_contents($path);
        if (! is_string($json)) {
            throw new FrameworkComponentException('FRAMEWORK_LOCK_MISSING', $path);
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new FrameworkComponentException('FRAMEWORK_LOCK_JSON_INVALID', $exception->getMessage());
        }
        if (! is_array($data)) {
            throw new FrameworkComponentException('FRAMEWORK_LOCK_INVALID');
        }

        return new self($data);
    }

    /** @return array<string, mixed> */
    public function runtime(): array
    {
        return $this->data['runtime'];
    }

    public function pairId(): string
    {
        return (string) $this->data['runtime']['pair_id'];
    }

    /** @return array<string, mixed> */
    public function assetProjection(): array
    {
        return $this->data['asset_projection'];
    }

    /** @return list<string> */
    public function nonclaims(): array
    {
        return $this->data['nonclaims'];
    }

    /** @return array<string, mixed> */
    public function manifest(string $key): array
    {
        $record = $this->data['manifests'][$key] ?? null;
        if (! is_array($record)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_NOT_LOCKED', $key);
        }

        return $record;
    }

    /** @return list<string> */
    public function manifestKeys(): array
    {
        $keys = array_keys($this->data['manifests']);
        sort($keys, SORT_STRING);

        return $keys;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    private function assertValid(): void
    {
        if (($this->data['schema'] ?? null) !== 'docara.framework_lock.v1') {
            throw new FrameworkComponentException('FRAMEWORK_LOCK_SCHEMA_INVALID');
        }

        $runtime = $this->data['runtime'] ?? null;
        if (! is_array($runtime)
            || ($runtime['schema'] ?? null) !== 'larena.ui.frontend_runtime_lock.v3'
            || ($runtime['runtime'] ?? null) !== 'simai-framework'
            || ! $this->isIdentifier($runtime['pair_id'] ?? null)
            || ! $this->isCommit($runtime['ui']['commit'] ?? null)
            || ! $this->isCommit($runtime['ui_smart']['commit'] ?? null)
            || ! is_array($runtime['boot'] ?? null)
            || ! is_array($runtime['components'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_LOCK_INVALID');
        }

        $manifests = $this->data['manifests'] ?? null;
        if (! is_array($manifests)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_LOCKS_INVALID');
        }
        if ($manifests === [] || array_is_list($manifests)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_LOCKS_INVALID');
        }
        foreach ($manifests as $key => $record) {
            if (! is_string($key)
                || preg_match('/\Aui(?:\.[a-z][a-z0-9_]*)+\z/D', $key) !== 1
                || ! is_array($record)
                || ($record['provider'] ?? null) !== 'larena/ui'
                || ! $this->isCommit($record['provider_revision'] ?? null)
                || ! $this->isSha256($record['sha256'] ?? null)
            ) {
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_LOCK_INVALID', $key);
            }
        }

        $projection = $this->data['asset_projection'] ?? null;
        if (! is_array($projection)
            || ($projection['schema'] ?? null) !== 'docara.framework_asset_projection.v1'
            || ($projection['mount'] ?? null) !== '_docara/framework'
            || ($projection['source']['provider'] ?? null) !== 'simai/ui-smart'
            || ($projection['source']['revision'] ?? null) !== ($runtime['ui_smart']['commit'] ?? null)
            || ! is_array($projection['files'] ?? null)
            || $projection['files'] === []
            || array_is_list($projection['files'])
        ) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_PROJECTION_INVALID');
        }
        foreach ($projection['files'] as $relativePath => $record) {
            if (! is_string($relativePath)
                || ! $this->isSafeRelativePath($relativePath)
                || ! str_starts_with($relativePath, 'smart/')
                || ! is_array($record)
                || array_keys($record) !== ['sha256']
                || ! $this->isSha256($record['sha256'] ?? null)
            ) {
                throw new FrameworkComponentException('FRAMEWORK_ASSET_PROJECTION_FILE_INVALID', $relativePath);
            }
        }

        $nonclaims = $this->data['nonclaims'] ?? null;
        if (! is_array($nonclaims)
            || ! array_is_list($nonclaims)
            || array_filter($nonclaims, 'is_string') !== $nonclaims
            || ! in_array('production_ready', $nonclaims, true)
            || ! in_array('all_framework_components_ready', $nonclaims, true)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_NONCLAIMS_INVALID');
        }

        foreach ([
            '/runtime/tag' => $runtime['tag'] ?? null,
            '/runtime/ui/tag' => $runtime['ui']['tag'] ?? null,
            '/runtime/ui_smart/tag' => $runtime['ui_smart']['tag'] ?? null,
        ] as $path => $reference) {
            $this->assertPinnedReleaseReference($reference, $path);
        }
    }

    private function assertPinnedReleaseReference(mixed $value, string $path): void
    {
        $normalized = is_string($value) ? strtolower(trim($value)) : '';
        if (in_array($normalized, ['main', 'master', 'latest', 'dev-main', 'refs/heads/main'], true)
            || preg_match('~(?:^|[/@])(?:main|master|latest)(?:$|[/])~', $normalized) === 1
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MOVING_REFERENCE_FORBIDDEN', $path);
        }
        if (preg_match('/^v\d+\.\d+\.\d+$/', $normalized) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_RELEASE_REFERENCE_INVALID', $path);
        }
    }

    private function isCommit(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{40}$/', $value) === 1;
    }

    private function isSha256(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{64}$/', $value) === 1;
    }

    private function isIdentifier(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9][a-z0-9._-]+$/', $value) === 1;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}
