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

        self::assertSame('ui-c6f98eb4cb29-smart-bb8f3cc5c329', $repository->runtime()['pair_id']);
    }

    #[Test]
    public function exact_direct_previous_framework_lock_is_admitted_without_editing_the_project_file(): void
    {
        $previous = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-5.6.1.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
        self::assertSame('ui-c6f98eb4cb29-smart-bb8f3cc5c329', $repository->runtime()['pair_id']);
        self::assertSame(1379, $repository->runtimeProjection()['files']);
        self::assertSame('sf-v5.6.1-34f5ff45-23d00d92', $previous['runtime']['pair_id']);

        $previous['runtime']['ui']['files'] = 6772;
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
        self::assertSame(1379, $repository->runtimeProjection()['files']);
        self::assertArrayHasKey('rule/rule.json', $repository->runtimeManifest()['files']);
        self::assertSame(117, $legacy['runtime_projection']['files']);

        $legacy['runtime_projection']['packet_sha256'] = str_repeat('f', 64);
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_RUNTIME_MANIFEST_HASH_MISMATCH');
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($legacy));
    }

    #[Test]
    public function a_pinned_typography_packet_is_admitted_as_it_stands_and_fails_closed_on_changed_bytes(): void
    {
        // The package no longer declares a typography edition of its own: the
        // own site and the stub take their foundation from the runtime
        // projection. There is therefore nothing to upgrade a consumer lock to,
        // and a consumer that pins an edition the package still ships keeps
        // exactly what it pinned.
        // A lock from the superseded list is adopted wholesale and therefore
        // takes the package's arrangement, foundation included. The case here
        // is the other one: a current lock that pins an edition of its own, the
        // way larena-doc does.
        $pinned = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $consumer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-5.6.1.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $pinned['typography_projection'] = $consumer['typography_projection'];

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($pinned));
        $projection = $repository->typographyProjection();
        self::assertSame(
            $pinned['typography_projection']['packet_sha256'],
            $projection['packet_sha256'],
            'a pinned packet was rewritten',
        );
        self::assertSame(
            $pinned['typography_projection']['files']['core']['sha256'],
            $projection['files']['core']['sha256'],
        );
        self::assertStringContainsString(
            '--sf-breakpoint-xxl',
            $repository->bundledTypographyAsset('core'),
        );

        // The fail-closed rule is unchanged: a hash the bytes do not match is
        // refused before anything renders.
        $tampered = $pinned;
        $tampered['typography_projection']['files']['core']['sha256'] = str_repeat('f', 64);
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_TYPOGRAPHY_ASSET_HASH_MISMATCH');
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($tampered));
    }

    #[Test]
    public function exact_projections_publish_typography_and_framework_runtime_locally(): void
    {
        // The shape larena-doc still has: the own site's runtime projection with
        // a pinned typography packet on top. The own site itself no longer pins
        // one, so the packet comes from the consumer fixture.
        $own = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $consumer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-5.6.1.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $own['typography_projection'] = $consumer['typography_projection'];
        $lock = FrameworkLock::fromArray($own);
        $repository = FrameworkManifestRepository::bundled($lock);
        $projection = $repository->typographyProjection();

        self::assertIsArray($projection);
        $pinned = $consumer['typography_projection'];
        self::assertSame($pinned['candidate'], $projection['candidate']);
        self::assertSame($pinned['source']['revision'], $projection['source']['revision']);
        self::assertSame($pinned['builder']['revision'], $projection['builder']['revision']);
        self::assertSame($pinned['distribution']['revision'], $projection['distribution']['revision']);
        self::assertSame($pinned['distribution']['published'], $projection['distribution']['published']);

        self::assertCount(10, $projection['files']);
        foreach (array_keys($projection['files']) as $key) {
            self::assertSame(
                $projection['files'][$key]['sha256'],
                hash('sha256', $repository->bundledTypographyAsset($key)),
            );
        }

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->plan([]);
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
        self::assertSame(1379, $runtime['files']);
        $runtimeFiles = $repository->runtimeManifest()['files'];
        self::assertCount(1379, $runtimeFiles);
        self::assertArrayHasKey('rule/rule.json', $runtimeFiles);
        self::assertArrayHasKey('utility/theme/default/css/default.css', $runtimeFiles);
        self::assertArrayHasKey('component/highlight/js/highlight.js', $runtimeFiles);
        self::assertCount(1, array_filter(
            array_keys($runtimeFiles),
            static fn (string $path): bool => str_starts_with($path, 'component/highlight/js/'),
        ));
        self::assertArrayHasKey('component/icons/a5f1f832a64baed42867.woff2', $runtimeFiles);
        self::assertArrayHasKey('component/icons/fonts/MaterialSymbols-Outlined.woff2', $runtimeFiles);
        // Nothing loads the third-party notices, so the projection names them
        // explicitly; the commercially licensed component/icon is gone from the pair.
        self::assertArrayHasKey('core/contracts/third-party-notices.v1.json', $runtimeFiles);
        self::assertSame(
            'simai.framework.third-party-notices.v1',
            json_decode(
                $repository->bundledRuntimeAsset('core/contracts/third-party-notices.v1.json'),
                true,
                512,
                JSON_THROW_ON_ERROR,
            )['schema'],
        );
        self::assertSame([], array_values(array_filter(
            array_keys($runtimeFiles),
            static fn (string $path): bool => str_starts_with($path, 'component/icon/'),
        )));
        // sf-flag fetches its catalogue and SVGs from a URL it builds from
        // sfPath, so the projection names the directory: 494 SVGs and index.json.
        self::assertArrayHasKey('component/flag/flags/index.json', $runtimeFiles);
        self::assertCount(495, array_filter(
            array_keys($runtimeFiles),
            static fn (string $path): bool => str_starts_with($path, 'component/flag/flags/'),
        ));
        foreach (array_keys($runtimeFiles) as $relativePath) {
            self::assertFalse(str_ends_with($relativePath, '.gz'), $relativePath);
            self::assertStringNotContainsString('.min.', $relativePath);
        }
        self::assertSame(
            '92419a793ab56ebff25c7828d90ff2e29a1aa8e4a1ee99208359257176b86bb6',
            $runtime['packet_sha256'],
        );
        $coreLoader = $repository->bundledRuntimeAsset('core/js/core-loader.js');
        self::assertStringNotContainsString('@latest', $coreLoader);
        self::assertStringContainsString('component/icons/fonts/${file}', $coreLoader);
        self::assertStringContainsString('/_docara/vendor/simai-framework/runtime/', $assets['simai.framework.boot']['content']);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $assets['simai.framework.boot']['content']);
        foreach (['simai.framework.smart_base.js', 'simai.framework.core.js'] as $assetKey) {
            self::assertStringStartsWith('/_docara/vendor/simai-framework/runtime/', $assets[$assetKey]['url']);
            self::assertSame('c6f98eb4cb29bdc892d4df6ce31c014479eb37fb', $assets[$assetKey]['source_revision']);
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
        self::assertStringContainsString(
            '.sf-icon:not(.sf-icon-rounded):not(.sf-icon-shape):not(.sf-icon-full-font)',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertStringContainsString(
            '.sf-icon.sf-icon-full-font:not(.sf-icon-rounded):not(.sf-icon-shape)',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            'icon.classList.add("sf-icon-full-font")',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            '/_docara/vendor/google/material-symbols/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/MaterialSymbolsOutlined.woff2',
            $assets['simai.framework.icon_fallback_font.css']['content'],
        );
        self::assertStringContainsString(
            'data-docara-example-framework-inline-style="simai.framework.icon_fallback_font.css"',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Outlined Full"',
            $assets['simai.framework.icon_fallback_font.css']['content'],
        );
        self::assertStringContainsString(
            'return "Material Symbols Outlined Full"',
            $assets['simai.framework.icon_font.ready']['content'],
        );
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

        $productionPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
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

        $singleGapClass = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
            '<div class="gap-2">Gap</div>',
            [],
        );
        self::assertContains('gap/default', $singleGapClass->preload['modules']);

        $substringCollision = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
            '<div class="docara-gap-2x">No utility</div>',
            [],
        );
        self::assertNotContains('gap/default', $substringCollision->preload['modules']);

        $bodyPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
            '<!doctype html><html><body class="max-container-7"><main>Content</main></body></html>',
            [],
        );
        self::assertContains('max-container/default', $bodyPlan->preload['modules']);

        foreach (['sf-input' => 'inputs', 'sf-admin-menu' => 'admin-menu', 'sf-table' => 'table'] as $tag => $module) {
            $smartPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
                "<$tag></$tag>",
                [],
            );
            self::assertSame([], $smartPlan->diagnostics, "$tag must not fall back to an unavailable dynamic URL.");
            self::assertNotEmpty(
                array_filter(
                    array_column($smartPlan->assets, 'url'),
                    static fn (string $url): bool => str_starts_with(
                        $url,
                        "/_docara/framework-runtime/smart/$module/js/$module.js?sf_v=",
                    ),
                ),
            );
        }

        $nested = (new FrameworkAssetPlanner($repository, '/project~/docs/_docara/framework-runtime'))->plan([]);
        $nestedAssets = array_column($nested->assets, null, 'key');
        self::assertStringStartsWith(
            '/project~/docs/_docara/vendor/simai-framework/typography/5.4.0/core.css?sf_v=',
            $nestedAssets['simai.framework.core.css']['url'],
        );
        self::assertStringStartsWith(
            '/project~/docs/_docara/vendor/simai-framework/runtime/c6f98eb4cb29bdc892d4df6ce31c014479eb37fb/distr/core/js/core.js?sf_v=',
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
        $revision = $lock->runtimeProjection()['source']['revision'];
        $core = $root . '/resources/portable/vendor/simai-framework/runtime/'
            . $revision . '/distr/core/js/core.js';
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
                    '/_docara/framework-runtime',
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
            $revision = $lock->runtimeProjection()['source']['revision'];
            $core = $root . '/resources/portable/vendor/simai-framework/runtime/'
                . $revision . '/distr/core/js/core.js';
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
                '/_docara/framework-runtime',
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
                '/_docara/framework-runtime',
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
                    '/_docara/framework-runtime',
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
        // The own site takes its foundation from the runtime projection and no
        // longer pins a typography packet. These cases guard the bytes of a
        // pinned packet, so the fixture keeps the shape larena-doc still has:
        // the own site's projections with a pinned edition on top.
        $projectLock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $pinningConsumer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-5.6.1.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $projectLock['typography_projection'] = $pinningConsumer['typography_projection'];
        $lock = FrameworkLock::fromArray($projectLock);
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
        $smartRevision = $lock->assetProjection()['source']['revision'];
        $smartFiles = $lock->assetProjection()['files'];
        $dynamicProjection = $lock->dynamicAssetProjection();
        if (is_array($dynamicProjection)) {
            $smartFiles = [...$smartFiles, ...$dynamicProjection['files']];
        }
        foreach (array_keys($smartFiles) as $relativePath) {
            $source = dirname(__DIR__, 2) . '/resources/framework/runtime-smart/'
                . $smartRevision . '/' . $relativePath;
            $target = $resources . '/framework/runtime-smart/' . $smartRevision . '/' . $relativePath;
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
