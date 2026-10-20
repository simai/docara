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

        self::assertSame('ui-32c7a64e4575', $repository->runtime()['pair_id']);
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
        self::assertSame('ui-32c7a64e4575', $repository->runtime()['pair_id']);
        self::assertSame(1385, $repository->runtimeProjection()['files']);
        self::assertSame('sf-v5.6.1-34f5ff45-23d00d92', $previous['runtime']['pair_id']);

        $previous['runtime']['ui']['files'] = 6772;
        $this->expectException(FrameworkComponentException::class);
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
    }

    #[Test]
    public function the_lock_docara_2_18_shipped_builds_with_the_bundled_release(): void
    {
        $previous = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-2.18.2.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
        self::assertSame('ui-4f5e5067afeb-smart-7d932ed51408', $previous['runtime']['pair_id']);
        self::assertSame('ui-32c7a64e4575', $repository->runtime()['pair_id']);
        self::assertSame(1385, $repository->runtimeProjection()['files']);

        $previous['runtime']['ui']['files'] = 6144;
        $this->expectException(FrameworkComponentException::class);
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
    }

    #[Test]
    public function the_lock_docara_3_0_5_shipped_builds_with_the_bundled_release(): void
    {
        $previous = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-3.0.5.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
        self::assertSame('ui-82fc0ba882ba', $previous['runtime']['pair_id']);
        self::assertSame('ui-32c7a64e4575', $repository->runtime()['pair_id']);
        self::assertSame(1385, $repository->runtimeProjection()['files']);

        $previous['runtime']['ui']['files'] = 8090;
        $this->expectException(FrameworkComponentException::class);
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
    }

    #[Test]
    public function the_lock_docara_3_1_to_3_5_shipped_builds_with_the_bundled_release(): void
    {
        // Docara 3.1.0 to 3.5.0 created sites pinned to ui-25beeb814ae8.
        $previous = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/framework/simai-framework-3.5.0.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($previous));
        self::assertSame('ui-25beeb814ae8', $previous['runtime']['pair_id']);
        self::assertSame('ui-32c7a64e4575', $repository->runtime()['pair_id']);
        self::assertSame(1385, $repository->runtimeProjection()['files']);

        $previous['runtime']['ui']['files'] = 8558;
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
        self::assertSame(1385, $repository->runtimeProjection()['files']);
        self::assertArrayHasKey('rule/rule.json', $repository->runtimeManifest()['files']);
        self::assertSame(117, $legacy['runtime_projection']['files']);

        $legacy['runtime_projection']['packet_sha256'] = str_repeat('f', 64);
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_RUNTIME_MANIFEST_HASH_MISMATCH');
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($legacy));
    }

    #[Test]
    public function a_retired_typography_packet_takes_the_runtime_foundation_and_any_other_pin_fails_closed(): void
    {
        // The package no longer ships a typography packet: the runtime
        // projection's core.css carries the foundation with its own Inter
        // faces. A current lock that still pins the retired 5.4.0 edition, the
        // way larena-doc once did, is recorded in
        // superseded_typography_projections and takes the runtime foundation.
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
        self::assertNull($repository->typographyProjection());
        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->plan([]);
        $assets = array_column($plan->assets, null, 'key');
        self::assertStringStartsWith(
            '/_docara/vendor/simai-framework/runtime/' . $pinned['runtime']['ui']['commit'] . '/distr/core/css/core.css?sf_v=',
            $assets['simai.framework.core.css']['url'],
        );
        self::assertDirectoryDoesNotExist(dirname(__DIR__, 2) . '/resources/portable/vendor/simai-framework/typography');

        // A pinned packet that is not a recorded edition is not rewritten, and
        // its bytes are no longer bundled: it is refused before anything renders.
        $unknown = $pinned;
        $unknown['typography_projection']['files']['core']['sha256'] = str_repeat('f', 64);
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_TYPOGRAPHY_ASSET_MISSING');
        FrameworkManifestRepository::bundled(FrameworkLock::fromArray($unknown));
    }

    #[Test]
    public function exact_projections_publish_the_foundation_and_framework_runtime_locally(): void
    {
        // The own site's lock: the foundation, its Inter faces and the Framework
        // runtime all come from the runtime projection.
        $own = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $lock = FrameworkLock::fromArray($own);
        $repository = FrameworkManifestRepository::bundled($lock);
        self::assertNull($repository->typographyProjection());
        $uiCommit = (string) $own['runtime']['ui']['commit'];

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->plan([]);
        $assets = array_column($plan->assets, null, 'key');
        // One content-addressed stylesheet per source, the Docara shell last.
        self::assertGreaterThan(1, count($plan->generatedAssets));
        foreach ($plan->generatedAssets as $shell) {
            self::assertMatchesRegularExpression('/^docara-shell\.[a-f0-9]{64}\.css$/D', $shell['filename']);
            self::assertSame('/_docara/' . $shell['filename'], $shell['url']);
            self::assertSame($shell['sha256'], hash('sha256', $shell['content']));
            self::assertCount(1, $shell['inputs']);
            self::assertSame(1, substr_count($shell['content'], 'docara-shell-source: '));
        }
        $shell = $plan->generatedAssets[array_key_last($plan->generatedAssets)];
        self::assertStringStartsWith('/* docara-shell-source: portable/declarative-shell.css */', $shell['content']);
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
        foreach (['simai.framework.core.css' => 'core/css/core.css', 'simai.framework.utility.full.css' => 'core/css/utility.full.css'] as $assetKey => $runtimeFile) {
            self::assertStringStartsWith(
                '/_docara/vendor/simai-framework/runtime/' . $uiCommit . '/distr/' . $runtimeFile . '?sf_v=',
                $assets[$assetKey]['url'],
            );
            self::assertSame($repository->runtimeAssetRecord($runtimeFile)['sha256'], $assets[$assetKey]['sha256']);
            self::assertSame($uiCommit, $assets[$assetKey]['source_revision']);
        }
        // Sandboxed example frames receive every Framework stylesheet link as
        // bytes, with the font files its faces name. The Inter faces sit in the
        // runtime projection's core.css and point at the hashed woff2 files the
        // sync projects beside core/css/.
        self::assertMatchesRegularExpression(
            '#<link rel="stylesheet" href="/_docara/vendor/simai-framework/runtime/' . $uiCommit . '/distr/core/css/core\.css\?sf_v=[^"]+" data-docara-framework-asset="simai\.framework\.core\.css">#',
            $plan->headHtml(),
        );
        $coreCss = $repository->bundledRuntimeAsset('core/css/core.css');
        self::assertSame(7, substr_count($coreCss, 'font-family: "Inter Variable"'));
        preg_match_all('/url\(\.\.\/([a-f0-9]{20})\.woff2\)/', $coreCss, $faces);
        self::assertCount(7, $faces[1]);
        foreach ($faces[1] as $font) {
            self::assertSame(
                $repository->runtimeAssetRecord('core/' . $font . '.woff2')['sha256'],
                hash('sha256', $repository->bundledRuntimeAsset('core/' . $font . '.woff2')),
            );
        }
        $runtime = $repository->runtimeProjection();
        self::assertIsArray($runtime);
        self::assertSame(1385, $runtime['files']);
        $runtimeFiles = $repository->runtimeManifest()['files'];
        self::assertCount(1385, $runtimeFiles);
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
        // The release names formats without a version and carries it apart.
        $notices = json_decode(
            $repository->bundledRuntimeAsset('core/contracts/third-party-notices.v1.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('simai.framework.third-party-notices', $notices['schema']);
        self::assertSame(1, $notices['schema_version']);
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
            '05b452c6a886cc32a3d5ad34fd479b7459b4ffd7be7b3f8dfadf21090e0b4d4b',
            $runtime['packet_sha256'],
        );
        $coreLoader = $repository->bundledRuntimeAsset('core/js/core-loader.js');
        self::assertStringNotContainsString('@latest', $coreLoader);
        self::assertStringContainsString('component/icons/fonts/${file}', $coreLoader);
        self::assertStringContainsString('/_docara/vendor/simai-framework/runtime/', $assets['simai.framework.boot']['content']);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $assets['simai.framework.boot']['content']);
        foreach (['simai.framework.smart_base.js', 'simai.framework.core.js'] as $assetKey) {
            self::assertStringStartsWith('/_docara/vendor/simai-framework/runtime/', $assets[$assetKey]['url']);
            self::assertSame('32c7a64e457583eaf5a1860590001217a86b5af9', $assets[$assetKey]['source_revision']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $assets[$assetKey]['sha256']);
        }
        self::assertStringContainsString(
            '/_docara/vendor/docara/icon-subset/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/material-symbols-outlined.1923885bcfbdb6f0ece3.woff2',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Outlined Subset 1923885bcfbd"',
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
            '/_docara/vendor/docara/icon-subset/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/material-symbols-outlined.1923885bcfbdb6f0ece3.woff2',
            $assets['simai.framework.icon_font.css']['content'],
        );
        self::assertSame(247824, $plan->preload['icons']['font_size']);
        self::assertSame('icon_catalog_then_local_full_font', $plan->preload['icons']['fallback']);
        self::assertSame(
            '6084d652c50f78e38bec4a5c0ddc0215660ee239ec7dd46885d7c7ba666ede8f',
            $plan->preload['icons']['catalog']['index_sha256'],
        );
        self::assertSame(3926, $plan->preload['icons']['catalog']['icons']);
        // Every family, weight and fill an author can ask for has a static
        // instance of every icon; the browser picks one by font-weight.
        $catalogCss = $assets['simai.framework.icon_catalog.css']['content'];
        self::assertSame(20, substr_count($catalogCss, '@font-face{'));
        foreach (['outlined', 'rounded', 'sharp'] as $family) {
            foreach ([300, 400, 500] as $weight) {
                foreach ([0, 1] as $fill) {
                    self::assertStringContainsString('/*docara-icon-face:' . $family . '-' . $weight . '-' . $fill . '*/@font-face{', $catalogCss);
                }
            }
        }
        self::assertStringContainsString(
            '/_docara/vendor/docara/icon-catalog/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/material-symbols-rounded.',
            $catalogCss,
        );
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Outlined Subset 1923885bcfbd";src:url("/_docara/vendor/docara/icon-catalog/',
            $catalogCss,
        );
        self::assertStringContainsString('font-weight:300;font-display:block}', $catalogCss);
        self::assertStringContainsString(
            'html body .sf-icon.sf-icon-rounded:is(.sf-icon-filled,.sf-icon--filled,.sf-icon-solid):not(.sf-icon-full-font){',
            $catalogCss,
        );
        self::assertStringContainsString(
            'html body .sf-icon.sf-icon-complete:not(.sf-icon-rounded):not(.sf-icon-shape):not(.sf-icon-full-font){',
            $catalogCss,
        );
        self::assertStringNotContainsString('MaterialSymbols', $catalogCss);
        self::assertStringContainsString('ensureFullFont()', $assets['simai.framework.icon_font.ready']['content']);
        // In an example frame the runtime asks the shell for the full font
        // instead of fetching it from the site.
        self::assertStringContainsString('document.documentElement.hasAttribute("data-docara-example-frame")', $assets['simai.framework.icon_font.ready']['content']);
        self::assertStringContainsString('parent.postMessage({type:"docara:example-icons",icons:[token]},"*")', $assets['simai.framework.icon_font.ready']['content']);
        self::assertStringContainsString('requestFrameStyle("simai.framework.icon_fallback_font.css","full")', $assets['simai.framework.icon_font.ready']['content']);
        self::assertStringContainsString('requestFrameStyle("simai.framework.icon_catalog.css#"+face.id,"face:"+face.id)', $assets['simai.framework.icon_font.ready']['content']);
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
            'style[data-docara-example-framework-inline-style="\'+key+\'"]',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            '@font-face{font-family:"Material Symbols Outlined Full"',
            $assets['simai.framework.icon_fallback_font.css']['content'],
        );
        // Only axes no catalogue instance draws take a variable full font;
        // a name the subset lacks takes the catalogue.
        self::assertStringContainsString(
            '{family:"Material Symbols Outlined Full",weight:weight,full:"full"}',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            'catalogWeights.indexOf(weight)!==-1',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            'icon.classList.toggle("sf-icon-complete",complete)',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        // The subset draws every name written with its own characters; only
        // a name with another character leaves it.
        self::assertStringContainsString(
            'beyondSubset=/[^_abcdefghijklmnoprstuvwxy]/',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        // A component names an icon after it was first seen (a checkbox mark
        // reads `check` once checked); the face is chosen again for the new
        // name, so a name outside the subset gets the full font.
        self::assertStringContainsString(
            'icon.getAttribute("data-docara-icon-name")===current',
            $assets['simai.framework.icon_font.ready']['content'],
        );
        self::assertStringContainsString(
            'observe(document.body,{childList:true,subtree:true,characterData:true,attributes:true,attributeFilter:["icon"]})',
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
            'html body .sf-icon.sf-icon-rounded.sf-icon-full-font{',
            $assets['simai.framework.icon_variant_fonts.css']['content'],
        );
        self::assertStringContainsString(
            'html body .sf-icon.sf-icon-shape.sf-icon-full-font{',
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
        self::assertContains(
            'runtime/utility/display/default/css/default.css',
            array_map(
                static fn (array $asset): string => $asset['inputs'][0]['source'],
                $productionPlan->generatedAssets,
            ),
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

        // The admin menu and the table left the free release for a product
        // package (served through package_projections); they are not bundled.
        self::assertArrayNotHasKey('sf-admin-menu', $repository->runtime()['components']);
        self::assertArrayNotHasKey('sf-table', $repository->runtime()['components']);
        foreach (['sf-input' => 'inputs', 'sf-tabs' => 'tabs'] as $tag => $module) {
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

        // cl-tabs names cl-context-menu, cl-sortable and cl-checkbox as lazy
        // relations: the Loader fetches them after the page is ready, so the
        // first frame neither waits for them nor carries them.
        // Breakpoint variants follow breakpoint order whatever the registry
        // order: md before lg, or md:grid-col-2 beats lg:grid-col-4 on wide screens.
        $gridPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))
            ->planForHtml('<ul class="grid grid-col-1 md:grid-col-2 lg:grid-col-4 xl:grid-col-6 sm:grid-col-1"></ul>', []);
        $gridSources = array_values(array_filter(
            array_map(
                static fn (array $asset): string => preg_match('#^/\* docara-shell-source: (\S+) \*/#', (string) ($asset['content'] ?? ''), $match) === 1 ? $match[1] : '',
                $gridPlan->generatedAssets,
            ),
            static fn (string $source): bool => str_contains($source, 'utility/grid-template-columns/'),
        ));
        self::assertSame([
            'runtime/utility/grid-template-columns/default/css/default.css',
            'runtime/utility/grid-template-columns/sm/css/sm.css',
            'runtime/utility/grid-template-columns/md/css/md.css',
            'runtime/utility/grid-template-columns/lg/css/lg.css',
            'runtime/utility/grid-template-columns/xl/css/xl.css',
        ], $gridSources);
        self::assertSame(
            ['a/default', 'b', 'a/sm', 'a/md', 'c/hover', 'a/lg', 'a/xl'],
            FrameworkAssetPlanner::breakpointOrdered(['a/default', 'b', 'a/lg', 'a/md', 'c/hover', 'a/xl', 'a/sm']),
        );

        $tabsPlan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml('<sf-tabs></sf-tabs>', []);
        self::assertContains('cl-tabs', $tabsPlan->preload['modules']);
        foreach (['cl-context-menu', 'cl-sortable', 'cl-checkbox'] as $lazy) {
            self::assertNotContains($lazy, $tabsPlan->preload['modules']);
        }

        $nested = (new FrameworkAssetPlanner($repository, '/project~/docs/_docara/framework-runtime'))->plan([]);
        $nestedAssets = array_column($nested->assets, null, 'key');
        self::assertStringStartsWith(
            '/project~/docs/_docara/vendor/simai-framework/runtime/' . $uiCommit . '/distr/core/css/core.css?sf_v=',
            $nestedAssets['simai.framework.core.css']['url'],
        );
        // core.js carries no query: Core preloads its chunks with core.js's
        // query while the chunk imports carry none, so a query fetched them twice.
        self::assertSame(
            '/project~/docs/_docara/vendor/simai-framework/runtime/32c7a64e457583eaf5a1860590001217a86b5af9/distr/core/js/core.js',
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
            'material-symbols-outlined.1923885bcfbdb6f0ece3.manifest.json' => 'FRAMEWORK_ICON_SUBSET_MANIFEST_HASH_MISMATCH',
            'material-symbols-outlined.1923885bcfbdb6f0ece3.css' => 'FRAMEWORK_ICON_SUBSET_CSS_HASH_MISMATCH',
            'material-symbols-outlined.1923885bcfbdb6f0ece3.woff2' => 'FRAMEWORK_PORTABLE_ASSET_HASH_MISMATCH',
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
    public function changed_icon_catalogue_files_fail_before_render(): void
    {
        $directory = '/resources/portable/vendor/docara/icon-catalog/50f0603134ce7b70b2d71b686cc13e8b57ccb74c/';
        $index = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . $directory . 'icon-catalog.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $instance = $index['instances'][0];
        $cases = [
            'icon-catalog.json' => 'FRAMEWORK_ICON_CATALOG_INDEX_HASH_MISMATCH',
            $instance['manifest']['path'] => 'FRAMEWORK_ICON_CATALOG_MANIFEST_HASH_MISMATCH',
            $instance['font']['path'] => 'FRAMEWORK_PORTABLE_ASSET_HASH_MISMATCH',
        ];

        foreach ($cases as $file => $expectedCode) {
            [$root, $lock] = $this->fixture();
            file_put_contents($root . $directory . $file, 'changed');

            try {
                (new FrameworkAssetPlanner(
                    new FrameworkManifestRepository($lock, $root . '/resources/framework'),
                    '/_docara/framework-runtime',
                ))->plan([]);
                self::fail('Changed icon catalogue file was admitted: ' . $file);
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
            self::assertGreaterThan(1, count($plan->generatedAssets));
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
        foreach (glob(dirname(__DIR__, 2) . '/resources/portable/vendor/docara/{icon-subset,icon-catalog}/*/*', GLOB_BRACE) ?: [] as $source) {
            $relative = substr($source, strlen(dirname(__DIR__, 2) . '/resources/'));
            $target = $resources . '/' . $relative;
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }
        copy(dirname(__DIR__, 2) . '/resources/framework/runtime-lock.json', $resources . '/framework/runtime-lock.json');
        // The package no longer ships a typography packet, but the planner still
        // honours one in a resource root that carries it. These cases guard the
        // bytes of such a packet, so the fixture writes a small stand-in packet
        // of its own and pins it with matching digests.
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
        foreach ($projectLock['typography_projection']['files'] as $key => $record) {
            $target = $resources . '/' . $record['path'];
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            $bytes = $key === 'core'
                ? ":root{--sf-breakpoint-xxl:1400px}\n"
                : 'stand-in ' . $key . "\n";
            file_put_contents($target, $bytes);
            $projectLock['typography_projection']['files'][$key]['sha256'] = hash('sha256', $bytes);
        }
        $lock = FrameworkLock::fromArray($projectLock);
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
