<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\I18n\LanguagePackRepository;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\Translator;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\PortableSite\PortableComponentCatalogProjector;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class StaticBuildVerifierTest extends TestCase
{
    private const FRAMEWORK_PAIR = 'sf-v5.3.2-7e836d8a-dd786bba';

    private const FRAMEWORK_PROVIDER_REVISION = '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663';

    private const FRAMEWORK_SMART_REVISION = 'dd786bbae98391fb21df9b4e1e6cd402ead0614c';

    private const SUPPORTED_COMPONENTS = ['ui.alert', 'ui.button'];

    #[Test]
    public function empty_or_broken_builds_fail_and_complete_builds_pass(): void
    {
        $build = $this->tmpPath('build');
        $this->filesystem->ensureDirectoryExists($build);

        $missingManifest = $this->verify($build);
        self::assertSame(1, $missingManifest->getExitCode());
        self::assertStringContainsString('@resolved-page-plans', $missingManifest->getOutput());
        self::assertStringContainsString('manifest is missing or unsafe', $missingManifest->getOutput());

        file_put_contents($build . '/index.html', '<a href="/asset.css">Asset</a>');
        file_put_contents($build . '/asset.css', 'body{}');
        $this->writeResolvedPlans($build, '/');
        $complete = $this->verify($build);
        self::assertSame(0, $complete->getExitCode(), $complete->getErrorOutput());
        self::assertStringContainsString('"html_pages": 14', $complete->getOutput());
        self::assertStringContainsString('"local_references_checked":', $complete->getOutput());

        $sentinel = $this->tmpPath('project-config-loaded');
        file_put_contents(
            $this->tmpPath('config.php'),
            '<?php file_put_contents(' . var_export($sentinel, true) . ", 'loaded'); return [];\n",
        );
        $cli = $this->verifyViaCli($build);
        self::assertSame(0, $cli->getExitCode(), $cli->getErrorOutput() . $cli->getOutput());
        self::assertStringContainsString('"html_pages": 14', $cli->getOutput());
        self::assertFileDoesNotExist($sentinel, 'verify-static must not execute project PHP configuration.');

        unlink($build . '/asset.css');
        $broken = $this->verify($build);
        self::assertSame(1, $broken->getExitCode());
        self::assertStringContainsString('asset.css', $broken->getOutput());
    }

    #[Test]
    public function declarative_example_receipt_and_rendered_exact_sources_are_verified_fail_closed(): void
    {
        $site = $this->tmpPath('declarative-example-site');
        $this->copyPortableFixtureLegacy($site);
        $this->installDeclarativeExampleFixture($site);
        $build = $site . '/build_local';
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        ))->build($site, $build);

        $valid = $this->verify($build, normalizeBuildIdentity: false);
        self::assertSame(0, $valid->getExitCode(), $valid->getErrorOutput() . $valid->getOutput());

        $publicPath = $build . '/_docara/declarative-examples.json';
        $privatePath = $build . '/.docara/declarative-example-pages.json';
        $receipt = $this->readJson($publicPath);
        $receipt['pages'][0]['sources'][1]['sha256'] = str_repeat('0', 64);
        $receipt['content_sha256'] = hash('sha256', CanonicalJson::encode([
            'index' => $receipt['index'],
            'pages' => $receipt['pages'],
        ]));
        $this->writeJson($publicPath, $receipt);
        $this->writeJson($privatePath, $receipt);

        $tampered = $this->verify($build, normalizeBuildIdentity: false);
        self::assertSame(1, $tampered->getExitCode());
        self::assertStringContainsString('@declarative-examples-contract', $tampered->getOutput());
        self::assertStringContainsString('does not match rendered exact code', $tampered->getOutput());
    }

    #[Test]
    public function nested_deployment_base_is_removed_only_from_matching_absolute_local_references(): void
    {
        $build = $this->tmpPath('nested-build');
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        $this->filesystem->ensureDirectoryExists($build . '/guide');
        $this->filesystem->ensureDirectoryExists($build . '/assets');
        file_put_contents($build . '/index.html', '<a href="/project/docs/guide/">Guide</a><link href="/project/docs/assets/app.css">');
        file_put_contents($build . '/guide/index.html', '<a href="/project/docs/">Home</a>');
        file_put_contents($build . '/assets/app.css', 'body{}');
        foreach (['/project/docs/', '/project/docs'] as $baseUrl) {
            $this->writeResolvedPlans($build, $baseUrl, ['index.html', 'guide/index.html']);
            $complete = $this->verify($build);
            self::assertSame(0, $complete->getExitCode(), $complete->getErrorOutput() . $complete->getOutput());
            self::assertStringContainsString('"deployment_base": "/project/docs/"', $complete->getOutput());
        }

        file_put_contents($build . '/index.html', '<a href="/project/docs-extra/guide/">Wrong prefix</a>');
        $collision = $this->verify($build);
        self::assertSame(1, $collision->getExitCode());
        self::assertStringContainsString('@outside-deployment-base', $collision->getOutput());
    }

    #[Test]
    public function percent_encoded_asset_names_resolve_but_encoded_path_control_segments_fail(): void
    {
        $build = $this->tmpPath('encoded-build');
        $this->filesystem->ensureDirectoryExists($build);
        $this->writeResolvedPlans($build, '/');
        file_put_contents($build . '/index.html', '<a href="image%20space.png">Image</a>');
        file_put_contents($build . '/image space.png', 'png');

        $complete = $this->verify($build);
        self::assertSame(0, $complete->getExitCode(), $complete->getErrorOutput() . $complete->getOutput());

        file_put_contents($build . '/index.html', '<a href="%2e%2e/secret.txt">Unsafe</a>');
        $unsafe = $this->verify($build);
        self::assertSame(1, $unsafe->getExitCode());
        self::assertStringContainsString('@unsafe-decoded-path-segment', $unsafe->getOutput());
    }

    #[Test]
    public function query_only_self_links_and_directory_urls_resolve_to_their_html_pages(): void
    {
        $build = $this->tmpPath('directory-build');
        $this->filesystem->ensureDirectoryExists($build . '/guide');
        $this->filesystem->ensureDirectoryExists($build . '/guides/getting-started');
        $this->writeResolvedPlans($build, '/', [
            'index.html',
            'guide/index.html',
            'guides/getting-started/index.html',
        ]);
        file_put_contents($build . '/index.html', '<a href="/guides/getting-started">Guide</a>');
        file_put_contents($build . '/guide/index.html', '<a href="?q=1">Current query</a>');
        file_put_contents($build . '/guides/getting-started/index.html', '<a href="/">Home</a>');

        $complete = $this->verify($build);
        self::assertSame(0, $complete->getExitCode(), $complete->getErrorOutput() . $complete->getOutput());
    }

    #[Test]
    public function local_fragments_resolve_unicode_ids_and_duplicate_or_missing_ids_fail_closed(): void
    {
        $build = $this->tmpPath('fragment-build');
        $this->filesystem->ensureDirectoryExists($build . '/guide');
        $this->writeResolvedPlans($build, '/', ['index.html', 'guide/index.html']);
        file_put_contents(
            $build . '/index.html',
            '<h1 id="home">Home</h1>'
            . '<h2 id="привет-мир">Привет</h2>'
            . '<a href="#привет-мир">Raw Unicode</a>'
            . '<a href="#%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82-%D0%BC%D0%B8%D1%80">Encoded Unicode</a>'
            . '<a href="/guide/#target">Guide target</a>',
        );
        file_put_contents($build . '/guide/index.html', '<h1 id="target">Guide</h1><a href="/#home">Home</a>');

        $complete = $this->verify($build);
        self::assertSame(0, $complete->getExitCode(), $complete->getErrorOutput() . $complete->getOutput());

        file_put_contents($build . '/guide/index.html', '<h1 id="target">Guide</h1><p id="target">Duplicate</p>');
        $duplicate = $this->verify($build);
        self::assertSame(1, $duplicate->getExitCode());
        self::assertStringContainsString('@duplicate-html-id', $duplicate->getOutput());

        file_put_contents($build . '/guide/index.html', '<h1 id="target">Guide</h1>');
        file_put_contents($build . '/index.html', '<a href="/guide/#missing">Missing</a>');
        $missing = $this->verify($build);
        self::assertSame(1, $missing->getExitCode());
        self::assertStringContainsString('@missing-fragment', $missing->getOutput());

        file_put_contents($build . '/index.html', '<h1 id="home">Home</h1><a href="#%ZZ">Unsafe</a>');
        $unsafe = $this->verify($build);
        self::assertSame(1, $unsafe->getExitCode());
        self::assertStringContainsString('@unsafe-fragment-encoding', $unsafe->getOutput());
    }

    #[Test]
    public function symlinked_html_or_asset_entries_are_rejected_without_following_external_targets(): void
    {
        $build = $this->tmpPath('symlink-build');
        $outsideHtml = $this->tmpPath('outside.html');
        $outsideAsset = $this->tmpPath('outside.css');
        $this->filesystem->ensureDirectoryExists($build);
        $this->writeResolvedPlans($build, '/');
        file_put_contents($outsideHtml, '<p>outside</p>');
        file_put_contents($outsideAsset, 'body{}');

        try {
            self::assertTrue(symlink($outsideHtml, $build . '/index.html'));
            $htmlLink = $this->verify($build);
            self::assertSame(1, $htmlLink->getExitCode(), $htmlLink->getErrorOutput() . $htmlLink->getOutput());
            self::assertStringContainsString('@unsafe-artifact-entry', $htmlLink->getOutput());
            self::assertStringContainsString('index.html', $htmlLink->getOutput());
            self::assertSame('<p>outside</p>', file_get_contents($outsideHtml));

            unlink($build . '/index.html');
            file_put_contents($build . '/index.html', '<link href="asset.css">');
            self::assertTrue(symlink($outsideAsset, $build . '/asset.css'));
            $assetLink = $this->verify($build);
            self::assertSame(1, $assetLink->getExitCode(), $assetLink->getErrorOutput() . $assetLink->getOutput());
            self::assertStringContainsString('@unsafe-artifact-entry', $assetLink->getOutput());
            self::assertStringContainsString('asset.css', $assetLink->getOutput());
            self::assertSame('body{}', file_get_contents($outsideAsset));
        } finally {
            @unlink($outsideHtml);
            @unlink($outsideAsset);
        }
    }

    #[Test]
    public function a_symlinked_build_root_is_rejected_before_traversal(): void
    {
        $realBuild = $this->tmpPath('real-build');
        $linkedBuild = $this->tmpPath('linked-build');
        $this->filesystem->ensureDirectoryExists($realBuild);
        file_put_contents($realBuild . '/index.html', '<p>valid but external</p>');

        try {
            self::assertTrue(symlink($realBuild, $linkedBuild));
            $result = $this->verify($linkedBuild);
            self::assertSame(1, $result->getExitCode());
            self::assertStringContainsString('missing or unsafe', $result->getErrorOutput());
            self::assertSame('<p>valid but external</p>', file_get_contents($realBuild . '/index.html'));

            $dotSegment = $this->verify($linkedBuild . '/.');
            self::assertSame(1, $dotSegment->getExitCode());
            self::assertStringContainsString('missing or unsafe', $dotSegment->getErrorOutput());
        } finally {
            @unlink($linkedBuild);
        }
    }

    #[Test]
    public function symlinked_or_hardlinked_resolved_plan_manifests_are_rejected(): void
    {
        $build = $this->tmpPath('unsafe-manifest-build');
        $outside = $this->tmpPath('outside-manifest.json');
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        file_put_contents($build . '/index.html', '<p>Page</p>');
        file_put_contents($outside, json_encode([
            'pages' => [[
                'resolved_page_plan' => ['configuration' => ['base_url' => '/']],
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        try {
            self::assertTrue(symlink($outside, $build . '/.docara/resolved-page-plans.json'));
            $symlink = $this->verify($build);
            self::assertSame(1, $symlink->getExitCode(), $symlink->getErrorOutput() . $symlink->getOutput());
            self::assertStringContainsString('manifest is missing or unsafe', $symlink->getOutput());

            unlink($build . '/.docara/resolved-page-plans.json');
            self::assertTrue(link($outside, $build . '/.docara/resolved-page-plans.json'));
            $hardlink = $this->verify($build);
            self::assertSame(1, $hardlink->getExitCode(), $hardlink->getErrorOutput() . $hardlink->getOutput());
            self::assertStringContainsString('manifest is missing or unsafe', $hardlink->getOutput());
        } finally {
            @unlink($outside);
        }
    }

    #[Test]
    public function resolved_plan_schema_records_base_and_outputs_are_fail_closed(): void
    {
        $build = $this->tmpPath('manifest-contract-build');
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        file_put_contents($build . '/index.html', '<p>Page</p>');

        $cases = [
            ['manifest' => ['schema' => 'not-docara', 'pages' => []], 'message' => 'unsupported schema'],
            ['manifest' => ['schema' => 'docara.resolved_page_plans.v1', 'pages' => []], 'message' => 'non-empty page list'],
            ['manifest' => [
                'schema' => 'docara.resolved_page_plans.v1',
                'pages' => [['output' => 'index.html', 'resolved_page_plan' => ['configuration' => []]]],
            ], 'message' => 'missing base_url'],
            ['manifest' => [
                'schema' => 'docara.resolved_page_plans.v1',
                'pages' => [[
                    'resolved_page_plan' => ['configuration' => ['base_url' => '/']],
                ]],
            ], 'message' => 'unsafe output'],
            ['manifest' => [
                'schema' => 'docara.resolved_page_plans.v1',
                'pages' => [
                    ['output' => 'index.html', 'resolved_page_plan' => ['configuration' => ['base_url' => '/']]],
                    ['output' => 'index.html', 'resolved_page_plan' => ['configuration' => ['base_url' => '/']]],
                ],
            ], 'message' => 'duplicated'],
            ['manifest' => [
                'schema' => 'docara.resolved_page_plans.v1',
                'pages' => [[
                    'output' => 'hidden/index.html',
                    'resolved_page_plan' => ['configuration' => ['base_url' => '/']],
                ]],
            ], 'message' => 'missing or unsafe'],
            ['manifest' => [
                'schema' => 'docara.resolved_page_plans.v1',
                'pages' => [[
                    'output' => 'index.html',
                    'resolved_page_plan' => ['configuration' => ['base_url' => '//']],
                ]],
            ], 'message' => 'deployment base is unsafe'],
        ];

        foreach ($cases as $case) {
            $this->writeManifest($build, $case['manifest']);
            $result = $this->verify($build);
            self::assertSame(1, $result->getExitCode(), $result->getErrorOutput() . $result->getOutput());
            self::assertStringContainsString($case['message'], $result->getOutput());
        }

        $this->filesystem->ensureDirectoryExists($build . '/hidden');
        file_put_contents($build . '/hidden/index.html', '<p>Unplanned page</p>');
        $this->writeResolvedPlans($build, '/');
        $unplanned = $this->verify($build);
        self::assertSame(1, $unplanned->getExitCode(), $unplanned->getErrorOutput() . $unplanned->getOutput());
        self::assertStringContainsString('do not exactly match generated HTML', $unplanned->getOutput());
    }

    #[Test]
    public function html_base_elements_are_rejected_and_attribute_whitespace_is_checked(): void
    {
        $build = $this->tmpPath('html-reference-contract-build');
        $this->filesystem->ensureDirectoryExists($build);
        file_put_contents($build . '/index.html', '<base href="/"><p>Page</p>');
        $this->writeResolvedPlans($build, '/');

        $base = $this->verify($build);
        self::assertSame(1, $base->getExitCode(), $base->getErrorOutput() . $base->getOutput());
        self::assertStringContainsString('@html-base-element', $base->getOutput());

        file_put_contents($build . '/index.html', "<a href = \"/missing/\">Missing</a><script src\n= '/missing.js'></script>");
        $whitespace = $this->verify($build);
        self::assertSame(1, $whitespace->getExitCode(), $whitespace->getErrorOutput() . $whitespace->getOutput());
        self::assertStringContainsString('/missing/', $whitespace->getOutput());
        self::assertStringContainsString('/missing.js', $whitespace->getOutput());
    }

    #[Test]
    public function special_and_hardlinked_artifact_entries_are_rejected(): void
    {
        $build = $this->tmpPath('unsafe-entry-build');
        $outside = $this->tmpPath('hardlink-source.css');
        $this->filesystem->ensureDirectoryExists($build);
        $this->writeResolvedPlans($build, '/');
        file_put_contents($build . '/index.html', '<p>Page</p>');
        file_put_contents($outside, 'body{}');
        self::assertTrue(link($outside, $build . '/hardlinked.css'));

        $hardlink = $this->verify($build);
        self::assertSame(1, $hardlink->getExitCode(), $hardlink->getErrorOutput() . $hardlink->getOutput());
        self::assertStringContainsString('@unsafe-artifact-entry', $hardlink->getOutput());
        self::assertStringContainsString('hardlinked.css', $hardlink->getOutput());

        unlink($build . '/hardlinked.css');
        if (! function_exists('posix_mkfifo')) {
            return;
        }
        self::assertTrue(posix_mkfifo($build . '/unexpected.pipe', 0600));
        $special = $this->verify($build);
        self::assertSame(1, $special->getExitCode(), $special->getErrorOutput() . $special->getOutput());
        self::assertStringContainsString('@unsafe-artifact-entry', $special->getOutput());
        self::assertStringContainsString('unexpected.pipe', $special->getOutput());
    }

    #[Test]
    public function search_artifacts_hashes_and_manifest_urls_are_verified_fail_closed(): void
    {
        foreach (['/' => 'root', '/project/docs/' => 'nested'] as $baseUrl => $case) {
            $valid = $this->createSearchBuild("search-valid-$case", $baseUrl);
            $pass = $this->verify($valid);
            self::assertSame(0, $pass->getExitCode(), $pass->getErrorOutput() . $pass->getOutput());

            $missing = $this->createSearchBuild("search-missing-$case", $baseUrl);
            unlink($missing . '/_docara/search-index.json');
            $missingResult = $this->verify($missing);
            self::assertSame(1, $missingResult->getExitCode());
            self::assertStringContainsString('@search-artifacts-missing', $missingResult->getOutput());

            $malformed = $this->createSearchBuild("search-malformed-$case", $baseUrl);
            file_put_contents($malformed . '/_docara/search-index.json', '{not-json');
            $malformedResult = $this->verify($malformed);
            self::assertSame(1, $malformedResult->getExitCode());
            self::assertStringContainsString('@search-index-contract', $malformedResult->getOutput());

            $wrongHash = $this->createSearchBuild("search-wrong-hash-$case", $baseUrl);
            $index = json_decode(
                (string) file_get_contents($wrongHash . '/_docara/search-index.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $index['content_sha256'] = str_repeat('0', 64);
            file_put_contents(
                $wrongHash . '/_docara/search-index.json',
                json_encode($index, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            $wrongHashResult = $this->verify($wrongHash);
            self::assertSame(1, $wrongHashResult->getExitCode());
            self::assertStringContainsString('content_sha256', $wrongHashResult->getOutput());

            $outside = $this->createSearchBuild("search-outside-$case", $baseUrl);
            $index = json_decode(
                (string) file_get_contents($outside . '/_docara/search-index.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $index['documents'][0]['url'] = '/outside/';
            $index['documents'][0]['id'] = hash(
                'sha256',
                $index['documents'][0]['locale'] . "\0" . $index['documents'][0]['url'],
            );
            $index['content_sha256'] = hash('sha256', CanonicalJson::encode($index['documents']));
            file_put_contents(
                $outside . '/_docara/search-index.json',
                json_encode($index, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            $outsideResult = $this->verify($outside);
            self::assertSame(1, $outsideResult->getExitCode());
            self::assertStringContainsString('@search-index-contract', $outsideResult->getOutput());

            $unmanifested = $this->createSearchBuild("search-unmanifested-$case", $baseUrl);
            $index = json_decode(
                (string) file_get_contents($unmanifested . '/_docara/search-index.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $index['documents'][1]['url'] = $baseUrl === '/' ? '/missing/' : $baseUrl . 'missing/';
            $index['documents'][1]['id'] = hash(
                'sha256',
                $index['documents'][1]['locale'] . "\0" . $index['documents'][1]['url'],
            );
            $index['content_sha256'] = hash('sha256', CanonicalJson::encode($index['documents']));
            file_put_contents(
                $unmanifested . '/_docara/search-index.json',
                json_encode($index, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
            $unmanifestedResult = $this->verify($unmanifested);
            self::assertSame(1, $unmanifestedResult->getExitCode());
            self::assertStringContainsString('do not exactly match', $unmanifestedResult->getOutput());
        }
    }

    #[Test]
    public function search_uses_the_inherited_default_locale_when_page_locale_is_not_explicit(): void
    {
        $build = $this->createSearchBuild('search-default-locale', '/');
        $manifestPath = $build . '/.docara/resolved-page-plans.json';
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['pages'] as &$page) {
            unset($page['resolved_page_plan']['configuration']['locale']);
            $page['resolved_page_plan']['configuration']['default_locale'] = 'ru';
        }
        unset($page);
        $this->writeManifest($build, $manifest);

        $result = $this->verify($build);

        self::assertSame(0, $result->getExitCode(), $result->getErrorOutput() . $result->getOutput());
    }

    #[Test]
    public function generated_component_catalogue_is_required_hash_bound_and_fail_closed(): void
    {
        $source = $this->tmpPath('component-catalogue-source');
        $build = $source . '/build_catalogue';
        $this->copyPortableFixtureLegacy($source);
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        ))->build($source, $build);

        $catalogPath = $build . '/_docara/component-catalog.json';
        $plansPath = $build . '/.docara/resolved-page-plans.json';
        $originalCatalog = (string) file_get_contents($catalogPath);
        $originalPlans = (string) file_get_contents($plansPath);
        $valid = $this->verify($build);
        self::assertSame(0, $valid->getExitCode(), $valid->getErrorOutput() . $valid->getOutput());

        $plans = json_decode($originalPlans, true, flags: JSON_THROW_ON_ERROR);
        foreach ($plans['pages'] as &$page) {
            $page['resolved_page_plan']['framework_lock']['unexpected'] = 'accepted';
        }
        unset($page);
        $this->writeJson($plansPath, $plans);
        $unknownLockField = $this->verify($build);
        self::assertSame(1, $unknownLockField->getExitCode(), $unknownLockField->getOutput());
        self::assertStringContainsString('@framework-asset-projection', $unknownLockField->getOutput());
        self::assertStringContainsString('not an allowed property', $unknownLockField->getOutput());
        file_put_contents($plansPath, $originalPlans);

        $buttonAssetPath = $build . '/_docara/framework/smart/buttons/js/buttons.js';
        $buttonAsset = (string) file_get_contents($buttonAssetPath);
        unlink($buttonAssetPath);
        $missingAsset = $this->verify($build);
        self::assertSame(1, $missingAsset->getExitCode(), $missingAsset->getOutput());
        self::assertStringContainsString('@framework-asset-projection', $missingAsset->getOutput());
        self::assertStringContainsString('missing or unsafe', $missingAsset->getOutput());
        file_put_contents($buttonAssetPath, $buttonAsset);

        file_put_contents($build . '/_docara/framework/unexpected.js', 'unexpected');
        $unexpectedAsset = $this->verify($build);
        self::assertSame(1, $unexpectedAsset->getExitCode(), $unexpectedAsset->getOutput());
        self::assertStringContainsString('@framework-asset-projection', $unexpectedAsset->getOutput());
        self::assertStringContainsString('do not exactly match', $unexpectedAsset->getOutput());
        unlink($build . '/_docara/framework/unexpected.js');

        unlink($catalogPath);
        $missing = $this->verify($build);
        self::assertSame(1, $missing->getExitCode(), $missing->getErrorOutput() . $missing->getOutput());
        self::assertStringContainsString('@component-catalog-contract', $missing->getOutput());
        self::assertStringContainsString('missing or unsafe', $missing->getOutput());

        file_put_contents($catalogPath, $originalCatalog);
        $catalog = json_decode($originalCatalog, true, flags: JSON_THROW_ON_ERROR);
        $supportedEntries = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] === 'supported',
        ));
        $unavailableEntries = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => $entry['lifecycle'] !== 'supported',
        ));
        self::assertCount(12, $supportedEntries);
        self::assertCount(5, $unavailableEntries);
        self::assertSame(
            [],
            array_values(array_filter(
                $supportedEntries,
                static fn (array $entry): bool => $entry['verification']['demo'] !== true,
            )),
        );
        self::assertSame(
            [],
            array_values(array_filter(
                $unavailableEntries,
                static fn (array $entry): bool => $entry['verification']['demo'] !== false,
            )),
        );
        $catalog['entries'][0]['docs_ref'] = 'docs/tampered-component.md';
        $this->writeJson($catalogPath, $catalog);
        $hashTamper = $this->verify($build);
        self::assertSame(
            1,
            $hashTamper->getExitCode(),
            $hashTamper->getErrorOutput() . $hashTamper->getOutput(),
        );
        self::assertStringContainsString('@component-catalog-contract', $hashTamper->getOutput());
        self::assertStringContainsString('content_sha256', $hashTamper->getOutput());

        $catalog = json_decode($originalCatalog, true, flags: JSON_THROW_ON_ERROR);
        $supportedIndex = array_search(
            'supported',
            array_column($catalog['entries'], 'lifecycle'),
            true,
        );
        self::assertIsInt($supportedIndex);
        $catalog['entries'][$supportedIndex]['verification']['docs'] = false;
        $catalog['content_sha256'] = hash('sha256', CanonicalJson::encode($catalog['entries']));
        $this->writeJson($catalogPath, $catalog);
        $incompleteEvidence = $this->verify($build);
        self::assertSame(
            1,
            $incompleteEvidence->getExitCode(),
            $incompleteEvidence->getErrorOutput() . $incompleteEvidence->getOutput(),
        );
        self::assertStringContainsString('@component-catalog-contract', $incompleteEvidence->getOutput());
        self::assertStringContainsString('incomplete evidence', $incompleteEvidence->getOutput());

        $catalog = json_decode($originalCatalog, true, flags: JSON_THROW_ON_ERROR);
        $nativeIndex = array_search(
            'native.code',
            array_column($catalog['entries'], 'id'),
            true,
        );
        self::assertIsInt($nativeIndex);
        $catalog['entries'][$nativeIndex]['family'] = 'requirement';
        $catalog['content_sha256'] = hash('sha256', CanonicalJson::encode($catalog['entries']));
        $this->writeJson($catalogPath, $catalog);
        $familyDrift = $this->verify($build);
        self::assertSame(1, $familyDrift->getExitCode(), $familyDrift->getOutput());
        self::assertStringContainsString('@component-catalog-contract', $familyDrift->getOutput());
        self::assertStringContainsString('incorrectly executable', $familyDrift->getOutput());

        $catalog = json_decode($originalCatalog, true, flags: JSON_THROW_ON_ERROR);
        $smartIndex = array_search('ui.alert', array_column($catalog['entries'], 'id'), true);
        self::assertIsInt($smartIndex);
        $catalog['entries'][$smartIndex]['consumer_policy']['managed_properties'] = [];
        $catalog['entries'][$smartIndex]['consumer_policy']['forbidden_inputs'] = [];
        $catalog['entries'][$smartIndex]['consumer_policy']['omitted_assets'] = [];
        $catalog['content_sha256'] = hash('sha256', CanonicalJson::encode($catalog['entries']));
        $this->writeJson($catalogPath, $catalog);
        $plans = json_decode((string) file_get_contents($plansPath), true, flags: JSON_THROW_ON_ERROR);
        $policies = [];
        foreach ($catalog['entries'] as $entry) {
            if (($entry['family'] ?? null) === 'framework_smart') {
                $policies[$entry['id']] = $entry['consumer_policy'];
            }
        }
        foreach ($plans['pages'] as &$page) {
            $page['component_runtime']['diagnostics']['consumer_policy_sha256'] = hash(
                'sha256',
                CanonicalJson::encode($policies),
            );
        }
        unset($page);
        $this->writeJson($plansPath, $plans);
        $policyWidening = $this->verify($build);
        self::assertSame(1, $policyWidening->getExitCode(), $policyWidening->getOutput());
        self::assertStringContainsString('@component-catalog-contract', $policyWidening->getOutput());
        self::assertStringContainsString('trusted source projection', $policyWidening->getOutput());

        $catalog = json_decode($originalCatalog, true, flags: JSON_THROW_ON_ERROR);
        $smartIndex = array_search('ui.alert', array_column($catalog['entries'], 'id'), true);
        self::assertIsInt($smartIndex);
        $catalog['entries'][$smartIndex]['provenance']['manifest_sha256'] = str_repeat('f', 64);
        $catalog['content_sha256'] = hash('sha256', CanonicalJson::encode($catalog['entries']));
        $this->writeJson($catalogPath, $catalog);
        $plans = json_decode((string) file_get_contents($plansPath), true, flags: JSON_THROW_ON_ERROR);
        foreach ($plans['pages'] as &$page) {
            $page['component_runtime']['diagnostics']['consumer_policy_sha256'] = hash(
                'sha256',
                CanonicalJson::encode($this->syntheticComponentPolicies()),
            );
        }
        unset($page);
        $this->writeJson($plansPath, $plans);
        $provenanceTamper = $this->verify($build);
        self::assertSame(1, $provenanceTamper->getExitCode(), $provenanceTamper->getOutput());
        self::assertStringContainsString('@component-catalog-contract', $provenanceTamper->getOutput());
        self::assertStringContainsString('trusted source projection', $provenanceTamper->getOutput());
    }

    #[Test]
    public function generated_component_catalogue_verification_uses_the_exact_resolved_locale(): void
    {
        $source = $this->tmpPath('component-catalogue-english-source');
        $build = $source . '/build_catalogue';
        $this->copyPortableFixtureLegacy($source);
        $configuration = $this->readJson($source . '/docara.json');
        $configuration['default_locale'] = 'en';
        $configuration['search'] = ['enabled' => false, 'indexed' => false];
        $this->writeJson($source . '/docara.json', $configuration);
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        ))->build($source, $build);

        $valid = $this->verify($build);
        self::assertSame(0, $valid->getExitCode(), $valid->getOutput());
        self::assertStringContainsString(
            '>Component catalog<',
            (string) file_get_contents($build . '/components/catalog/index.html'),
        );

        $manifestPath = $build . '/.docara/resolved-page-plans.json';
        $manifest = $this->readJson($manifestPath);
        foreach ($manifest['pages'] as &$page) {
            if (($page['output'] ?? null) === 'components/catalog/index.html') {
                $page['resolved_page_plan']['configuration']['default_locale'] = 'ru';
            }
        }
        unset($page);
        $this->writeJson($manifestPath, $manifest);

        $localeDrift = $this->verify($build);
        self::assertSame(1, $localeDrift->getExitCode(), $localeDrift->getOutput());
        self::assertStringContainsString(
            'Resolved pages do not share one locale and documentation version.',
            $localeDrift->getOutput(),
        );
    }

    #[Test]
    public function resolved_build_and_html_locale_version_identity_fail_closed(): void
    {
        $mutations = [
            'html-lang' => static function (string $build): void {
                $path = $build . '/index.html';
                $html = (string) file_get_contents($path);
                file_put_contents($path, str_replace('lang="ru"', 'lang="en"', $html));
            },
            'html-version-attribute' => static function (string $build): void {
                $path = $build . '/index.html';
                $html = (string) file_get_contents($path);
                file_put_contents(
                    $path,
                    str_replace(
                        'data-docara-documentation-version="current"',
                        'data-docara-documentation-version="forged"',
                        $html,
                    ),
                );
            },
            'html-version-meta' => static function (string $build): void {
                $path = $build . '/index.html';
                $html = (string) file_get_contents($path);
                file_put_contents(
                    $path,
                    str_replace(
                        'name="docara:documentation-version" content="current"',
                        'name="docara:documentation-version" content="forged"',
                        $html,
                    ),
                );
            },
        ];

        foreach ($mutations as $case => $mutate) {
            $build = $this->createGeneratedCatalogBuild('build-identity-' . $case);
            $mutate($build);
            $result = $this->verify($build, false);
            self::assertSame(1, $result->getExitCode(), $result->getOutput());
            self::assertStringContainsString('@page-build-identity', $result->getOutput());
        }

        $missingBuild = $this->createGeneratedCatalogBuild('build-identity-missing-manifest-build');
        $manifestPath = $missingBuild . '/.docara/resolved-page-plans.json';
        $manifest = $this->readJson($manifestPath);
        unset($manifest['build']);
        $this->writeJson($manifestPath, $manifest);
        $missingBuildResult = $this->verify($missingBuild, false);
        self::assertSame(
            1,
            $missingBuildResult->getExitCode(),
            $missingBuildResult->getOutput(),
        );
        self::assertStringContainsString('@resolved-page-plans', $missingBuildResult->getOutput());
        self::assertStringContainsString(
            'Resolved build metadata is required',
            $missingBuildResult->getOutput(),
        );
    }

    #[Test]
    public function generated_component_catalogue_pages_fail_closed_on_receipt_inventory_and_semantic_drift(): void
    {
        $missingReceipt = $this->createGeneratedCatalogBuild('catalog-pages-missing-receipt');
        unlink($missingReceipt . '/.docara/component-catalog-pages.json');
        $missingReceiptResult = $this->verify($missingReceipt);
        self::assertSame(1, $missingReceiptResult->getExitCode(), $missingReceiptResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $missingReceiptResult->getOutput());
        self::assertStringContainsString('receipt is missing or unsafe', $missingReceiptResult->getOutput());

        $removedSurface = $this->createGeneratedCatalogBuild('catalog-pages-surface-removed');
        $removedManifest = $this->readJson($removedSurface . '/.docara/resolved-page-plans.json');
        $removedManifest['pages'] = array_values(array_filter(
            $removedManifest['pages'],
            static fn (array $page): bool => ! str_starts_with(
                (string) $page['output'],
                'components/catalog/',
            ),
        ));
        $this->writeJson($removedSurface . '/.docara/resolved-page-plans.json', $removedManifest);
        $this->filesystem->deleteDirectory($removedSurface . '/components/catalog');
        unlink($removedSurface . '/.docara/component-catalog-pages.json');
        $removedSurfaceResult = $this->verify($removedSurface);
        self::assertSame(1, $removedSurfaceResult->getExitCode(), $removedSurfaceResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $removedSurfaceResult->getOutput());

        $receiptHashTamper = $this->createGeneratedCatalogBuild('catalog-pages-receipt-hash-tamper');
        $receipt = $this->readJson($receiptHashTamper . '/.docara/component-catalog-pages.json');
        $receipt['index']['route'] = '/stale-hash/catalog/';
        $this->writeJson($receiptHashTamper . '/.docara/component-catalog-pages.json', $receipt);
        $receiptHashResult = $this->verify($receiptHashTamper);
        self::assertSame(1, $receiptHashResult->getExitCode(), $receiptHashResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $receiptHashResult->getOutput());
        self::assertStringContainsString('content_sha256', $receiptHashResult->getOutput());

        $receiptTamper = $this->createGeneratedCatalogBuild('catalog-pages-receipt-tamper');
        $receipt = $this->readJson($receiptTamper . '/.docara/component-catalog-pages.json');
        $receipt['index']['route'] = '/forged/catalog/';
        $this->rehashCatalogPagesReceipt($receipt);
        $this->writeJson($receiptTamper . '/.docara/component-catalog-pages.json', $receipt);
        $receiptTamperResult = $this->verify($receiptTamper);
        self::assertSame(1, $receiptTamperResult->getExitCode(), $receiptTamperResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $receiptTamperResult->getOutput());
        self::assertStringContainsString('trusted page projection', $receiptTamperResult->getOutput());

        $receiptSplit = $this->createGeneratedCatalogBuild('catalog-pages-receipt-split');
        $receipt = $this->readJson($receiptSplit . '/.docara/component-catalog-pages.json');
        $receipt['catalog_content_sha256'] = str_repeat('f', 64);
        $this->rehashCatalogPagesReceipt($receipt);
        $this->writeJson($receiptSplit . '/.docara/component-catalog-pages.json', $receipt);
        $receiptSplitResult = $this->verify($receiptSplit);
        self::assertSame(1, $receiptSplitResult->getExitCode(), $receiptSplitResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $receiptSplitResult->getOutput());
        self::assertStringContainsString('trusted catalogue hash', $receiptSplitResult->getOutput());

        $wrongId = $this->createGeneratedCatalogBuild('catalog-pages-wrong-id');
        $receipt = $this->readJson($wrongId . '/.docara/component-catalog-pages.json');
        $receipt['pages'][0]['id'] = 'aaa.component';
        $this->rehashCatalogPagesReceipt($receipt);
        $this->writeJson($wrongId . '/.docara/component-catalog-pages.json', $receipt);
        $wrongIdResult = $this->verify($wrongId);
        self::assertSame(1, $wrongIdResult->getExitCode(), $wrongIdResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $wrongIdResult->getOutput());
        self::assertStringContainsString('trusted page projection', $wrongIdResult->getOutput());

        $missingDetail = $this->createGeneratedCatalogBuild('catalog-pages-missing-detail');
        $receipt = $this->readJson($missingDetail . '/.docara/component-catalog-pages.json');
        $removed = $receipt['pages'][0];
        $manifest = $this->readJson($missingDetail . '/.docara/resolved-page-plans.json');
        $manifest['pages'] = array_values(array_filter(
            $manifest['pages'],
            static fn (array $page): bool => $page['output'] !== $removed['output'],
        ));
        $this->writeJson($missingDetail . '/.docara/resolved-page-plans.json', $manifest);
        $this->filesystem->deleteDirectory(dirname($missingDetail . '/' . $removed['output']));
        $missingDetailResult = $this->verify($missingDetail);
        self::assertSame(1, $missingDetailResult->getExitCode(), $missingDetailResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $missingDetailResult->getOutput());

        $extraDetail = $this->createGeneratedCatalogBuild('catalog-pages-extra-detail');
        $receipt = $this->readJson($extraDetail . '/.docara/component-catalog-pages.json');
        $original = $receipt['pages'][0];
        $rogue = $original;
        $rogue['id'] = 'rogue.component';
        $rogue['output'] = 'components/catalog/rogue.component/index.html';
        $rogue['route'] = '/components/catalog/rogue.component/';
        $manifest = $this->readJson($extraDetail . '/.docara/resolved-page-plans.json');
        $sourceRecord = null;
        foreach ($manifest['pages'] as $page) {
            if ($page['output'] === $original['output']) {
                $sourceRecord = $page;
                break;
            }
        }
        self::assertIsArray($sourceRecord);
        $sourceRecord['output'] = $rogue['output'];
        $sourceRecord['url'] = $rogue['route'];
        $sourceRecord['resolved_page_plan']['page'] = 'content/components/catalog/rogue.component.md';
        $manifest['pages'][] = $sourceRecord;
        $this->writeJson($extraDetail . '/.docara/resolved-page-plans.json', $manifest);
        $rogueHtml = str_replace(
            (string) $original['id'],
            (string) $rogue['id'],
            (string) file_get_contents($extraDetail . '/' . $original['output']),
        );
        $this->filesystem->ensureDirectoryExists(dirname($extraDetail . '/' . $rogue['output']));
        file_put_contents($extraDetail . '/' . $rogue['output'], $rogueHtml);
        $extraDetailResult = $this->verify($extraDetail);
        self::assertSame(1, $extraDetailResult->getExitCode(), $extraDetailResult->getOutput());
        self::assertStringContainsString('@component-catalog-pages-contract', $extraDetailResult->getOutput());

        foreach ([
            'source' => [
                'needle' => "Markdown и JSON можно хранить рядом с кодом и проверять через Git.\n:::",
                'replacement' => "Подменённый исходный код примера.\n:::",
            ],
            'rendered' => [
                'needle' => '<p>Markdown и JSON можно хранить рядом с кодом и проверять через Git.</p>',
                'replacement' => '<p>Подменённый отрисованный пример.</p>',
            ],
            'metadata' => [
                'needle' => 'Объединяет связанное содержимое Markdown на нейтральной визуально ограниченной поверхности.',
                'replacement' => 'Forged component metadata.',
            ],
            'provenance' => [
                'needle' => 'resources/component-catalog/typed/docara.card.json',
                'replacement' => 'resources/component-catalog/typed/forged.card.json',
            ],
        ] as $kind => $change) {
            $build = $this->createGeneratedCatalogBuild('catalog-pages-' . $kind . '-drift');
            $path = $build . '/components/catalog/docara.card/index.html';
            $html = (string) file_get_contents($path);
            $count = 0;
            $tampered = str_replace($change['needle'], $change['replacement'], $html, $count);
            self::assertGreaterThan(0, $count, "The [$kind] drift fixture did not match generated HTML.");
            file_put_contents($path, $tampered);

            $result = $this->verify($build);
            self::assertSame(1, $result->getExitCode(), $result->getOutput());
            self::assertStringContainsString('@component-catalog-pages-contract', $result->getOutput());
            self::assertStringContainsString('trusted contract fragment', $result->getOutput());

            $receipt = $this->readJson($build . '/.docara/component-catalog-pages.json');
            foreach ($receipt['pages'] as &$receiptPage) {
                if ($receiptPage['id'] !== 'docara.card') {
                    continue;
                }
                $receiptPage['contract_fragment_sha256'] = hash('sha256', $tampered);
                if ($kind === 'source') {
                    $receiptPage['example_sha256'] = hash('sha256', $change['replacement']);
                } elseif ($kind === 'rendered') {
                    $receiptPage['rendered_fragment_sha256'] = hash('sha256', $change['replacement']);
                } else {
                    $receiptPage['catalog_entry_sha256'] = hash('sha256', $change['replacement']);
                }
            }
            unset($receiptPage);
            $this->rehashCatalogPagesReceipt($receipt);
            $this->writeJson($build . '/.docara/component-catalog-pages.json', $receipt);
            $selfConsistentResult = $this->verify($build);
            self::assertSame(1, $selfConsistentResult->getExitCode(), $selfConsistentResult->getOutput());
            self::assertStringContainsString(
                '@component-catalog-pages-contract',
                $selfConsistentResult->getOutput(),
            );
            self::assertStringContainsString('trusted page projection', $selfConsistentResult->getOutput());
        }
    }

    #[Test]
    public function generated_component_catalogue_shell_fails_closed_on_landmark_adjacency_and_sibling_drift(): void
    {
        foreach ([
            'breadcrumbs-removed' => static fn (string $html): string => (string) preg_replace(
                '~\s*<nav data-docara-breadcrumbs\b.*?</nav>\s*~s',
                '',
                $html,
                1,
            ),
            'adjacency-removed' => static fn (string $html): string => (string) preg_replace(
                '~\s*<nav data-docara-previous-next\b.*?</nav>\s*~s',
                '',
                $html,
                1,
            ),
            'article-sibling-injected' => static fn (string $html): string => (string) preg_replace(
                '~</article>~',
                '<aside data-forged-shell-sibling>forged</aside></article>',
                $html,
                1,
            ),
            'mobile-toc-state-mismatch' => static fn (string $html): string => str_replace(
                'data-mobile-toc="auto-hidden"',
                'data-mobile-toc="shown"',
                $html,
            ),
        ] as $case => $mutate) {
            $build = $this->createGeneratedCatalogBuild('catalog-shell-' . $case);
            $path = $build . '/components/catalog/native.code/index.html';
            $original = (string) file_get_contents($path);
            $tampered = $mutate($original);
            self::assertNotSame($original, $tampered, "The [$case] shell fixture did not mutate.");
            file_put_contents($path, $tampered);

            $result = $this->verify($build);
            self::assertSame(1, $result->getExitCode(), $result->getOutput());
            self::assertStringContainsString(
                '@component-catalog-pages-contract',
                $result->getOutput(),
            );
        }
    }

    #[Test]
    public function generated_component_catalogue_receipt_and_detail_links_are_rejected_as_unsafe(): void
    {
        foreach (['symlink', 'hardlink'] as $kind) {
            $receiptBuild = $this->createGeneratedCatalogBuild('catalog-pages-receipt-' . $kind);
            $receiptPath = $receiptBuild . '/.docara/component-catalog-pages.json';
            $outsideReceipt = $this->tmpPath('outside-catalog-receipt-' . $kind . '.json');
            file_put_contents($outsideReceipt, (string) file_get_contents($receiptPath));
            unlink($receiptPath);
            self::assertTrue(
                $kind === 'symlink'
                    ? symlink($outsideReceipt, $receiptPath)
                    : link($outsideReceipt, $receiptPath),
            );
            $receiptResult = $this->verify($receiptBuild);
            self::assertSame(1, $receiptResult->getExitCode(), $receiptResult->getOutput());
            self::assertStringContainsString('@component-catalog-pages-contract', $receiptResult->getOutput());
            self::assertStringContainsString('receipt is missing or unsafe', $receiptResult->getOutput());
            self::assertStringStartsWith('{', (string) file_get_contents($outsideReceipt));

            $detailBuild = $this->createGeneratedCatalogBuild('catalog-pages-detail-' . $kind);
            $detailPath = $detailBuild . '/components/catalog/docara.card/index.html';
            $outsideDetail = $this->tmpPath('outside-catalog-detail-' . $kind . '.html');
            file_put_contents($outsideDetail, (string) file_get_contents($detailPath));
            unlink($detailPath);
            self::assertTrue(
                $kind === 'symlink'
                    ? symlink($outsideDetail, $detailPath)
                    : link($outsideDetail, $detailPath),
            );
            $detailResult = $this->verify($detailBuild);
            self::assertSame(1, $detailResult->getExitCode(), $detailResult->getOutput());
            self::assertStringContainsString('@unsafe-artifact-entry', $detailResult->getOutput());
            self::assertStringContainsString('docara.card/index.html', $detailResult->getOutput());
            self::assertStringStartsWith('<!doctype html>', (string) file_get_contents($outsideDetail));
        }
    }

    #[Test]
    public function redirect_receipts_and_redirect_html_are_verified_fail_closed(): void
    {
        $validBuild = $this->createGeneratedCatalogBuild('redirect-contract-valid');
        $valid = $this->verify($validBuild);
        self::assertSame(0, $valid->getExitCode(), $valid->getOutput());

        $missingBuild = $this->createGeneratedCatalogBuild('redirect-contract-missing');
        $receiptPath = $missingBuild . '/.docara/redirects.json';
        $receipt = $this->readJson($receiptPath);
        foreach ($receipt['redirects'] as $redirect) {
            $this->filesystem->deleteDirectory(
                dirname($missingBuild . '/' . $redirect['output']),
            );
        }
        unlink($receiptPath);
        $missing = $this->verify($missingBuild);
        self::assertSame(1, $missing->getExitCode(), $missing->getOutput());
        self::assertStringContainsString('@redirect-contract', $missing->getOutput());
        self::assertStringContainsString('Configured redirects require', $missing->getOutput());

        $htmlBuild = $this->createGeneratedCatalogBuild('redirect-contract-html-tamper');
        $htmlPath = $htmlBuild . '/components/button/index.html';
        file_put_contents(
            $htmlPath,
            str_replace(
                'noindex,follow',
                'index,follow',
                (string) file_get_contents($htmlPath),
            ),
        );
        $htmlTamper = $this->verify($htmlBuild);
        self::assertSame(1, $htmlTamper->getExitCode(), $htmlTamper->getOutput());
        self::assertStringContainsString('@redirect-contract', $htmlTamper->getOutput());
        self::assertStringContainsString('deterministic receipt', $htmlTamper->getOutput());

        $hashBuild = $this->createGeneratedCatalogBuild('redirect-contract-hash-tamper');
        $receiptPath = $hashBuild . '/.docara/redirects.json';
        $receipt = $this->readJson($receiptPath);
        $receipt['content_sha256'] = str_repeat('f', 64);
        $this->writeJson($receiptPath, $receipt);
        $hashTamper = $this->verify($hashBuild);
        self::assertSame(1, $hashTamper->getExitCode(), $hashTamper->getOutput());
        self::assertStringContainsString('@redirect-contract', $hashTamper->getOutput());
        self::assertStringContainsString('content_sha256', $hashTamper->getOutput());

        $sourceHashBuild = $this->createGeneratedCatalogBuild('redirect-contract-source-hash-tamper');
        $receiptPath = $sourceHashBuild . '/.docara/redirects.json';
        $receipt = $this->readJson($receiptPath);
        $receipt['source_sha256'] = str_repeat('f', 64);
        $this->writeJson($receiptPath, $receipt);
        $sourceHashTamper = $this->verify($sourceHashBuild);
        self::assertSame(1, $sourceHashTamper->getExitCode(), $sourceHashTamper->getOutput());
        self::assertStringContainsString('@redirect-contract', $sourceHashTamper->getOutput());
        self::assertStringContainsString('source_sha256', $sourceHashTamper->getOutput());

        $routeBuild = $this->createGeneratedCatalogBuild('redirect-contract-route-tamper');
        $receiptPath = $routeBuild . '/.docara/redirects.json';
        $receipt = $this->readJson($receiptPath);
        $receipt['redirects'][0]['target_url'] = '/forged/';
        $receipt['content_sha256'] = hash(
            'sha256',
            CanonicalJson::encode($receipt['redirects']),
        );
        $this->writeJson($receiptPath, $receipt);
        $routeTamper = $this->verify($routeBuild);
        self::assertSame(1, $routeTamper->getExitCode(), $routeTamper->getOutput());
        self::assertStringContainsString('@redirect-contract', $routeTamper->getOutput());
        self::assertStringContainsString('exact generated routes', $routeTamper->getOutput());
    }

    /** @param list<string> $outputs */
    private function writeResolvedPlans(string $build, string $baseUrl, array $outputs = ['index.html']): void
    {
        $this->writeManifest($build, [
            'schema' => 'docara.resolved_page_plans.v1',
            'pages' => array_map(static fn (string $output): array => [
                'output' => $output,
                'resolved_page_plan' => ['configuration' => ['base_url' => $baseUrl]],
            ], $outputs),
        ]);
    }

    private function createSearchBuild(string $name, string $baseUrl): string
    {
        $build = $this->tmpPath($name);
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        $this->filesystem->ensureDirectoryExists($build . '/_docara');
        $this->filesystem->ensureDirectoryExists($build . '/guide');
        $runtime = '(function(){"use strict";}());';
        file_put_contents($build . '/_docara/search.js', $runtime);

        $pages = [
            ['output' => 'index.html', 'url' => $baseUrl, 'title' => 'Home'],
            [
                'output' => 'guide/index.html',
                'url' => $baseUrl === '/' ? '/guide/' : $baseUrl . 'guide/',
                'title' => 'Guide',
            ],
        ];
        $documents = array_map(static function (array $page): array {
            return [
                'id' => hash('sha256', 'ru' . "\0" . $page['url']),
                'url' => $page['url'],
                'locale' => 'ru',
                'title' => $page['title'],
                'description' => '',
                'trail' => [],
                'headings' => [['level' => 1, 'text' => $page['title']]],
                'text' => $page['title'],
            ];
        }, $pages);
        usort($documents, static fn (array $left, array $right): int => [
            $left['locale'],
            $left['url'],
        ] <=> [
            $right['locale'],
            $right['url'],
        ]);
        $contentHash = hash('sha256', CanonicalJson::encode($documents));
        file_put_contents(
            $build . '/_docara/search-index.json',
            CanonicalJson::encodePretty([
                'schema' => 'docara.search_index.v1',
                'version' => 1,
                'algorithm' => 'docara-prefix-v1',
                'content_sha256' => $contentHash,
                'documents' => $documents,
            ]),
        );
        $runtimeHash = hash('sha256', $runtime);
        $searchIndexUrl = $baseUrl . '_docara/search-index.json?docara_v=' . $contentHash;
        $runtimeUrl = $baseUrl . '_docara/search.js?docara_v=' . $runtimeHash;
        foreach ($pages as $page) {
            $directory = dirname($build . '/' . $page['output']);
            $this->filesystem->ensureDirectoryExists($directory);
            file_put_contents(
                $build . '/' . $page['output'],
                '<dialog data-docara-search-index="' . $searchIndexUrl . '"></dialog>'
                . '<script defer src="' . $runtimeUrl . '" data-docara-search-runtime></script>',
            );
        }
        $this->writeManifest($build, [
            'schema' => 'docara.resolved_page_plans.v1',
            'pages' => array_map(static fn (array $page): array => [
                'output' => $page['output'],
                'url' => $page['url'],
                'resolved_page_plan' => ['configuration' => [
                    'base_url' => $baseUrl,
                    'locale' => 'ru',
                    'search' => ['enabled' => true, 'indexed' => true],
                ]],
            ], $pages),
        ]);

        return $build;
    }

    private function createGeneratedCatalogBuild(string $name): string
    {
        $source = $this->tmpPath($name . '-source');
        $build = $source . '/build_catalogue';
        $this->copyPortableFixtureLegacy($source);
        $configuration = $this->readJson($source . '/docara.json');
        $configuration['search'] = ['enabled' => false, 'indexed' => false];
        $this->writeJson($source . '/docara.json', $configuration);
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            new PortableHtmlRenderer,
        ))->build($source, $build);

        return $build;
    }

    /** @return array<string, mixed> */
    private function readJson(string $path): array
    {
        return json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /** @param array<string, mixed> $receipt */
    private function rehashCatalogPagesReceipt(array &$receipt): void
    {
        $receipt['content_sha256'] = hash('sha256', CanonicalJson::encode([
            'catalog_content_sha256' => $receipt['catalog_content_sha256'],
            'index' => $receipt['index'],
            'pages' => $receipt['pages'],
        ]));
    }

    /** @param array<string, mixed> $manifest */
    private function writeManifest(string $build, array $manifest): void
    {
        if (is_array($manifest['pages'] ?? null)) {
            foreach ($manifest['pages'] as &$page) {
                if (! is_array($page) || array_key_exists('component_runtime', $page)) {
                    continue;
                }
                $page['component_runtime'] = [
                    'diagnostics' => [
                        'runtime_pair' => self::FRAMEWORK_PAIR,
                        'provider_revision' => self::FRAMEWORK_PROVIDER_REVISION,
                        'supported_components' => self::SUPPORTED_COMPONENTS,
                        'consumer_policy_sha256' => hash(
                            'sha256',
                            CanonicalJson::encode($this->syntheticComponentPolicies()),
                        ),
                    ],
                ];
                $page['resolved_page_plan']['framework_lock'] ??= $this->frameworkLock();
            }
            unset($page);
        }
        if (($manifest['schema'] ?? null) === 'docara.resolved_page_plans.v1'
            && is_array($manifest['pages'] ?? null)
            && array_is_list($manifest['pages'])
            && $manifest['pages'] !== []
            && ! array_key_exists('build', $manifest)
        ) {
            $configuration = $manifest['pages'][0]['resolved_page_plan']['configuration'] ?? null;
            if (is_array($configuration)) {
                $manifest['build'] = [
                    'documentation_version' => $configuration['documentation_version'] ?? 'current',
                    'locale' => $configuration['default_locale']
                        ?? $configuration['locale']
                        ?? 'en',
                ];
            }
        }
        if ($this->manifestSupportsComponentCatalogProjection($manifest)) {
            $manifest = $this->appendComponentCatalogProjection($build, $manifest);
        }
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        $this->writeJson($build . '/.docara/resolved-page-plans.json', $manifest);
        $this->writeComponentCatalog($build);
        $this->writeFrameworkAssets($build);
    }

    /** @param array<string, mixed> $manifest */
    private function manifestSupportsComponentCatalogProjection(array $manifest): bool
    {
        if (($manifest['schema'] ?? null) !== 'docara.resolved_page_plans.v1'
            || ! is_array($manifest['pages'] ?? null)
            || ! array_is_list($manifest['pages'])
            || $manifest['pages'] === []
        ) {
            return false;
        }
        $outputs = [];
        $baseUrl = null;
        foreach ($manifest['pages'] as $page) {
            $output = is_array($page) ? ($page['output'] ?? null) : null;
            $configuration = is_array($page)
                ? ($page['resolved_page_plan']['configuration'] ?? null)
                : null;
            $currentBase = is_array($configuration) ? ($configuration['base_url'] ?? null) : null;
            if (! is_string($output)
                || preg_match('#\A(?:[A-Za-z0-9][A-Za-z0-9._~-]*/)*[A-Za-z0-9][A-Za-z0-9._~-]*\.html\z#', $output) !== 1
                || isset($outputs[$output])
                || str_starts_with($output, 'components/catalog/')
                || ! is_string($currentBase)
                || $currentBase === ''
            ) {
                return false;
            }
            $outputs[$output] = true;
            $baseUrl ??= $currentBase;
            if ($currentBase !== $baseUrl) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function appendComponentCatalogProjection(string $build, array $manifest): array
    {
        $first = $manifest['pages'][0];
        $lock = $first['resolved_page_plan']['framework_lock'];
        $firstConfiguration = $first['resolved_page_plan']['configuration'];
        $baseUrl = (string) $firstConfiguration['base_url'];
        $locale = (string) (
            $firstConfiguration['locale']
            ?? $firstConfiguration['default_locale']
            ?? 'en'
        );
        $deploymentBase = $baseUrl === '/' ? '/' : '/' . trim($baseUrl, '/') . '/';
        $catalog = EffectiveComponentCatalogBuilder::bundled(
            FrameworkLock::fromArray($lock),
        )->build();
        $runtime = FrameworkComponentRuntime::fromLock(
            $lock,
            rtrim($deploymentBase, '/') . '/_docara/framework',
        );
        $translationConfiguration = $firstConfiguration;
        $translationConfiguration['default_locale'] = $locale;
        if (is_array($translationConfiguration['locales'] ?? null)
            && ! isset($translationConfiguration['locales'][$locale])
        ) {
            unset($translationConfiguration['locales']);
        }
        $projector = new PortableComponentCatalogProjector(
            new PortableMarkdownRenderer,
            translator: new Translator(
                LocaleRegistry::fromSite($translationConfiguration),
                new LanguagePackRepository(dirname(__DIR__, 2)),
            ),
        );
        $renderer = new PortableHtmlRenderer;
        $projection = $projector->project(
            catalog: $catalog,
            runtime: $runtime,
            basePlan: new ResolvedPagePlan(
                page: '@test/component-catalog.md',
                markdown: '',
                configuration: [
                    'base_url' => $baseUrl,
                    'default_locale' => $locale,
                    'locale' => $locale,
                    'preset' => 'docs',
                    'layout' => ['max_width' => 'normal'],
                    'navigation' => ['hidden' => false, 'order' => 900],
                    'search' => ['enabled' => false, 'indexed' => false],
                    'reading' => [
                        'breadcrumbs' => true,
                        'toc' => true,
                        'toc_depth' => 3,
                        'previous_next' => true,
                    ],
                    'settings' => ['theme' => 'system'],
                ],
                frameworkLock: $lock,
                trace: [],
                provenance: [],
            ),
            contentRoot: 'content',
            baseUrl: $deploymentBase,
            homeUrl: $deploymentBase,
            reservedDocumentIds: $renderer->reservedDocumentIds(),
        );

        $catalogNavigation = [[
            'title' => $locale === 'ru' ? 'Каталог компонентов' : 'Component catalog',
            'url' => $projection['receipt']['index']['route'],
            'children' => [],
            'active' => true,
            'current_section' => false,
            'active_ancestor' => false,
        ]];
        foreach ($projection['pages'] as $page) {
            $page['branding'] = ['title' => 'Docara'];
            $page['breadcrumbs'] = $page['component_catalog_breadcrumbs'];
            $page['previous'] = $page['component_catalog_previous'];
            $page['next'] = $page['component_catalog_next'];
            $output = $build . '/' . $page['output'];
            $this->filesystem->ensureDirectoryExists(dirname($output));
            $html = $renderer->render(
                $page,
                $catalogNavigation,
                'Docara',
                $page['components']->assetPlan,
            );
            $html = str_replace(
                '<script data-docara-shell-controller',
                '<script id="docara-runtime-copy" type="application/json">{}</script>'
                . '<script data-docara-shell-controller',
                $html,
            );
            file_put_contents($output, $html);
            $manifest['pages'][] = [
                'canonical_hash' => $page['plan']->canonicalHash(),
                'output' => $page['output'],
                'url' => $page['url'],
                'resolved_page_plan' => $page['plan']->toArray(),
                'component_runtime' => $page['components']->toArray(),
            ];
        }

        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        $this->writeJson(
            $build . '/.docara/component-catalog-pages.json',
            $projection['receipt'],
        );
        foreach ($projector->assets() as $relative => $bytes) {
            $target = $build . '/' . $relative;
            $this->filesystem->ensureDirectoryExists(dirname($target));
            file_put_contents($target, $bytes);
        }

        return $manifest;
    }

    private function writeComponentCatalog(string $build): void
    {
        $catalog = EffectiveComponentCatalogBuilder::bundled(
            FrameworkLock::fromArray($this->frameworkLock()),
        )->build();
        $this->filesystem->ensureDirectoryExists($build . '/_docara');
        $this->writeJson($build . '/_docara/component-catalog.json', $catalog);
    }

    private function installDeclarativeExampleFixture(string $site): void
    {
        $this->filesystem->ensureDirectoryExists($site . '/examples');
        $this->filesystem->ensureDirectoryExists($site . '/content/example-results');
        $this->writeJson($site . '/content/example-results/section.json', [
            'schema' => 'docara.section.v1',
            'navigation' => ['hidden' => true],
            'search' => ['indexed' => false],
        ]);
        file_put_contents(
            $site . '/content/example-results/button.md',
            "# Button\n\n:::ui.button\n{\"text\":\"Continue\",\"preset\":\"primary\"}\n:::\n",
        );
        $this->writeJson($site . '/content/example-results/button.page.json', [
            'schema' => 'docara.page.v1',
            'title' => 'Button',
        ]);
        $this->writeJson($site . '/examples/smart-button.json', [
            'schema' => 'docara.declarative_example.v1',
            'id' => 'smart-button',
            'title' => 'Smart Button',
            'description' => 'A real Smart component example.',
            'category' => 'smart',
            'order' => 10,
            'result_page' => 'content/example-results/button.md',
            'preview' => 'compact',
            'sources' => [[
                'label' => 'Markdown',
                'path' => 'content/example-results/button.md',
                'language' => 'markdown',
            ], [
                'label' => 'Page settings',
                'path' => 'content/example-results/button.page.json',
                'language' => 'json',
            ]],
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function syntheticComponentPolicies(): array
    {
        $policy = new FrameworkConsumerPolicy;
        $policies = [];
        foreach (self::SUPPORTED_COMPONENTS as $component) {
            $policies[$component] = $policy->catalogMetadata($component);
        }

        return $policies;
    }

    /** @return array<string, mixed> */
    private function frameworkLock(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function writeFrameworkAssets(string $build): void
    {
        $root = dirname(__DIR__, 2);
        foreach (array_keys($this->frameworkLock()['asset_projection']['files']) as $relativePath) {
            $source = $root . '/resources/framework/assets/' . $relativePath;
            $target = $build . '/_docara/framework/' . $relativePath;
            $this->filesystem->ensureDirectoryExists(dirname($target));
            file_put_contents($target, (string) file_get_contents($source));
        }
    }

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        file_put_contents(
            $path,
            json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ) . PHP_EOL,
        );
    }

    private function verify(string $build, bool $normalizeBuildIdentity = true): Process
    {
        if ($normalizeBuildIdentity) {
            $this->normalizeBuildIdentityFixture($build);
        }
        $process = new Process([
            PHP_BINARY,
            'scripts/verify-static-build.php',
            $build,
        ], dirname(__DIR__, 2));
        $process->run();

        return $process;
    }

    private function verifyViaCli(string $build): Process
    {
        $this->normalizeBuildIdentityFixture($build);
        $root = dirname(__DIR__, 2);
        $process = new Process([
            PHP_BINARY,
            $root . '/docara',
            'verify-static',
            $build,
            '--no-interaction',
        ], $this->tmp);
        $process->run();

        return $process;
    }

    private function normalizeBuildIdentityFixture(string $build): void
    {
        $manifestPath = $build . '/.docara/resolved-page-plans.json';
        if (! is_file($manifestPath)) {
            return;
        }
        try {
            $manifest = $this->readJson($manifestPath);
        } catch (\Throwable) {
            return;
        }
        $pages = $manifest['pages'] ?? null;
        if (! is_array($pages) || ! array_is_list($pages)) {
            return;
        }
        foreach ($pages as $page) {
            if (! is_array($page) || ! is_string($page['output'] ?? null)) {
                continue;
            }
            $path = $build . '/' . $page['output'];
            $stat = @lstat($path);
            if (is_link($path)
                || ! is_array($stat)
                || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                || ($stat['nlink'] ?? 1) > 1
            ) {
                continue;
            }
            $html = (string) file_get_contents($path);
            if (preg_match('/<html\b/i', $html) === 1) {
                continue;
            }
            $configuration = $page['resolved_page_plan']['configuration'] ?? [];
            $locale = is_array($configuration)
                ? ($configuration['locale'] ?? $configuration['default_locale'] ?? 'en')
                : 'en';
            $documentationVersion = is_array($configuration)
                ? ($configuration['documentation_version'] ?? 'current')
                : 'current';
            file_put_contents(
                $path,
                '<!doctype html><html lang="' . htmlspecialchars((string) $locale, ENT_QUOTES)
                . '" data-docara-documentation-version="'
                . htmlspecialchars((string) $documentationVersion, ENT_QUOTES)
                . '"><head><meta name="docara:documentation-version" content="'
                . htmlspecialchars((string) $documentationVersion, ENT_QUOTES)
                . '"></head><body>' . $html . '</body></html>',
            );
        }
    }

    private function copyPortableFixtureLegacy(string $target): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $target);
        rename($target . '/content/ru', $target . '/content-legacy');
        rmdir($target . '/content');
        rename($target . '/content-legacy', $target . '/content');
        $site = $this->readJson($target . '/docara.json');
        $site['content_root'] = 'content';
        unset($site['locales']);
        $site['locale_routing'] = [
            'strategy' => 'default_unprefixed',
            'root' => 'default_locale',
            'detect_browser_language' => false,
            'legacy_unprefixed_redirects' => false,
        ];
        $this->writeJson($target . '/docara.json', $site);
        $redirects = $this->readJson($target . '/redirects.json');
        $redirects['redirects'] = array_values(array_map(
            static fn (array $redirect): array => [
                'from' => $redirect['from'],
                'to' => preg_replace('#^ru/#', '', (string) $redirect['to']),
            ],
            array_filter(
                $redirects['redirects'],
                static fn (array $redirect): bool => ! str_starts_with((string) $redirect['from'], 'ru/'),
            ),
        ));
        $this->writeJson($target . '/redirects.json', $redirects);
    }
}
