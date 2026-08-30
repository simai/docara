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

    /** @var array<string, mixed> */
    private array $effectiveRuntime;

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
        $packageRoot = dirname(__DIR__, 2);
        $packageLock = FrameworkLock::fromJsonFile(
            $packageRoot . '/stubs/portable/simai-framework.lock.json',
        );
        $effectiveLock = self::effectiveBundledLock(
            $lock,
            $packageLock,
            $packageRoot . '/resources/framework/runtime-lock.json',
        );

        return new self($effectiveLock, $packageRoot . '/resources/framework');
    }

    private static function effectiveBundledLock(
        FrameworkLock $projectLock,
        FrameworkLock $packageLock,
        string $runtimeLockPath,
    ): FrameworkLock {
        if ($projectLock->pairId() !== $packageLock->pairId()) {
            return $projectLock;
        }
        $packageProjection = $packageLock->runtimeProjection();
        $projectProjection = $projectLock->runtimeProjection();
        if (! is_array($packageProjection)
            || ! self::sameRuntimeProjectionSource($packageProjection, $packageLock->runtime())
            || (is_array($projectProjection)
                && ! self::sameRuntimeProjectionSource($projectProjection, $projectLock->runtime()))
        ) {
            return $projectLock;
        }
        $bytes = @file_get_contents($runtimeLockPath);
        try {
            $runtimeLock = is_string($bytes)
                ? json_decode($bytes, true, 512, JSON_THROW_ON_ERROR)
                : null;
        } catch (\JsonException) {
            $runtimeLock = null;
        }
        $admitted = $projectProjection === null
            || CanonicalJson::encode($projectProjection) === CanonicalJson::encode($packageProjection);
        if (! $admitted && is_array($projectProjection)) {
            $compatibility = is_array($runtimeLock)
                ? ($runtimeLock['asset_planner'] ?? null)
                : null;
            $superseded = is_array($compatibility)
                && ($compatibility['schema'] ?? null) === 'simai.framework.asset_planner_compatibility.v1'
                && is_array($compatibility['superseded_runtime_projections'] ?? null)
                && array_is_list($compatibility['superseded_runtime_projections'])
                    ? $compatibility['superseded_runtime_projections']
                    : [];
            foreach ($superseded as $record) {
                if (is_array($record)
                    && array_keys($record) === ['packet_sha256', 'files', 'manifest_sha256']
                    && ($record['packet_sha256'] ?? null) === ($projectProjection['packet_sha256'] ?? null)
                    && ($record['files'] ?? null) === ($projectProjection['files'] ?? null)
                    && ($record['manifest_sha256'] ?? null) === ($projectProjection['manifest']['sha256'] ?? null)
                ) {
                    $admitted = true;
                    break;
                }
            }
        }
        if (! $admitted) {
            return $projectLock;
        }
        $data = $projectLock->toArray();
        $data['runtime_projection'] = $packageProjection;

        $projectTypography = $projectLock->typographyProjection();
        $packageTypography = $packageLock->typographyProjection();
        if (is_array($projectTypography)
            && is_array($packageTypography)
            && CanonicalJson::encode($projectTypography) !== CanonicalJson::encode($packageTypography)
        ) {
            $compatibleTypography = false;
            $compatibility = is_array($runtimeLock)
                ? ($runtimeLock['asset_planner'] ?? null)
                : null;
            $superseded = is_array($compatibility)
                && is_array($compatibility['superseded_typography_projections'] ?? null)
                && array_is_list($compatibility['superseded_typography_projections'])
                    ? $compatibility['superseded_typography_projections']
                    : [];
            foreach ($superseded as $record) {
                if (! is_array($record)
                    || array_keys($record) !== ['packet_sha256', 'core_sha256']
                    || ($record['packet_sha256'] ?? null) !== ($projectTypography['packet_sha256'] ?? null)
                    || ($record['core_sha256'] ?? null) !== ($projectTypography['files']['core']['sha256'] ?? null)
                ) {
                    continue;
                }
                $candidate = $projectTypography;
                $candidate['packet_sha256'] = $packageTypography['packet_sha256'];
                $candidate['files']['core']['sha256'] = $packageTypography['files']['core']['sha256'];
                if (CanonicalJson::encode($candidate) === CanonicalJson::encode($packageTypography)) {
                    $compatibleTypography = true;
                    break;
                }
            }
            if ($compatibleTypography) {
                $data['typography_projection'] = $packageTypography;
            }
        }

        return FrameworkLock::fromArray($data);
    }

    /** @param array<string, mixed> $projection @param array<string, mixed> $runtime */
    private static function sameRuntimeProjectionSource(array $projection, array $runtime): bool
    {
        return ($projection['source']['provider'] ?? null) === 'simai/ui'
            && ($projection['source']['revision'] ?? null) === ($runtime['ui']['commit'] ?? null)
            && ($projection['source']['tree_sha256'] ?? null) === ($runtime['ui']['sha256'] ?? null);
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
        return $this->effectiveRuntime;
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

    /** @return array<string, mixed>|null */
    public function iconProjection(): ?array
    {
        return $this->lock->iconProjection();
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

    public function bundledPortableAsset(string $relativePath): string
    {
        $this->assertSafeRelativePath($relativePath);
        $resources = dirname($this->resourceRoot);
        $path = $resources . '/portable/' . $relativePath;
        $this->assertTrustedResourceFile(
            $resources,
            $path,
            'FRAMEWORK_PORTABLE_ASSET_UNSAFE',
            'FRAMEWORK_PORTABLE_ASSET_MISSING',
            $relativePath,
        );
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            throw new FrameworkComponentException('FRAMEWORK_PORTABLE_ASSET_MISSING', $relativePath);
        }
        if (preg_match('//u', $bytes) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_PORTABLE_ASSET_ENCODING_INVALID', $relativePath);
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

    public function bundledIconAsset(string $key): string
    {
        $projection = $this->lock->iconProjection();
        $record = $projection['files'][$key] ?? null;
        if (! is_array($record)
            || ! is_string($record['path'] ?? null)
            || ! is_string($record['sha256'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_ASSET_NOT_PROJECTED', $key);
        }

        $resources = dirname($this->resourceRoot);
        $path = $resources . '/' . $record['path'];
        $this->assertTrustedResourceFile(
            $resources,
            $path,
            'FRAMEWORK_ICON_ASSET_UNSAFE',
            'FRAMEWORK_ICON_ASSET_MISSING',
            $key,
        );
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            throw new FrameworkComponentException('FRAMEWORK_ICON_ASSET_MISSING', $key);
        }
        if (! hash_equals($record['sha256'], hash('sha256', $bytes))) {
            throw new FrameworkComponentException('FRAMEWORK_ICON_ASSET_HASH_MISMATCH', $key);
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
        $lockedRuntime = $this->lock->runtime();
        if (! is_array($runtime)
            || CanonicalJson::encode($this->runtimeCompatibilityView($runtime, $lockedRuntime))
                !== CanonicalJson::encode($lockedRuntime)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_PROJECTION_MISMATCH');
        }
        // The shell loader contract is package-owned metadata. Older project
        // locks may omit it, but after the compatibility view proves that the
        // pinned runtime identity and every supplied field match exactly, the
        // bundled contract is the authoritative effective runtime.
        $this->effectiveRuntime = $runtime;

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
        if ($this->lock->iconProjection() !== null) {
            $projection = $this->lock->iconProjection();
            $ledger = '';
            foreach (['license', 'outlined', 'rounded', 'sharp'] as $key) {
                $record = $projection['files'][$key];
                $bytes = $this->bundledIconAsset($key);
                $ledger .= hash('sha256', $bytes) . '  ' . basename((string) $record['path']) . "\n";
            }
            if (! hash_equals((string) $projection['packet_sha256'], hash('sha256', $ledger))) {
                throw new FrameworkComponentException('FRAMEWORK_ICON_PACKET_HASH_MISMATCH');
            }
        }
    }

    /**
     * Older project locks predate the optional package-owned loader metadata.
     * Admit only that exact omission; supplied metadata must still match the
     * bundled contract byte-for-byte.
     *
     * @param  array<string, mixed>  $bundled
     * @param  array<string, mixed>  $locked
     * @return array<string, mixed>
     */
    private function runtimeCompatibilityView(array $bundled, array $locked): array
    {
        unset($bundled['asset_planner']);
        $documentationSource = $locked['framework_registry']['documentation_source'] ?? null;
        if (is_array($documentationSource) && is_array($bundled['framework_registry'] ?? null)) {
            $bundled['framework_registry']['documentation_source'] = $documentationSource;
        }
        if (! array_key_exists('shell', $locked)) {
            unset($bundled['shell']);
        }
        $lockedComponents = $locked['components'] ?? null;
        if (! is_array($lockedComponents) || ! is_array($bundled['components'] ?? null)) {
            return $bundled;
        }
        foreach ($lockedComponents as $tag => $component) {
            if (! is_string($tag)
                || ! is_array($component)
                || array_key_exists('loader', $component)
                || ! is_array($bundled['components'][$tag] ?? null)
            ) {
                continue;
            }
            unset($bundled['components'][$tag]['loader']);
        }

        return $bundled;
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
