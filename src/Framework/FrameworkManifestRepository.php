<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Smart\SmartManifestValidationException;
use Simai\Docara\Smart\SmartManifestValidator;

final readonly class FrameworkManifestRepository
{
    public const PROVIDER_REVISION = '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663';

    /** @var array<string, mixed>|null */
    private ?array $validatedRuntimeManifest;

    public function __construct(
        private FrameworkLock $lock,
        private string $resourceRoot,
        private SmartManifestValidator $commonValidator = new SmartManifestValidator,
    ) {
        $this->validatedRuntimeManifest = $this->lock->runtimeProjection() === null
            ? null
            : $this->loadRuntimeManifest();
        $this->assertBundledRuntime();
    }

    public static function bundled(FrameworkLock $lock): self
    {
        return new self($lock, dirname(__DIR__, 2) . '/resources/framework');
    }

    /** @return array<string, mixed> */
    public function get(string $key): array
    {
        if (! in_array($key, $this->keys(), true)) {
            throw new FrameworkComponentException('FRAMEWORK_COMPONENT_UNSUPPORTED', $key);
        }

        $record = $this->lock->manifest($key);
        if (($record['provider'] ?? null) !== 'larena/ui'
            || ($record['provider_revision'] ?? null) !== self::PROVIDER_REVISION
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_PROVIDER_MISMATCH', $key);
        }

        $relativePath = $this->manifestRelativePath($key);
        $path = $this->resourceRoot . '/' . $relativePath;
        $this->assertTrustedRegularFile(
            $path,
            'FRAMEWORK_MANIFEST_SOURCE_UNSAFE',
            'FRAMEWORK_MANIFEST_MISSING',
            $key,
        );
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
            || ! is_string($manifest['version'] ?? null)
            || preg_match('/^v?\\d+\\.\\d+\\.\\d+$/D', $manifest['version']) !== 1
            || ($manifest['owner_package'] ?? null) !== 'larena/ui'
            || ($manifest['kind'] ?? null) !== 'smart'
            || ($manifest['props']['type'] ?? null) !== 'object'
            || ($manifest['render']['strategy'] ?? null) !== 'host'
            || ($manifest['render']['renderer'] ?? null) !== 'ui.sf.element'
            || ($manifest['frontend']['runtime'] ?? null) !== 'simai-framework'
            || ! is_string($manifest['frontend']['tag'] ?? null)
            || preg_match('/^sf-[a-z][a-z0-9-]*$/D', $manifest['frontend']['tag']) !== 1
            || ! is_array($manifest['props']['properties'] ?? null)
            || ! is_array($manifest['props']['required'] ?? null)
            || ($manifest['props']['additionalProperties'] ?? null) !== false
            || ! is_array($manifest['presets'] ?? null)
            || ! is_array($manifest['constraints'] ?? null)
            || ! is_array($manifest['assets'] ?? null)
            || ! array_is_list($manifest['assets'])
            || $manifest['assets'] === []
            || ! is_array($manifest['atlas']['example_props'] ?? null)
            || array_is_list($manifest['atlas']['example_props'])
            || ! is_array($manifest['atlas']['controls'] ?? null)
            || ! is_array($manifest['atlas']['readiness'] ?? null)
            || ($manifest['provenance']['reference_status'] ?? null) !== 'source_backed'
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_INVALID', $key);
        }
        try {
            $this->commonValidator->assertValid($key, $manifest);
        } catch (SmartManifestValidationException $exception) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_INVALID', $exception->getMessage());
        }
        if (($manifest['provenance']['upstream_revision'] ?? null)
            !== ($this->lock->runtime()['ui_smart']['commit'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_UPSTREAM_REVISION_MISMATCH', $key);
        }
        (new FrameworkManifestContract)->assertValid($key, $manifest);
        $componentAsset = 'simai.framework.'
            . str_replace('-', '_', $manifest['frontend']['tag'])
            . '.js';
        $assetKeys = [];
        foreach ($manifest['assets'] as $asset) {
            if (! is_array($asset)
                || ! is_string($asset['key'] ?? null)
                || ! is_string($asset['kind'] ?? null)
                || ! is_bool($asset['critical'] ?? null)
            ) {
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_INVALID', $key);
            }
            $assetKeys[] = $asset['key'];
        }
        if (! in_array($componentAsset, $assetKeys, true)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_COMPONENT_ASSET_MISSING', $key);
        }

        return $manifest;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return $this->lock->manifestKeys();
    }

    public function providerRevision(string $key): string
    {
        return (string) $this->lock->manifest($key)['provider_revision'];
    }

    public function manifestReference(string $key): string
    {
        if (! in_array($key, $this->keys(), true)) {
            throw new FrameworkComponentException('FRAMEWORK_COMPONENT_UNSUPPORTED', $key);
        }

        return 'resources/framework/' . $this->manifestRelativePath($key);
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

    /** @return array<string, mixed>|null */
    public function typographyProjection(): ?array
    {
        return $this->lock->typographyProjection();
    }

    /** @return array<string, mixed>|null */
    public function runtimeProjection(): ?array
    {
        return $this->lock->runtimeProjection();
    }

    /** @return array<string, mixed> */
    public function runtimeManifest(): array
    {
        if ($this->validatedRuntimeManifest === null) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_MANIFEST_NOT_PROJECTED');
        }

        return $this->validatedRuntimeManifest;
    }

    /** @return array<string, mixed> */
    private function loadRuntimeManifest(): array
    {
        $projection = $this->lock->runtimeProjection();
        $record = $projection['manifest'] ?? null;
        if (! is_array($projection)
            || ! is_array($record)
            || ! is_string($record['path'] ?? null)
            || ! is_string($record['sha256'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_MANIFEST_NOT_PROJECTED');
        }
        $resources = dirname($this->resourceRoot);
        $path = $resources . '/' . $record['path'];
        $this->assertTrustedResourceFile(
            $resources,
            $path,
            'FRAMEWORK_RUNTIME_MANIFEST_UNSAFE',
            'FRAMEWORK_RUNTIME_MANIFEST_MISSING',
        );
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_MANIFEST_MISSING');
        }
        if (! hash_equals($record['sha256'], hash('sha256', $bytes))) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_MANIFEST_HASH_MISMATCH');
        }
        try {
            $manifest = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_MANIFEST_INVALID');
        }
        if (! is_array($manifest)
            || ($manifest['schema'] ?? null) !== 'docara.framework_runtime_assets.v1'
            || ($manifest['root'] ?? null) !== 'distr'
            || ($manifest['source'] ?? null) !== $projection['source']
            || ($manifest['packet_sha256'] ?? null) !== $projection['packet_sha256']
            || ! is_array($manifest['files'] ?? null)
            || array_is_list($manifest['files'])
            || count($manifest['files']) !== $projection['files']
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_MANIFEST_INVALID');
        }
        foreach ($manifest['files'] as $relativePath => $file) {
            if (! is_string($relativePath)
                || ! $this->isSafeRelativePath($relativePath)
                || ! is_array($file)
                || array_keys($file) !== ['sha256']
                || ! is_string($file['sha256'])
                || preg_match('/^[a-f0-9]{64}$/', $file['sha256']) !== 1
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_RUNTIME_MANIFEST_FILE_INVALID',
                    is_string($relativePath) ? $relativePath : '',
                );
            }
        }

        return $manifest;
    }

    /** @return array{sha256: string} */
    public function runtimeAssetRecord(string $relativePath): array
    {
        $this->assertSafeRelativePath($relativePath);
        $record = $this->runtimeManifest()['files'][$relativePath] ?? null;
        if (! is_array($record) || ! is_string($record['sha256'] ?? null)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_NOT_PROJECTED', $relativePath);
        }

        return $record;
    }

    public function bundledRuntimeAsset(string $relativePath): string
    {
        $this->assertSafeRelativePath($relativePath);
        $projection = $this->lock->runtimeProjection();
        if (! is_array($projection)) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_NOT_PROJECTED', $relativePath);
        }
        $record = $this->runtimeAssetRecord($relativePath);
        $revision = $projection['source']['revision'] ?? null;
        if (! is_array($record)
            || ! is_string($record['sha256'] ?? null)
            || ! is_string($revision)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_NOT_PROJECTED', $relativePath);
        }

        $resources = dirname($this->resourceRoot);
        $path = $resources . '/portable/vendor/simai-framework/runtime/'
            . $revision . '/distr/' . $relativePath;
        $this->assertTrustedResourceFile(
            $resources,
            $path,
            'FRAMEWORK_RUNTIME_ASSET_UNSAFE',
            'FRAMEWORK_RUNTIME_ASSET_MISSING',
            $relativePath,
        );
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_MISSING', $relativePath);
        }
        if (! hash_equals($record['sha256'], hash('sha256', $bytes))) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_ASSET_HASH_MISMATCH', $relativePath);
        }

        return $bytes;
    }

    public function bundledTypographyAsset(string $key): string
    {
        $projection = $this->lock->typographyProjection();
        $record = $projection['files'][$key] ?? null;
        if (! is_array($record)
            || ! is_string($record['path'] ?? null)
            || ! is_string($record['sha256'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_NOT_PROJECTED', $key);
        }

        $resources = dirname($this->resourceRoot);
        $path = $resources . '/' . $record['path'];
        $stat = @lstat($path);
        $root = realpath($resources);
        $real = realpath($path);
        if (! is_array($stat)
            || is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || $root === false
            || $real === false
            || ! FilesystemPath::isWithin($real, $root)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_UNSAFE', $key);
        }
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_MISSING', $key);
        }
        if (! hash_equals($record['sha256'], hash('sha256', $bytes))) {
            throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_HASH_MISMATCH', $key);
        }

        return $bytes;
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
        $this->assertTrustedRegularFile(
            $path,
            'FRAMEWORK_BUNDLED_ASSET_UNSAFE',
            'FRAMEWORK_BUNDLED_ASSET_MISSING',
            $relativePath,
        );
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
        $this->assertTrustedRegularFile(
            $path,
            'FRAMEWORK_BUNDLED_RUNTIME_UNSAFE',
            'FRAMEWORK_BUNDLED_RUNTIME_MISSING',
        );
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

        if ($this->lock->typographyProjection() !== null) {
            foreach (array_keys($this->lock->typographyProjection()['files']) as $key) {
                $this->bundledTypographyAsset($key);
            }
        }
        if ($this->lock->runtimeProjection() !== null) {
            $projection = $this->lock->runtimeProjection();
            $manifest = $this->runtimeManifest();
            $paths = array_keys($manifest['files']);
            sort($paths, SORT_STRING);
            $ledger = '';
            foreach ($paths as $relativePath) {
                $bytes = $this->bundledRuntimeAsset($relativePath);
                $ledger .= hash('sha256', $bytes) . '  ' . $relativePath . "\n";
            }
            if (! hash_equals((string) $projection['packet_sha256'], hash('sha256', $ledger))) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_PACKET_HASH_MISMATCH');
            }
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

    private function isSafeRelativePath(string $relativePath): bool
    {
        if ($relativePath === ''
            || str_starts_with($relativePath, '/')
            || str_contains($relativePath, '\\')
            || str_contains($relativePath, "\0")
        ) {
            return false;
        }
        foreach (explode('/', $relativePath) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function assertTrustedRegularFile(
        string $path,
        string $unsafeCode,
        string $missingCode,
        string $detail = '',
    ): void {
        $stat = @lstat($path);
        if (! is_array($stat)) {
            throw new FrameworkComponentException($missingCode, $detail);
        }
        $root = realpath($this->resourceRoot);
        $real = realpath($path);
        if (is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || $root === false
            || $real === false
            || ! FilesystemPath::isWithin($real, $root)
        ) {
            throw new FrameworkComponentException($unsafeCode, $detail);
        }
    }

    private function assertTrustedResourceFile(
        string $rootPath,
        string $path,
        string $unsafeCode,
        string $missingCode,
        string $detail = '',
    ): void {
        $stat = @lstat($path);
        if (! is_array($stat)) {
            throw new FrameworkComponentException($missingCode, $detail);
        }
        $root = realpath($rootPath);
        $real = realpath($path);
        if (is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || $root === false
            || $real === false
            || ! FilesystemPath::isWithin($real, $root)
        ) {
            throw new FrameworkComponentException($unsafeCode, $detail);
        }
    }

    private function manifestRelativePath(string $key): string
    {
        if (preg_match('/\Aui(?:\.[a-z][a-z0-9_]*)+\z/D', $key) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_COMPONENT_UNSUPPORTED', $key);
        }

        return 'manifests/' . str_replace(['.', '_'], '-', $key) . '.json';
    }
}
