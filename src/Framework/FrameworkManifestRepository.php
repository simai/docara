<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;

final readonly class FrameworkManifestRepository
{
    public const PROVIDER_REVISION = '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663';

    private const MANIFEST_FILES = [
        'ui.button' => 'manifests/ui-button.json',
        'ui.alert' => 'manifests/ui-alert.json',
    ];

    public function __construct(
        private FrameworkLock $lock,
        private string $resourceRoot,
    ) {
        $this->assertBundledRuntime();
    }

    public static function bundled(FrameworkLock $lock): self
    {
        return new self($lock, dirname(__DIR__, 2) . '/resources/framework');
    }

    /** @return array<string, mixed> */
    public function get(string $key): array
    {
        $relativePath = self::MANIFEST_FILES[$key] ?? null;
        if ($relativePath === null) {
            throw new FrameworkComponentException('FRAMEWORK_COMPONENT_UNSUPPORTED', $key);
        }

        $record = $this->lock->manifest($key);
        if (($record['provider'] ?? null) !== 'larena/ui'
            || ($record['provider_revision'] ?? null) !== self::PROVIDER_REVISION
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_PROVIDER_MISMATCH', $key);
        }

        $path = $this->resourceRoot . '/' . $relativePath;
        $json = @file_get_contents($path);
        if (! is_string($json)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_MISSING', $key);
        }
        $actualSha = hash('sha256', $json);
        if (! hash_equals((string) $record['sha256'], $actualSha)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_HASH_MISMATCH', $key);
        }

        try {
            $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_JSON_INVALID', $key);
        }
        if (! is_array($manifest)
            || ($manifest['schema'] ?? null) !== 'larena.ui.smart_manifest.v1'
            || ($manifest['key'] ?? null) !== $key
            || ($manifest['owner_package'] ?? null) !== 'larena/ui'
            || ($manifest['render']['strategy'] ?? null) !== 'host'
            || ($manifest['render']['renderer'] ?? null) !== 'ui.sf.element'
            || ($manifest['frontend']['runtime'] ?? null) !== 'simai-framework'
            || ! is_string($manifest['frontend']['tag'] ?? null)
            || ! is_array($manifest['props']['properties'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_INVALID', $key);
        }

        return $manifest;
    }

    /** @return array<string, mixed> */
    public function runtime(): array
    {
        return $this->lock->runtime();
    }

    public function pairId(): string
    {
        return $this->lock->pairId();
    }

    /** @return array<string, mixed> */
    public function assetProjection(): array
    {
        return $this->lock->assetProjection();
    }

    public function bundledAsset(string $relativePath): string
    {
        $this->assertSafeRelativePath($relativePath);
        $projection = $this->lock->assetProjection();
        $record = $projection['files'][$relativePath] ?? null;
        if (! is_array($record) || ! is_string($record['sha256'] ?? null)) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_NOT_PROJECTED', $relativePath);
        }

        $path = $this->resourceRoot . '/assets/' . $relativePath;
        $bytes = @file_get_contents($path);
        if (! is_string($bytes)) {
            throw new FrameworkComponentException('FRAMEWORK_BUNDLED_ASSET_MISSING', $relativePath);
        }
        if (! hash_equals($record['sha256'], hash('sha256', $bytes))) {
            throw new FrameworkComponentException('FRAMEWORK_BUNDLED_ASSET_HASH_MISMATCH', $relativePath);
        }

        return $bytes;
    }

    /** @return list<string> */
    public function nonclaims(): array
    {
        return $this->lock->nonclaims();
    }

    private function assertBundledRuntime(): void
    {
        $path = $this->resourceRoot . '/runtime-lock.json';
        $json = @file_get_contents($path);
        if (! is_string($json)) {
            throw new FrameworkComponentException('FRAMEWORK_BUNDLED_RUNTIME_MISSING');
        }
        try {
            $runtime = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new FrameworkComponentException('FRAMEWORK_BUNDLED_RUNTIME_INVALID');
        }
        if (! is_array($runtime)
            || CanonicalJson::encode($runtime) !== CanonicalJson::encode($this->lock->runtime())
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_PROJECTION_MISMATCH');
        }
    }

    private function assertSafeRelativePath(string $relativePath): void
    {
        if ($relativePath === ''
            || str_starts_with($relativePath, '/')
            || str_contains($relativePath, '\\')
            || str_contains($relativePath, "\0")
        ) {
            throw new FrameworkComponentException('FRAMEWORK_ASSET_PATH_INVALID', $relativePath);
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new FrameworkComponentException('FRAMEWORK_ASSET_PATH_INVALID', $relativePath);
            }
        }
    }
}
