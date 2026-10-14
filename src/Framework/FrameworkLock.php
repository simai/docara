<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class FrameworkLock
{
    /** The lock entry of a product component package taken by its receipt. */
    public const PACKAGE_PROJECTION_SCHEMA = 'docara.framework_package_projection';

    /** Version 1 pinned a SIMAI Framework Pro release lock and is no longer read. */
    public const PACKAGE_PROJECTION_SCHEMA_VERSION = 2;

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

    /** @return array<string, mixed>|null */
    public function dynamicAssetProjection(): ?array
    {
        $projection = $this->data['dynamic_asset_projection'] ?? null;

        return is_array($projection) ? $projection : null;
    }

    /**
     * Where the Smart components come from. A lock v1 names the separate
     * simai/ui-smart repository (tree `smart`); a lock v2 names the `ui`
     * release itself, whose distribution carries Smart in `distr/smart` at the
     * same revision as Core (`ui-<12>`).
     *
     * @return array{provider: string, revision: string, release_lock_version: int}
     */
    public function smartSource(): array
    {
        return self::smartSourceOf($this->data['runtime']);
    }

    /**
     * @param  array<string, mixed>  $runtime
     * @return array{provider: string, revision: string, release_lock_version: int}
     */
    public static function smartSourceOf(array $runtime): array
    {
        $insideUi = ($runtime['ui_smart']['tree'] ?? null) === 'distr/smart';

        return [
            'provider' => $insideUi ? 'simai/ui' : 'simai/ui-smart',
            'revision' => (string) ($runtime['ui_smart']['commit'] ?? ''),
            'release_lock_version' => $insideUi ? 2 : 1,
        ];
    }

    /**
     * Product component packages served beside the Framework (Larena's
     * `larena` is the first), keyed by package name. Each is taken by its
     * package receipt and served under its own mount.
     *
     * @return array<string, array<string, mixed>>
     */
    public function packageProjections(): array
    {
        $projections = $this->data['package_projections'] ?? null;

        return is_array($projections) ? $projections : [];
    }

    /** @return array<string, mixed>|null */
    public function typographyProjection(): ?array
    {
        $projection = $this->data['typography_projection'] ?? null;

        return is_array($projection) ? $projection : null;
    }

    /** @return array<string, mixed>|null */
    public function runtimeProjection(): ?array
    {
        $projection = $this->data['runtime_projection'] ?? null;

        return is_array($projection) ? $projection : null;
    }

    /** @return array<string, mixed>|null */
    public function iconProjection(): ?array
    {
        $projection = $this->data['icon_projection'] ?? null;

        return is_array($projection) ? $projection : null;
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

    /** @return array<string, array<string, mixed>> */
    public function consumerPolicies(): array
    {
        $policies = [];
        foreach ($this->manifestKeys() as $key) {
            $policies[$key] = $this->consumerPolicy($key);
        }

        return $policies;
    }

    /** @return array<string, mixed> */
    public function consumerPolicy(string $key): array
    {
        $policy = $this->manifest($key)['consumer_policy'] ?? null;
        if (! is_array($policy) || array_is_list($policy)) {
            throw new FrameworkComponentException('FRAMEWORK_CONSUMER_POLICY_INVALID', $key);
        }

        return $policy;
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
        $smartSource = self::smartSourceOf($runtime);
        if ($smartSource['release_lock_version'] === 2
            && (($runtime['ui_smart']['commit'] ?? null) !== ($runtime['ui']['commit'] ?? null)
                || ($runtime['ui_smart']['mount'] ?? null) !== 'ui'
                || ($runtime['pair_id'] ?? null) !== 'ui-' . substr((string) $runtime['ui']['commit'], 0, 12))
        ) {
            // A v2 release is one `ui` commit: Smart is read from that commit's
            // `distr/smart`, and the release is named by that commit alone.
            throw new FrameworkComponentException('FRAMEWORK_RUNTIME_LOCK_V2_INVALID');
        }
        if ($smartSource['release_lock_version'] === 1
            && (($runtime['ui_smart']['tree'] ?? null) !== 'smart'
                || ($runtime['ui_smart']['mount'] ?? null) !== 'smart')
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
                || ! is_array($record['consumer_policy'] ?? null)
                || array_is_list($record['consumer_policy'])
            ) {
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_LOCK_INVALID', $key);
            }
        }

        $projection = $this->data['asset_projection'] ?? null;
        if (! is_array($projection)
            || ($projection['schema'] ?? null) !== 'docara.framework_asset_projection.v1'
            || ! in_array($projection['mount'] ?? null, ['_docara/framework', '_docara/framework-runtime'], true)
            || ($projection['source']['provider'] ?? null) !== $smartSource['provider']
            || ($projection['source']['revision'] ?? null) !== $smartSource['revision']
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

        $dynamicProjection = $this->data['dynamic_asset_projection'] ?? null;
        if ($dynamicProjection !== null) {
            if (! is_array($dynamicProjection)
                || ($dynamicProjection['schema'] ?? null) !== 'docara.framework_dynamic_asset_projection.v1'
                || ($dynamicProjection['mount'] ?? null) !== $projection['mount']
                || ($dynamicProjection['source']['provider'] ?? null) !== $smartSource['provider']
                || ($dynamicProjection['source']['revision'] ?? null) !== $smartSource['revision']
                || ! is_array($dynamicProjection['files'] ?? null)
                || $dynamicProjection['files'] === []
                || array_is_list($dynamicProjection['files'])
            ) {
                throw new FrameworkComponentException('FRAMEWORK_DYNAMIC_ASSET_PROJECTION_INVALID');
            }
            foreach ($dynamicProjection['files'] as $relativePath => $record) {
                if (! is_string($relativePath)
                    || ! $this->isSafeRelativePath($relativePath)
                    || ! str_starts_with($relativePath, 'smart/')
                    || isset($projection['files'][$relativePath])
                    || ! is_array($record)
                    || array_keys($record) !== ['sha256']
                    || ! $this->isSha256($record['sha256'] ?? null)
                ) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_DYNAMIC_ASSET_PROJECTION_FILE_INVALID',
                        $relativePath,
                    );
                }
            }
        }

        $this->assertPackageProjections($runtime, $smartSource);

        $typography = $this->data['typography_projection'] ?? null;
        if ($typography !== null) {
            if (! is_array($typography)
                || ($typography['schema'] ?? null) !== 'docara.framework_typography_projection.v1'
                || ! is_string($typography['candidate'] ?? null)
                || preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+(?:-rc\.[0-9]+)?\z/D', $typography['candidate']) !== 1
                || ! in_array($typography['source']['provider'] ?? null, ['simai/ui-source', 'simai/ui-loader'], true)
                || ! $this->isCommit($typography['source']['revision'] ?? null)
                || ! $this->isCommit($typography['source']['rollback_parent'] ?? null)
                || ($typography['builder']['provider'] ?? null) !== 'simai/ui-builder'
                || ! $this->isCommit($typography['builder']['revision'] ?? null)
                || ($typography['distribution']['provider'] ?? null) !== 'simai/ui'
                || ! $this->isCommit($typography['distribution']['revision'] ?? null)
                || ! $this->isCommit($typography['distribution']['rollback_parent'] ?? null)
                || ! is_bool($typography['distribution']['published'] ?? null)
                || (($typography['candidate'] ?? null) === '5.4.0-rc.1'
                    && ($typography['distribution']['published'] ?? null) !== false)
                || (($typography['candidate'] ?? null) === '5.4.0'
                    && ($typography['distribution']['published'] ?? null) !== true)
                || ! $this->isSha256($typography['packet_sha256'] ?? null)
                || ! is_array($typography['files'] ?? null)
                || array_keys(array_intersect_key($typography['files'], array_flip(['contract', 'core', 'utility']))) !== ['contract', 'core', 'utility']
                || count($typography['files']) !== 10
            ) {
                throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_PROJECTION_INVALID');
            }
            foreach ($typography['files'] as $key => $record) {
                if (! is_array($record)
                    || array_keys($record) !== ['path', 'public', 'sha256']
                    || ! $this->isSafeRelativePath($record['path'] ?? null)
                    || ! $this->isSafeRelativePath($record['public'] ?? null)
                    || ! str_starts_with((string) $record['path'], 'portable/vendor/simai-framework/typography/')
                    || ! str_starts_with((string) $record['public'], '_docara/vendor/simai-framework/typography/')
                    || ! $this->isSha256($record['sha256'] ?? null)
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_INVALID', (string) $key);
                }
                if (str_starts_with((string) $key, 'font_')) {
                    $digestName = substr((string) $key, 5);
                    if (preg_match('/\A[a-f0-9]{20}\z/D', $digestName) !== 1
                        || $record['path'] !== 'portable/vendor/simai-framework/typography/' . $digestName . '.woff2'
                        || $record['public'] !== '_docara/vendor/simai-framework/typography/' . $digestName . '.woff2'
                    ) {
                        throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_INVALID', (string) $key);
                    }
                } elseif (! in_array($key, ['contract', 'core', 'utility'], true)) {
                    throw new FrameworkComponentException('FRAMEWORK_TYPOGRAPHY_ASSET_INVALID', (string) $key);
                }
            }
        }

        $runtimeProjection = $this->data['runtime_projection'] ?? null;
        if ($runtimeProjection !== null) {
            $revision = $runtime['ui']['commit'] ?? null;
            $expectedMount = is_string($revision)
                ? '_docara/vendor/simai-framework/runtime/' . $revision . '/distr'
                : '';
            if (! is_array($runtimeProjection)
                || ($runtimeProjection['schema'] ?? null) !== 'docara.framework_runtime_projection.v1'
                || ($runtimeProjection['mount'] ?? null) !== $expectedMount
                || ($runtimeProjection['source']['provider'] ?? null) !== 'simai/ui'
                || ($runtimeProjection['source']['revision'] ?? null) !== $revision
                || ($runtimeProjection['source']['tree_sha256'] ?? null) !== ($runtime['ui']['sha256'] ?? null)
                || ! $this->isSha256($runtimeProjection['packet_sha256'] ?? null)
                || ! $this->isSafeRelativePath($runtimeProjection['icon_font'] ?? null)
                || ! is_int($runtimeProjection['files'] ?? null)
                || $runtimeProjection['files'] < 1
                || ! is_array($runtimeProjection['manifest'] ?? null)
                || array_keys($runtimeProjection['manifest']) !== ['path', 'public', 'sha256']
                || ! $this->isSafeRelativePath($runtimeProjection['manifest']['path'] ?? null)
                || ! $this->isSafeRelativePath($runtimeProjection['manifest']['public'] ?? null)
                || ! $this->isSha256($runtimeProjection['manifest']['sha256'] ?? null)
            ) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_PROJECTION_INVALID');
            }
            $runtimeBase = 'portable/vendor/simai-framework/runtime/' . $revision;
            if ($runtimeProjection['manifest']['path'] !== $runtimeBase . '/runtime-manifest.json'
                || $runtimeProjection['manifest']['public'] !== '_docara/vendor/simai-framework/runtime/'
                    . $revision . '/runtime-manifest.json'
            ) {
                throw new FrameworkComponentException('FRAMEWORK_RUNTIME_PROJECTION_INVALID');
            }
        }

        $iconProjection = $this->data['icon_projection'] ?? null;
        if ($iconProjection !== null) {
            $revision = $iconProjection['source']['revision'] ?? null;
            $base = is_string($revision)
                ? 'portable/vendor/google/material-symbols/' . $revision
                : '';
            $publicBase = is_string($revision)
                ? '_docara/vendor/google/material-symbols/' . $revision
                : '';
            if (! is_array($iconProjection)
                || ($iconProjection['schema'] ?? null) !== 'docara.framework_icon_projection.v1'
                || ($iconProjection['source']['provider'] ?? null) !== 'google/material-design-icons'
                || ! $this->isCommit($revision)
                || ($iconProjection['source']['license'] ?? null) !== 'Apache-2.0'
                || ! $this->isSha256($iconProjection['packet_sha256'] ?? null)
                || ! is_array($iconProjection['files'] ?? null)
                || array_keys($iconProjection['files']) !== ['license', 'outlined', 'rounded', 'sharp']
            ) {
                throw new FrameworkComponentException('FRAMEWORK_ICON_PROJECTION_INVALID');
            }
            foreach ($iconProjection['files'] as $key => $record) {
                if (! is_array($record)
                    || array_keys($record) !== ['path', 'public', 'sha256']
                    || ! $this->isSafeRelativePath($record['path'] ?? null)
                    || ! $this->isSafeRelativePath($record['public'] ?? null)
                    || ! $this->isSha256($record['sha256'] ?? null)
                    || ! str_starts_with((string) $record['path'], $base . '/')
                    || ! str_starts_with((string) $record['public'], $publicBase . '/')
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_ICON_ASSET_INVALID', (string) $key);
                }
            }
            foreach ([
                'license' => 'LICENSE',
                'outlined' => 'MaterialSymbolsOutlined.woff2',
                'rounded' => 'MaterialSymbolsRounded.woff2',
                'sharp' => 'MaterialSymbolsSharp.woff2',
            ] as $key => $filename) {
                if ($iconProjection['files'][$key]['path'] !== $base . '/' . $filename
                    || $iconProjection['files'][$key]['public'] !== $publicBase . '/' . $filename
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_ICON_ASSET_INVALID', $key);
                }
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
            if ($reference === null && ($runtime['publication_profile'] ?? null) === 'verified-commit-candidate-v1') {
                continue;
            }
            $this->assertPinnedReleaseReference($reference, $path);
        }
    }

    /**
     * A product component package is taken by its package receipt
     * (`simai.framework.product-package-receipt` version 1): the lock keeps
     * the receipt's identity (package source revision, free Framework source,
     * builder, manifest and archive digests), the `ui-<12>` release the
     * package must be served beside, and the hash of each file laid. Its files
     * are laid out like `distr/smart` under `smart/`, with `smart/rule.js` as
     * the rule file the Loader fetches. Packages exist only beside a v2
     * release, and each is served under its own mount, named by the package
     * source revision.
     *
     * @param  array<string, mixed>  $runtime
     * @param  array{provider: string, revision: string, release_lock_version: int}  $smartSource
     */
    private function assertPackageProjections(array $runtime, array $smartSource): void
    {
        if (! array_key_exists('package_projections', $this->data)) {
            return;
        }
        $packages = $this->data['package_projections'];
        if (! is_array($packages) || $packages === [] || array_is_list($packages)
            || $smartSource['release_lock_version'] !== 2
        ) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_PROJECTIONS_INVALID');
        }
        foreach ($packages as $name => $package) {
            if (is_array($package) && ($package['schema'] ?? null) === 'docara.framework_package_projection.v1') {
                // Docara 2.16 read SIMAI Framework Pro release locks; Pro is
                // dropped, and its components live in product packages.
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_PROJECTION_UNSUPPORTED', (string) $name);
            }
            $receipt = is_array($package) ? ($package['receipt'] ?? null) : null;
            $revision = is_array($receipt) ? ($receipt['package_source']['revision'] ?? null) : null;
            if (! is_string($name)
                || preg_match('/\A[a-z][a-z0-9-]*\z/D', $name) !== 1
                || ! is_array($package)
                || ($package['schema'] ?? null) !== self::PACKAGE_PROJECTION_SCHEMA
                || ($package['schema_version'] ?? null) !== self::PACKAGE_PROJECTION_SCHEMA_VERSION
                || ($package['package'] ?? null) !== $name
                || ($package['requires'] ?? null) !== ($runtime['pair_id'] ?? null)
                || ! is_array($receipt)
                || ($receipt['schema'] ?? null) !== FrameworkPackageReceipt::SCHEMA
                || ($receipt['schema_version'] ?? null) !== FrameworkPackageReceipt::SCHEMA_VERSION
                || ! $this->isCommit($revision)
                || ! $this->isRepository($receipt['package_source']['repository'] ?? null)
                || ! is_string($receipt['package_source']['path'] ?? null)
                || ! $this->isCommit($receipt['package_source']['tree'] ?? null)
                || ! $this->isRepository($receipt['framework_source']['repository'] ?? null)
                || ! $this->isCommit($receipt['framework_source']['revision'] ?? null)
                || ! $this->isRepository($receipt['builder']['repository'] ?? null)
                || ! $this->isCommit($receipt['builder']['revision'] ?? null)
                || ! $this->isSha256($receipt['manifest_sha256'] ?? null)
                || ! $this->isSha256($receipt['archive_sha256'] ?? null)
                || ($package['mount'] ?? null) !== '_docara/framework-packages/' . $name . '/' . $revision
                || ! is_array($package['files'] ?? null)
                || ! isset($package['files']['smart/rule.js'])
                || array_is_list($package['files'])
            ) {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_PROJECTION_INVALID', (string) $name);
            }
            foreach ($package['files'] as $relativePath => $record) {
                if (! is_string($relativePath)
                    || ! $this->isSafeRelativePath($relativePath)
                    || ! str_starts_with($relativePath, 'smart/')
                    || ! is_array($record)
                    || array_keys($record) !== ['sha256']
                    || ! $this->isSha256($record['sha256'] ?? null)
                ) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_PACKAGE_PROJECTION_FILE_INVALID',
                        $name . ':' . $relativePath,
                    );
                }
            }
        }
    }

    private function isRepository(mixed $value): bool
    {
        return is_string($value) && preg_match('~\A[a-z0-9-]+/[a-z0-9._-]+\z~D', $value) === 1;
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
