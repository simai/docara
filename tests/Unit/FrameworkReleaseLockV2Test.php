<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;

/**
 * Release lock v2: a Framework release is one `ui` commit whose distribution
 * carries Smart in `distr/smart`, named `ui-<12>`. A product component package
 * (taken by its package receipt) is served beside it under its own mount and
 * declared in `SF_BOOT_CONFIG.packages` before Core starts. Locks of the v1
 * shape (a separate simai/ui-smart revision) keep working.
 */
final class FrameworkReleaseLockV2Test extends TestCase
{
    private const PACKAGE_REVISION = '98ad1a012a1750300a05cecef67ed2cbb91aa462';

    private const RULE_FILE = "SF.RuleLoader.registerPackage(\"larena\", {rules: {}});\n";

    private ?string $temporaryRoot = null;

    protected function tearDown(): void
    {
        if ($this->temporaryRoot !== null && is_dir($this->temporaryRoot)) {
            exec('rm -rf ' . escapeshellarg($this->temporaryRoot));
        }
        $this->temporaryRoot = null;
        parent::tearDown();
    }

    #[Test]
    public function a_v1_lock_still_reads_smart_from_the_separate_repository(): void
    {
        $lock = FrameworkLock::fromArray($this->v1Lock($this->bundledLock()));

        self::assertSame('simai/ui-smart', $lock->smartSource()['provider']);
        self::assertSame(1, $lock->smartSource()['release_lock_version']);
        self::assertSame([], $lock->packageProjections());
    }

    #[Test]
    public function a_v2_lock_reads_smart_from_the_ui_release_at_the_core_revision(): void
    {
        $data = $this->v2Lock($this->bundledLock());
        $lock = FrameworkLock::fromArray($data);
        $uiCommit = $data['runtime']['ui']['commit'];

        self::assertSame([
            'provider' => 'simai/ui',
            'revision' => $uiCommit,
            'release_lock_version' => 2,
        ], $lock->smartSource());
        self::assertSame('ui-' . substr($uiCommit, 0, 12), $lock->pairId());
    }

    #[Test]
    public function a_v2_lock_that_names_smart_apart_from_core_is_refused(): void
    {
        $cases = [
            'smart at another commit' => static function (array $lock): array {
                $lock['runtime']['ui_smart']['commit'] = str_repeat('b', 40);
                $lock['asset_projection']['source']['revision'] = str_repeat('b', 40);
                $lock['dynamic_asset_projection']['source']['revision'] = str_repeat('b', 40);

                return $lock;
            },
            'a v1 pair id' => static function (array $lock): array {
                $lock['runtime']['pair_id'] .= '-smart-' . str_repeat('c', 12);

                return $lock;
            },
            'a smart mount of its own' => static function (array $lock): array {
                $lock['runtime']['ui_smart']['mount'] = 'smart';

                return $lock;
            },
        ];
        foreach ($cases as $name => $mutate) {
            $this->assertRefused('FRAMEWORK_RUNTIME_LOCK_V2_INVALID', $mutate($this->v2Lock($this->bundledLock())), $name);
        }

        $lock = $this->v2Lock($this->bundledLock());
        $lock['asset_projection']['source']['provider'] = 'simai/ui-smart';
        $this->assertRefused('FRAMEWORK_ASSET_PROJECTION_INVALID', $lock, 'projection from the ui-smart repository');

        $lock = $this->v1Lock($this->bundledLock());
        $lock['runtime']['ui_smart']['mount'] = 'ui';
        $this->assertRefused('FRAMEWORK_RUNTIME_LOCK_INVALID', $lock, 'a v1 tree under the ui mount');
    }

    #[Test]
    public function a_package_projection_is_accepted_beside_a_v2_release(): void
    {
        $data = $this->v2Lock($this->bundledLock());
        $data['package_projections'] = ['larena' => $this->packageProjection($data['runtime']['pair_id'])];
        $lock = FrameworkLock::fromArray($data);

        self::assertSame(['larena'], array_keys($lock->packageProjections()));
        self::assertSame(
            self::PACKAGE_REVISION,
            $lock->packageProjections()['larena']['receipt']['package_source']['revision'],
        );
    }

    #[Test]
    public function a_package_projection_that_does_not_fit_its_release_is_refused(): void
    {
        $v1 = $this->v1Lock($this->bundledLock());
        $v1['package_projections'] = ['larena' => $this->packageProjection($v1['runtime']['pair_id'])];
        $this->assertRefused('FRAMEWORK_PACKAGE_PROJECTIONS_INVALID', $v1, 'a package beside a v1 pair');

        $cases = [
            'built against another release' => static fn (array $package): array => ['requires' => 'ui-' . str_repeat('d', 12)] + $package,
            'served at a shared mount' => static fn (array $package): array => ['mount' => '_docara/framework-runtime'] + $package,
            'a mount of another revision' => static fn (array $package): array => ['mount' => '_docara/framework-packages/larena/' . str_repeat('e', 40)] + $package,
            'another package name' => static fn (array $package): array => ['package' => 'docara'] + $package,
            'no receipt identity' => static function (array $package): array {
                unset($package['receipt']['archive_sha256']);

                return $package;
            },
            'no rule file' => static function (array $package): array {
                unset($package['files']['smart/rule.js']);

                return $package;
            },
        ];
        foreach ($cases as $name => $mutate) {
            $lock = $this->v2Lock($this->bundledLock());
            $lock['package_projections'] = ['larena' => $mutate($this->packageProjection($lock['runtime']['pair_id']))];
            $this->assertRefused('FRAMEWORK_PACKAGE_PROJECTION_INVALID', $lock, $name);
        }

        $lock = $this->v2Lock($this->bundledLock());
        $package = $this->packageProjection($lock['runtime']['pair_id']);
        $package['files']['table/js/table.js'] = ['sha256' => str_repeat('1', 64)];
        $lock['package_projections'] = ['larena' => $package];
        $this->assertRefused('FRAMEWORK_PACKAGE_PROJECTION_FILE_INVALID', $lock, 'a file outside smart/');
    }

    #[Test]
    public function a_package_release_lock_projection_of_docara_2_16_is_no_longer_read(): void
    {
        $lock = $this->v2Lock($this->bundledLock());
        $lock['package_projections'] = ['pro' => [
            'schema' => 'docara.framework_package_projection.v1',
            'package' => 'pro',
            'release' => 'pro-' . substr(self::PACKAGE_REVISION, 0, 12),
            'requires' => $lock['runtime']['pair_id'],
            'mount' => '_docara/framework-packages/pro/' . self::PACKAGE_REVISION,
            'source' => [
                'provider' => 'simai/ui-pro',
                'revision' => self::PACKAGE_REVISION,
                'archive_sha256' => str_repeat('f', 64),
            ],
            'files' => ['smart/rule.js' => ['sha256' => str_repeat('a', 64)]],
        ]];
        $this->assertRefused('FRAMEWORK_PACKAGE_PROJECTION_UNSUPPORTED', $lock, 'a SIMAI Framework Pro release lock projection');
    }

    #[Test]
    public function the_planner_declares_the_package_before_core_and_serves_it_from_its_own_mount(): void
    {
        [$repository, $lock] = $this->v2Repository(withPackage: true);
        $uiCommit = (string) $lock['runtime']['ui']['commit'];
        $pairId = (string) $lock['runtime']['pair_id'];

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->plan([]);
        $assets = array_column($plan->assets, null, 'key');
        $boot = (string) $assets['simai.framework.boot']['content'];

        self::assertStringContainsString(
            '"packages":{"larena":{"base":"/_docara/framework-packages/larena/' . self::PACKAGE_REVISION . '/smart"}}',
            $boot,
        );
        self::assertStringContainsString('"pluginListVersion":"' . $pairId . '"', $boot);
        // The site serves the readable runtime files; the Loader is told not to
        // fetch their .min twins, which the projection does not publish.
        self::assertStringContainsString('"minified":false', $boot);
        // Docara serves Smart from its own projection, so it still names that
        // root; the lock no longer names a separate Smart repository.
        self::assertStringContainsString('window.sfSmartPath="/_docara/framework-runtime"', $boot);
        $keys = array_keys($assets);
        self::assertLessThan(
            array_search('simai.framework.core.js', $keys, true),
            array_search('simai.framework.boot', $keys, true),
        );
        foreach ($plan->assets as $asset) {
            if (($asset['kind'] ?? null) === 'smart_javascript' || ($asset['kind'] ?? null) === 'preloaded_smart_javascript') {
                self::assertSame($uiCommit, $asset['source_revision'], (string) $asset['key']);
                self::assertStringContainsString('?sf_v=' . $pairId . '-', (string) $asset['url']);
            }
        }

        self::assertSame(
            self::RULE_FILE,
            $repository->bundledPackageAsset('larena', 'smart/rule.js'),
        );
    }

    #[Test]
    public function a_v2_release_without_a_package_declares_none(): void
    {
        [$repository] = $this->v2Repository(withPackage: false);

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->plan([]);
        $boot = (string) array_column($plan->assets, null, 'key')['simai.framework.boot']['content'];

        self::assertStringNotContainsString('"packages"', $boot);
    }

    #[Test]
    public function package_bytes_that_disagree_with_the_lock_are_refused(): void
    {
        [$repository, , $resources] = $this->v2Repository(withPackage: true);
        file_put_contents(
            $resources . '/framework/runtime-packages/larena/' . self::PACKAGE_REVISION . '/smart/rule.js',
            "/* changed */\n",
        );

        try {
            $repository->bundledPackageAsset('larena', 'smart/rule.js');
            self::fail('Changed package bytes were served.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_PACKAGE_ASSET_HASH_MISMATCH', $exception->errorCode);
        }
        try {
            $repository->bundledPackageAsset('larena', 'smart/table/js/table.js');
            self::fail('A file the lock does not name was served.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_PACKAGE_ASSET_NOT_PROJECTED', $exception->errorCode);
        }
    }

    /** @return array<string, mixed> */
    private function bundledLock(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * The bundled release relabelled as a release lock v1 pair: Smart from a
     * separate simai/ui-smart revision.
     *
     * @param  array<string, mixed>  $lock
     * @return array<string, mixed>
     */
    private function v1Lock(array $lock): array
    {
        $smartCommit = str_repeat('7', 40);
        $runtime = $lock['runtime'];
        $pairId = 'ui-' . substr($runtime['ui']['commit'], 0, 12) . '-smart-' . substr($smartCommit, 0, 12);
        $runtime['ui_smart'] = [
            'tag' => null,
            'commit' => $smartCommit,
            'tree' => 'smart',
            'mount' => 'smart',
            'sha256' => $runtime['ui_smart']['sha256'],
            'files' => $runtime['ui_smart']['files'],
        ];
        $runtime['bundle_id'] = $pairId . substr($runtime['bundle_id'], strlen($runtime['pair_id']));
        $runtime['pair_id'] = $pairId;
        $runtime['framework_registry']['compatibility_id'] = $pairId;
        $lock['runtime'] = $runtime;
        foreach (['asset_projection', 'dynamic_asset_projection'] as $projection) {
            $lock[$projection]['source'] = ['provider' => 'simai/ui-smart', 'revision' => $smartCommit];
        }

        return $lock;
    }

    /**
     * The bundled pair relabelled as a v2 release: the same bytes, with Smart
     * read from the ui commit's `distr/smart`.
     *
     * @param  array<string, mixed>  $lock
     * @return array<string, mixed>
     */
    private function v2Lock(array $lock): array
    {
        $lock['runtime'] = $this->v2Runtime($lock['runtime']);
        $uiCommit = $lock['runtime']['ui']['commit'];
        foreach (['asset_projection', 'dynamic_asset_projection'] as $projection) {
            $lock[$projection]['source'] = ['provider' => 'simai/ui', 'revision' => $uiCommit];
        }

        return $lock;
    }

    /**
     * @param  array<string, mixed>  $runtime
     * @return array<string, mixed>
     */
    private function v2Runtime(array $runtime): array
    {
        $uiCommit = $runtime['ui']['commit'];
        $pairId = 'ui-' . substr($uiCommit, 0, 12);
        $runtime['ui_smart'] = [
            'tag' => null,
            'commit' => $uiCommit,
            'tree' => 'distr/smart',
            'mount' => 'ui',
            'sha256' => $runtime['ui_smart']['sha256'],
            'files' => $runtime['ui_smart']['files'],
        ];
        $runtime['bundle_id'] = $pairId . substr($runtime['bundle_id'], strlen($runtime['pair_id']));
        $runtime['pair_id'] = $pairId;
        $runtime['framework_registry']['compatibility_id'] = $pairId;

        return $runtime;
    }

    /** @return array<string, mixed> */
    private function packageProjection(string $requires): array
    {
        return [
            'schema' => 'docara.framework_package_projection',
            'schema_version' => 2,
            'package' => 'larena',
            'requires' => $requires,
            'mount' => '_docara/framework-packages/larena/' . self::PACKAGE_REVISION,
            'receipt' => [
                'schema' => 'simai.framework.product-package-receipt',
                'schema_version' => 1,
                'package_source' => [
                    'repository' => 'simai/larena-ui',
                    'revision' => self::PACKAGE_REVISION,
                    'path' => 'components',
                    'tree' => str_repeat('3', 40),
                ],
                'framework_source' => ['repository' => 'simai/ui-source', 'revision' => str_repeat('2', 40)],
                'builder' => ['repository' => 'simai/ui-builder', 'revision' => str_repeat('4', 40)],
                'manifest_sha256' => str_repeat('e', 64),
                'archive_sha256' => str_repeat('f', 64),
            ],
            'files' => [
                'smart/rule.js' => ['sha256' => hash('sha256', self::RULE_FILE)],
            ],
        ];
    }

    /**
     * A copy of the package resources holding the bundled pair as a v2
     * release (and, when asked, a package), so the repository's own checks of
     * bundled bytes run unchanged.
     *
     * @return array{0: FrameworkManifestRepository, 1: array<string, mixed>, 2: string}
     */
    private function v2Repository(bool $withPackage): array
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/docara-lock-v2-' . bin2hex(random_bytes(6));
        $resources = $this->temporaryRoot . '/resources';
        mkdir($resources, 0755, true);
        $source = dirname(__DIR__, 2) . '/resources';
        foreach (['framework', 'portable'] as $directory) {
            exec('cp -R ' . escapeshellarg($source . '/' . $directory) . ' ' . escapeshellarg($resources . '/'), $output, $status);
            self::assertSame(0, $status);
        }

        $lock = $this->bundledLock();
        $smartCommit = (string) $lock['runtime']['ui_smart']['commit'];
        $lock = $this->v2Lock($lock);
        $uiCommit = (string) $lock['runtime']['ui']['commit'];
        rename(
            $resources . '/framework/runtime-smart/' . $smartCommit,
            $resources . '/framework/runtime-smart/' . $uiCommit,
        );

        $runtimeLockPath = $resources . '/framework/runtime-lock.json';
        $runtimeLock = json_decode((string) file_get_contents($runtimeLockPath), true, 512, JSON_THROW_ON_ERROR);
        $assetPlanner = $runtimeLock['asset_planner'];
        unset($runtimeLock['asset_planner']);
        $runtimeLock = $this->v2Runtime($runtimeLock);
        file_put_contents($runtimeLockPath, json_encode(
            ['schema' => $runtimeLock['schema'], 'asset_planner' => $assetPlanner] + $runtimeLock,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ) . "\n");

        foreach (['ui.alert' => 'ui-alert.json', 'ui.button' => 'ui-button.json'] as $key => $file) {
            $path = $resources . '/framework/manifests/' . $file;
            $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $manifest['provenance']['upstream_revision'] = $uiCommit;
            file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            $lock['manifests'][$key]['sha256'] = hash_file('sha256', $path);
        }

        if ($withPackage) {
            $lock['package_projections'] = ['larena' => $this->packageProjection($lock['runtime']['pair_id'])];
            $packageRoot = $resources . '/framework/runtime-packages/larena/' . self::PACKAGE_REVISION . '/smart';
            mkdir($packageRoot, 0755, true);
            file_put_contents($packageRoot . '/rule.js', self::RULE_FILE);
        }

        return [
            new FrameworkManifestRepository(FrameworkLock::fromArray($lock), $resources . '/framework'),
            $lock,
            $resources,
        ];
    }

    /** @param array<string, mixed> $lock */
    private function assertRefused(string $code, array $lock, string $case): void
    {
        try {
            FrameworkLock::fromArray($lock);
            self::fail('Accepted: ' . $case);
        } catch (FrameworkComponentException $exception) {
            self::assertSame($code, $exception->errorCode, $case);
        }
    }
}
