<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;

final class FrameworkTypographyProjectionTest extends TestCase
{
    #[Test]
    public function project_documentation_source_pointer_does_not_change_runtime_identity(): void
    {
        $path = dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json';
        $project = FrameworkLock::fromJsonFile($path)->toArray();
        $project['runtime']['framework_registry']['documentation_source'] = [
            'schema' => 'docara.documentation_source.v1',
            'relative_path' => 'contract/contracts/generated/documentation-source.json',
            'file_sha256' => str_repeat('a', 64),
        ];

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($project));

        self::assertSame('sf-v5.5.0-286e48b8-23d00d92', $repository->runtime()['pair_id']);
    }

    #[Test]
    public function exact_previous_framework_lock_is_admitted_without_editing_the_project_file(): void
    {
        $path = dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json';
        $current = FrameworkLock::fromJsonFile($path)->toArray();
        $previous = $current;
        $previous['runtime']['pair_id'] = 'sf-v5.4.1-185ca062-23d00d92';
        $previous['runtime']['bundle_id'] = 'sf-v5.4.1-185ca062-23d00d92-registry-983204b9-verified-release-artifact-v1';
        $previous['runtime']['tag'] = 'v5.4.1';
        $previous['runtime']['ui']['tag'] = 'v5.4.1';
        $previous['runtime']['ui']['commit'] = '185ca0620df6b46b9e2c9c92231a46c9b79a786b';
        $previous['runtime']['ui']['sha256'] = '1c57b3d20e1acf0c9145caef6e796aebea3506b4fc8df620380f81e3d22cce8c';
        $previous['runtime']['ui']['files'] = 6771;
        $previous['runtime']['framework_registry']['compatibility_id'] = 'sf-v5.4.1-185ca062-23d00d92';
        $previous['runtime']['framework_registry']['file_sha256'] = '983204b912635a2ee44d72d6837cffd4212afaa455dbee8da396ef2de168851a';
        $previous['runtime']['framework_registry']['source']['commit'] = '185ca0620df6b46b9e2c9c92231a46c9b79a786b';
        $previous['runtime']['framework_registry']['source']['tree_oid'] = '7dedb026e01df5053a8e59e1d86b2d5ee11bc0b7';
        $previous['runtime']['framework_registry']['source']['sha256'] = '41ffa37c2451e66d8ccc9536c0462aff20fbb905aa764047433e8fed5c5cd6a1';
        $previous['runtime_projection']['mount'] = '_docara/vendor/simai-framework/runtime/185ca0620df6b46b9e2c9c92231a46c9b79a786b/distr';
        $previous['runtime_projection']['source']['revision'] = '185ca0620df6b46b9e2c9c92231a46c9b79a786b';
        $previous['runtime_projection']['source']['tree_sha256'] = '1c57b3d20e1acf0c9145caef6e796aebea3506b4fc8df620380f81e3d22cce8c';
        $previous['runtime_projection']['packet_sha256'] = 'e6c8c03005b94497c02691ec9a49b23dd91421a0ed5816978a8b423636cf1fba';
        $previous['runtime_projection']['files'] = 826;
        $previous['runtime_projection']['manifest']['path'] = 'portable/vendor/simai-framework/runtime/185ca0620df6b46b9e2c9c92231a46c9b79a786b/runtime-manifest.json';
        $previous['runtime_projection']['manifest']['public'] = '_docara/vendor/simai-framework/runtime/185ca0620df6b46b9e2c9c92231a46c9b79a786b/runtime-manifest.json';
        $previous['runtime_projection']['manifest']['sha256'] = '91b1fdad622c6c107a2f49a396f04bf8d4549e486e7df3edbc0b3eeaa16cf6b8';

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
        self::assertSame('sf-v5.5.0-286e48b8-23d00d92', $repository->runtime()['pair_id']);
        self::assertSame(840, $repository->runtimeProjection()['files']);
        self::assertSame('sf-v5.4.1-185ca062-23d00d92', $previous['runtime']['pair_id']);

        $previous['runtime']['ui']['files'] = 6770;
        $this->expectException(FrameworkComponentException::class);
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
    }

    #[Test]
    public function known_same_runtime_projection_is_upgraded_without_editing_the_project_lock(): void
    {
        $path = dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json';
        $current = FrameworkLock::fromJsonFile($path)->toArray();
        $legacy = $current;
        $legacy['runtime_projection']['packet_sha256'] = '790b8014c4c1a0853e6a0650f30e0b4f33ab3b428f878b0fa010faf0c3f449c0';
        $legacy['runtime_projection']['files'] = 117;
        $legacy['runtime_projection']['manifest']['sha256'] = '8c917f69a678df084260ded24c5e39e78aaa4fc12c317bf98afaf11ee2a29a8e';

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($legacy));
        self::assertSame(840, $repository->runtimeProjection()['files']);
        self::assertArrayHasKey('rule/rule.json', $repository->runtimeManifest()['files']);
        self::assertSame(117, $legacy['runtime_projection']['files']);

        $legacy['runtime_projection']['packet_sha256'] = str_repeat('f', 64);
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_RUNTIME_MANIFEST_HASH_MISMATCH');
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($legacy));
    }

    #[Test]
    public function known_typography_projection_is_upgraded_to_metric_fallback_without_editing_project_lock(): void
    {
        $path = dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json';
        $current = FrameworkLock::fromJsonFile($path)->toArray();
        $legacy = $current;
        $legacy['typography_projection']['packet_sha256'] = 'd20a0ce7d97bbb3e9502236fa3cb73acd7ca3d74b2559a3120ea3496a4c98dad';
        $legacy['typography_projection']['files']['core']['sha256'] = '9c235fbdd02246def279e710bd92ee3c6fed4c3dcdcc859f0ebf9ab73afb20af';

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($legacy));
        $projection = $repository->typographyProjection();
        self::assertSame(
            $current['typography_projection']['packet_sha256'],
            $projection['packet_sha256'],
        );
        self::assertSame(
            $current['typography_projection']['files']['core']['sha256'],
            $projection['files']['core']['sha256'],
        );
        self::assertStringContainsString(
            'font-family: "Inter Fallback"',
            $repository->bundledTypographyAsset('core'),
        );
        self::assertSame(
            'd20a0ce7d97bbb3e9502236fa3cb73acd7ca3d74b2559a3120ea3496a4c98dad',
            $legacy['typography_projection']['packet_sha256'],
        );

        $legacy['typography_projection']['files']['core']['sha256'] = str_repeat('f', 64);
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_TYPOGRAPHY_ASSET_HASH_MISMATCH');
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($legacy));
    }

    #[Test]
    public function exact_projections_publish_typography_and_framework_runtime_locally(): void
    {
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json');
        $repository = FrameworkManifestRepository::bundled($lock);
        $projection = $repository->typographyProjection();

        self::assertIsArray($projection);
        self::assertSame('5.4.0', $projection['candidate']);
        self::assertSame('c94a214fb727f0468863d10a94d4388e0f111852', $projection['source']['revision']);
        self::assertSame('367b3423f9707b850c6bef9476ab8d1ed44039e1', $projection['builder']['revision']);
        self::assertSame('b2e8444659ae0d213296c2d349257259d3ed0c9c', $projection['distribution']['revision']);
        self::assertTrue($projection['distribution']['published']);

        self::assertCount(10, $projection['files']);
        foreach (array_keys($projection['files']) as $key) {
            self::assertSame(
                $projection['files'][$key]['sha256'],
                hash('sha256', $repository->bundledTypographyAsset($key)),
            );
        }

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework'))->plan([]);
        $assets = array_column($plan->assets, null, 'key');
        self::assertCount(1, $plan->generatedAssets);
        $shell = $plan->generatedAssets[0];
        self::assertMatchesRegularExpression('/^docara-shell\.[a-f0-9]{64}\.css$/D', $shell['filename']);
        self::assertSame('/_docara/' . $shell['filename'], $shell['url']);
        self::assertSame($shell['sha256'], hash('sha256', $shell['content']));
        self::assertStringContainsString('docara-shell-source: portable/declarative-shell.css', $shell['content']);
        self::assertSame('static_shell', $plan->preload['mode']);
        self::assertContains('cl-buttons', $plan->preload['modules']);
        self::assertContains('cl-icons', $plan->preload['modules']);
        self::assertContains('cl-modal', $plan->preload['modules']);
        self::assertStringContainsString('window.SF_PRELOADED=', $plan->headHtml());
        $assetKeys = array_column($plan->assets, 'key');
        self::assertLessThan(
            array_search('simai.framework.preloaded.component.buttons.js', $assetKeys, true),
            array_search('simai.framework.smart_base.js', $assetKeys, true),
        );
        self::assertLessThan(
            array_search('simai.framework.preloaded.sf_button.js', $assetKeys, true),
            array_search('simai.framework.preloaded.component.buttons.js', $assetKeys, true),
        );
        self::assertLessThan(
            array_search('simai.framework.core.js', $assetKeys, true),
            array_search('simai.framework.preloaded.sf_modal.js', $assetKeys, true),
        );
        foreach (['simai.framework.core.css' => 'core', 'simai.framework.utility.full.css' => 'utility'] as $assetKey => $fileKey) {
            self::assertStringStartsWith('/' . $projection['files'][$fileKey]['public'] . '?sf_v=', $assets[$assetKey]['url']);
            self::assertSame($projection['files'][$fileKey]['sha256'], $assets[$assetKey]['sha256']);
            self::assertSame($projection['distribution']['revision'], $assets[$assetKey]['source_revision']);
        }
        $runtime = $repository->runtimeProjection();
        self::assertIsArray($runtime);
        self::assertSame(840, $runtime['files']);
        $runtimeFiles = $repository->runtimeManifest()['files'];
        self::assertCount(840, $runtimeFiles);
        self::assertArrayHasKey('rule/rule.json', $runtimeFiles);
        self::assertArrayHasKey('utility/theme/default/css/default.css', $runtimeFiles);
        self::assertArrayHasKey('component/highlight/js/156256801485311.js', $runtimeFiles);
        self::assertArrayHasKey('component/highlight/js/22635021162243.js', $runtimeFiles);
        self::assertArrayHasKey('component/icons/fonts/MaterialSymbols-Outlined.woff2', $runtimeFiles);
        foreach (array_keys($runtimeFiles) as $relativePath) {
            self::assertFalse(str_ends_with($relativePath, '.gz'), $relativePath);
            self::assertStringNotContainsString('.min.', $relativePath);
        }
        self::assertSame(
            '42228b12e7cf63930d3872977b0e3b8fb64d12ddb6261cb5574df6b1bb97b80c',
            $runtime['packet_sha256'],
        );
        $coreLoader = $repository->bundledRuntimeAsset('core/js/core-loader.js');
        self::assertStringNotContainsString('@latest', $coreLoader);
        self::assertStringContainsString('component/icons/fonts/${file}', $coreLoader);
        self::assertStringContainsString('/_docara/vendor/simai-framework/runtime/', $assets['simai.framework.boot']['content']);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $assets['simai.framework.boot']['content']);
        foreach (['simai.framework.smart_base.js', 'simai.framework.core.js'] as $assetKey) {
            self::assertStringStartsWith('/_docara/vendor/simai-framework/runtime/', $assets[$assetKey]['url']);
            self::assertSame('286e48b8ce2b8e765eb5794d74b711f5b8f78783', $assets[$assetKey]['source_revision']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $assets[$assetKey]['sha256']);
        }
        self::assertStringContainsString(
            '/_docara/vendor/docara/icon-subset/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/material-symbols-outlined.995fbf08c43fe8ae9c3b.woff2',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Outlined Subset 995fbf08c43f"',
            $assets['simai.framework.icon_font.css']['content'],
        );
        $icons = $repository->iconProjection();
        self::assertIsArray($icons);
        self::assertSame('google/material-design-icons', $icons['source']['provider']);
        self::assertSame('50f0603134ce7b70b2d71b686cc13e8b57ccb74c', $icons['source']['revision']);
        self::assertSame('Apache-2.0', $icons['source']['license']);
        self::assertSame('040d5a4c9fb0893e0333bd5d5020cc98929e21dad28f68273c8e306102213f5c', $icons['packet_sha256']);
        foreach (['license', 'outlined', 'rounded', 'sharp'] as $key) {
            self::assertSame($icons['files'][$key]['sha256'], hash('sha256', $repository->bundledIconAsset($key)));
        }
        self::assertSame(
            '5c0be48d07803e6eb6a993ad441f6fc92340ee0da9d1b57cc348f62569947ae5',
            $icons['files']['outlined']['sha256'],
        );
        self::assertStringContainsString(
            '/_docara/vendor/docara/icon-subset/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/material-symbols-outlined.995fbf08c43fe8ae9c3b.woff2',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertSame(244368, $plan->preload['icons']['font_size']);
        self::assertSame('local_full_font_on_unknown_icon', $plan->preload['icons']['fallback']);
        self::assertStringContainsString('ensureFullFont()', $assets['simai.framework.icon_font.ready']['content']);
        self::assertStringNotContainsString('@latest', $plan->headHtml());
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Rounded"',
            $assets['simai.framework.icon_variant_fonts.css']['content'],
        );
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Sharp"',
            $assets['simai.framework.icon_variant_fonts.css']['content'],
        );
        self::assertStringContainsString(
            '/_docara/vendor/google/material-symbols/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/MaterialSymbolsRounded.woff2',
            $assets['simai.framework.icon_variant_fonts.css']['content'],
        );
        self::assertStringContainsString(
            'html body .sf-icon.sf-icon-rounded',
            $assets['simai.framework.icon_variant_fonts.css']['content'],
        );
        self::assertStringContainsString(
            'html body .sf-icon.sf-icon-shape',
            $assets['simai.framework.icon_variant_fonts.css']['content'],
        );
        self::assertStringContainsString(
            'html body .sf-icon:not(.sf-icon-rounded):not(.sf-icon-shape)',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertStringNotContainsString(
            'html body sf-icon > .sf-icon',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertStringContainsString(
            '{rounded:"Material Symbols Rounded",shape:"Material Symbols Sharp"}',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString('function family(icon)', $assets['simai.framework.icon_font.ready']['content']);
        self::assertStringNotContainsString(
            '["Material Symbols Outlined","Material Symbols Rounded","Material Symbols Sharp"].map',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        foreach ($plan->assets as $asset) {
            self::assertStringNotContainsString('cdn.jsdelivr.net', (string) ($asset['url'] ?? '') . (string) ($asset['content'] ?? ''));
        }

        $productionPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework'))->planForHtml(
            '<main class="flex p-1"><sf-button>Save</sf-button></main>',
            [],
        );
        self::assertSame('production_exact', $productionPlan->preload['mode']);
        self::assertSame('simai.framework.asset_plan.v1', $productionPlan->preload['schema']);
        self::assertContains('display/default', $productionPlan->preload['modules']);
        self::assertContains('padding/default', $productionPlan->preload['modules']);
        self::assertContains('cl-buttons', $productionPlan->preload['modules']);
        self::assertContains('pointer-events/default', $productionPlan->preload['modules']);
        self::assertContains('text-align/default', $productionPlan->preload['modules']);
        self::assertNotContains('simai.framework.utility.full.css', array_column($productionPlan->assets, 'key'));
        self::assertStringContainsString(
            'docara-shell-source: runtime/utility/display/default/css/default.css',
            $productionPlan->generatedAssets[0]['content'],
        );

        $bodyPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework'))->planForHtml(
            '<!doctype html><html><body class="max-container-7"><main>Content</main></body></html>',
            [],
        );
        self::assertContains('max-container/default', $bodyPlan->preload['modules']);

        $nested = (new FrameworkAssetPlanner($repository, '/project~/docs/_docara/framework'))->plan([]);
        $nestedAssets = array_column($nested->assets, null, 'key');
        self::assertStringStartsWith(
            '/project~/docs/_docara/vendor/simai-framework/typography/5.4.0/core.css?sf_v=',
            $nestedAssets['simai.framework.core.css']['url'],
        );
        self::assertStringStartsWith(
            '/project~/docs/_docara/vendor/simai-framework/runtime/286e48b8ce2b8e765eb5794d74b711f5b8f78783/distr/core/js/core.js?sf_v=',
            $nestedAssets['simai.framework.core.js']['url'],
        );
    }

    #[Test]
    public function changed_projected_bytes_fail_before_render(): void
    {
        [$root, $lock] = $this->fixture();
        file_put_contents($root . '/resources/portable/vendor/simai-framework/typography/5.4.0/core.css', 'changed');

        try {
            new FrameworkManifestRepository($lock, $root . '/resources/framework');
            self::fail('Changed typography bytes were admitted.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_TYPOGRAPHY_ASSET_HASH_MISMATCH', $exception->errorCode);
        } finally {
            $this->removeFixture($root);
        }
    }

    #[Test]
    public function symlink_and_hardlink_projected_assets_fail_closed(): void
    {
        foreach (['symlink', 'hardlink'] as $attack) {
            [$root, $lock] = $this->fixture();
            $core = $root . '/resources/portable/vendor/simai-framework/typography/5.4.0/core.css';
            $outside = $root . '/outside.css';
            file_put_contents($outside, file_get_contents($core));
            unlink($core);
            $attack === 'symlink' ? symlink($outside, $core) : link($outside, $core);

            try {
                new FrameworkManifestRepository($lock, $root . '/resources/framework');
                self::fail(ucfirst($attack) . ' typography bytes were admitted.');
            } catch (FrameworkComponentException $exception) {
                self::assertSame('FRAMEWORK_TYPOGRAPHY_ASSET_UNSAFE', $exception->errorCode);
            } finally {
                $this->removeFixture($root);
            }
        }
    }

    #[Test]
    public function changed_runtime_bytes_fail_before_render(): void
    {
        [$root, $lock] = $this->fixture();
        $core = $root . '/resources/portable/vendor/simai-framework/runtime/'
            . '286e48b8ce2b8e765eb5794d74b711f5b8f78783/distr/core/js/core.js';
        file_put_contents($core, 'changed');

        try {
            new FrameworkManifestRepository($lock, $root . '/resources/framework');
            self::fail('Changed runtime bytes were admitted.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_RUNTIME_ASSET_HASH_MISMATCH', $exception->errorCode);
        } finally {
            $this->removeFixture($root);
        }
    }

    #[Test]
    public function changed_icon_variant_bytes_fail_before_render(): void
    {
        [$root, $lock] = $this->fixture();
        $rounded = $root . '/resources/portable/vendor/google/material-symbols/'
            . '50f0603134ce7b70b2d71b686cc13e8b57ccb74c/MaterialSymbolsRounded.woff2';
        file_put_contents($rounded, 'changed');

        try {
            new FrameworkManifestRepository($lock, $root . '/resources/framework');
            self::fail('Changed icon variant bytes were admitted.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_ICON_ASSET_HASH_MISMATCH', $exception->errorCode);
        } finally {
            $this->removeFixture($root);
        }
    }

    #[Test]
    public function changed_shell_icon_subset_files_fail_before_render(): void
    {
        $cases = [
            'material-symbols-outlined.995fbf08c43fe8ae9c3b.manifest.json' => 'FRAMEWORK_ICON_SUBSET_MANIFEST_HASH_MISMATCH',
            'material-symbols-outlined.995fbf08c43fe8ae9c3b.css' => 'FRAMEWORK_ICON_SUBSET_CSS_HASH_MISMATCH',
            'material-symbols-outlined.995fbf08c43fe8ae9c3b.woff2' => 'FRAMEWORK_PORTABLE_ASSET_HASH_MISMATCH',
        ];

        foreach ($cases as $file => $expectedCode) {
            [$root, $lock] = $this->fixture();
            $subset = $root . '/resources/portable/vendor/docara/icon-subset/'
                . '50f0603134ce7b70b2d71b686cc13e8b57ccb74c/' . $file;
            file_put_contents($subset, 'changed');

            try {
                (new FrameworkAssetPlanner(
                    new FrameworkManifestRepository($lock, $root . '/resources/framework'),
                    '/_docara/framework',
                ))->plan([]);
                self::fail('Changed shell icon subset file was admitted: ' . $file);
            } catch (FrameworkComponentException $exception) {
                self::assertSame($expectedCode, $exception->errorCode);
            } finally {
                $this->removeFixture($root);
            }
        }
    }

    #[Test]
    public function symlink_and_hardlink_runtime_assets_fail_closed(): void
    {
        foreach (['symlink', 'hardlink'] as $attack) {
            [$root, $lock] = $this->fixture();
            $core = $root . '/resources/portable/vendor/simai-framework/runtime/'
                . '286e48b8ce2b8e765eb5794d74b711f5b8f78783/distr/core/js/core.js';
            $outside = $root . '/outside.js';
            file_put_contents($outside, file_get_contents($core));
            unlink($core);
            $attack === 'symlink' ? symlink($outside, $core) : link($outside, $core);

            try {
                new FrameworkManifestRepository($lock, $root . '/resources/framework');
                self::fail(ucfirst($attack) . ' runtime bytes were admitted.');
            } catch (FrameworkComponentException $exception) {
                self::assertSame('FRAMEWORK_RUNTIME_ASSET_UNSAFE', $exception->errorCode);
            } finally {
                $this->removeFixture($root);
            }
        }
    }

    #[Test]
    public function older_locks_without_loader_metadata_use_the_package_owned_contract(): void
    {
        [$root] = $this->fixture();
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        foreach (FrameworkAssetPlanner::DOCARA_SHELL_RUNTIME_TAGS as $tag) {
            unset($lock['runtime']['components'][$tag]['loader']);
        }

        try {
            $plan = (new FrameworkAssetPlanner(
                new FrameworkManifestRepository(FrameworkLock::fromArray($lock), $root . '/resources/framework'),
                '/_docara/framework',
            ))->plan([]);
            self::assertSame('static_shell', $plan->preload['mode']);
            self::assertSame([], $plan->diagnostics);
            self::assertStringContainsString('window.SF_PRELOADED=', $plan->headHtml());
            self::assertArrayNotHasKey('simai.framework.sf_icon.js', array_column($plan->assets, null, 'key'));
            self::assertCount(1, $plan->generatedAssets);
        } finally {
            $this->removeFixture($root);
        }
    }

    #[Test]
    public function runtimes_without_any_shell_metadata_keep_the_dynamic_fallback(): void
    {
        [$root] = $this->fixture();
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        unset($lock['runtime']['shell']);
        foreach (FrameworkAssetPlanner::DOCARA_SHELL_RUNTIME_TAGS as $tag) {
            unset($lock['runtime']['components'][$tag]['loader']);
        }
        $bundledRuntime = $lock['runtime'];
        file_put_contents(
            $root . '/resources/framework/runtime-lock.json',
            json_encode($bundledRuntime, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        );

        try {
            $plan = (new FrameworkAssetPlanner(
                new FrameworkManifestRepository(FrameworkLock::fromArray($lock), $root . '/resources/framework'),
                '/_docara/framework',
            ))->plan([]);
            self::assertSame('dynamic_fallback', $plan->preload['mode']);
            self::assertSame('FRAMEWORK_SHELL_PRELOAD_METADATA_MISSING', $plan->diagnostics[0]['code']);
            self::assertStringNotContainsString('window.SF_PRELOADED=', $plan->headHtml());
        } finally {
            $this->removeFixture($root);
        }
    }

    #[Test]
    public function changed_loader_metadata_and_unsafe_shell_sources_fail_closed(): void
    {
        [$root] = $this->fixture();
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $lock['runtime']['components']['sf-icon']['loader']['plugin'] = 'cl-forged';
        try {
            new FrameworkManifestRepository(FrameworkLock::fromArray($lock), $root . '/resources/framework');
            self::fail('Changed loader metadata was admitted.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_RUNTIME_PROJECTION_MISMATCH', $exception->errorCode);
        } finally {
            $this->removeFixture($root);
        }

        foreach (['symlink', 'hardlink'] as $attack) {
            [$root, $exactLock] = $this->fixture();
            $shell = $root . '/resources/portable/declarative-shell.css';
            $outside = $root . '/outside-shell.css';
            file_put_contents($outside, file_get_contents($shell));
            unlink($shell);
            $attack === 'symlink' ? symlink($outside, $shell) : link($outside, $shell);
            try {
                (new FrameworkAssetPlanner(
                    new FrameworkManifestRepository($exactLock, $root . '/resources/framework'),
                    '/_docara/framework',
                ))->plan([]);
                self::fail(ucfirst($attack) . ' shell CSS was admitted.');
            } catch (FrameworkComponentException $exception) {
                self::assertSame('FRAMEWORK_PORTABLE_ASSET_UNSAFE', $exception->errorCode);
            } finally {
                $this->removeFixture($root);
            }
        }
    }

    /** @return array{string, FrameworkLock} */
    private function fixture(): array
    {
        $root = sys_get_temp_dir() . '/docara-typography-' . bin2hex(random_bytes(8));
        $resources = $root . '/resources';
        mkdir($resources . '/framework', 0777, true);
        mkdir($resources . '/portable/vendor/simai-framework/typography/5.4.0', 0777, true);
        copy(
            dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css',
            $resources . '/portable/declarative-shell.css',
        );
        $subsetRoot = 'portable/vendor/docara/icon-subset';
        foreach (glob(dirname(__DIR__, 2) . '/resources/' . $subsetRoot . '/*/*') ?: [] as $source) {
            $relative = substr($source, strlen(dirname(__DIR__, 2) . '/resources/'));
            $target = $resources . '/' . $relative;
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }
        copy(dirname(__DIR__, 2) . '/resources/framework/runtime-lock.json', $resources . '/framework/runtime-lock.json');
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json');
        foreach ($lock->typographyProjection()['files'] as $record) {
            $source = dirname(__DIR__, 2) . '/resources/' . $record['path'];
            $target = $resources . '/' . $record['path'];
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }
        $runtime = $lock->runtimeProjection();
        $manifestSource = dirname(__DIR__, 2) . '/resources/' . $runtime['manifest']['path'];
        $manifestTarget = $resources . '/' . $runtime['manifest']['path'];
        if (! is_dir(dirname($manifestTarget))) {
            mkdir(dirname($manifestTarget), 0777, true);
        }
        copy($manifestSource, $manifestTarget);
        $manifest = json_decode((string) file_get_contents($manifestSource), true, 512, JSON_THROW_ON_ERROR);
        foreach (array_keys($manifest['files']) as $relativePath) {
            $relative = 'portable/vendor/simai-framework/runtime/'
                . $runtime['source']['revision'] . '/distr/' . $relativePath;
            $source = dirname(__DIR__, 2) . '/resources/' . $relative;
            $target = $resources . '/' . $relative;
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }
        foreach ($lock->iconProjection()['files'] as $record) {
            $source = dirname(__DIR__, 2) . '/resources/' . $record['path'];
            $target = $resources . '/' . $record['path'];
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }
        foreach (array_keys($lock->assetProjection()['files']) as $relativePath) {
            $source = dirname(__DIR__, 2) . '/resources/framework/assets/' . $relativePath;
            $target = $resources . '/framework/assets/' . $relativePath;
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }

        return [$root, $lock];
    }

    private function removeFixture(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $item->isDir() && ! $item->isLink() ? rmdir($path) : unlink($path);
        }
        rmdir($root);
    }
}
