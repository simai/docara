<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkAssetPlan;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkPortableAssetProjection;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortablePerformanceReceipt;
use Simai\Docara\PortableSite\PortablePublisherAssetPublisher;
use Simai\Docara\PortableSite\PortableRuntimeMetadata;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Smart\SmartRegistry;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class StaticBuildVerifierTest extends TestCase
{
    private const FRAMEWORK_PAIR = 'sf-v5.5.0-286e48b8-23d00d92';

    private const FRAMEWORK_PROVIDER_REVISION = '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663';

    private const FRAMEWORK_SMART_REVISION = '23d00d92346717b8f835297d142a14458f806602';

    private const SUPPORTED_COMPONENTS = ['ui.alert', 'ui.button'];

    /** @var array<string, true> */
    private array $normalizedBuilds = [];

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
        self::assertSame(0, $complete->getExitCode(), $complete->getErrorOutput() . $complete->getOutput());
        self::assertStringContainsString('"html_pages": 1', $complete->getOutput());
        self::assertStringContainsString('"local_references_checked":', $complete->getOutput());

        $sentinel = $this->tmpPath('project-config-loaded');
        file_put_contents(
            $this->tmpPath('config.php'),
            '<?php file_put_contents(' . var_export($sentinel, true) . ", 'loaded'); return [];\n",
        );
        $cli = $this->verifyViaCli($build);
        self::assertSame(0, $cli->getExitCode(), $cli->getErrorOutput() . $cli->getOutput());
        self::assertStringContainsString('"html_pages": 1', $cli->getOutput());
        self::assertFileDoesNotExist($sentinel, 'verify-static must not execute project PHP configuration.');

        unlink($build . '/asset.css');
        $broken = $this->verify($build);
        self::assertSame(1, $broken->getExitCode());
        self::assertStringContainsString('asset.css', $broken->getOutput());
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
    public function effective_component_catalogue_is_required_hash_bound_and_fail_closed(): void
    {
        $source = $this->tmpPath('component-catalogue-source');
        $build = $source . '/build_catalogue';
        $this->copyPortableFixtureLegacy($source);
        $this->removeAuthoredComponentPages($source);
        $configuration = $this->readJson($source . '/docara.json');
        $configuration['default_locale'] = 'en';
        $this->writeJson($source . '/docara.json', $configuration);
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
        ))->build($source, $build);

        $catalogPath = $build . '/_docara/component-catalog.json';
        self::assertDirectoryDoesNotExist($build . '/_docara/component-catalog');
        $plansPath = $build . '/.docara/resolved-page-plans.json';
        $originalCatalog = (string) file_get_contents($catalogPath);
        $originalPlans = (string) file_get_contents($plansPath);
        $performancePath = $build . '/.docara/performance.json';
        $originalPerformance = (string) file_get_contents($performancePath);
        $valid = $this->verify($build);
        self::assertSame(0, $valid->getExitCode(), $valid->getErrorOutput() . $valid->getOutput());

        $performance = json_decode($originalPerformance, true, flags: JSON_THROW_ON_ERROR);
        $performance['pages'][0]['html_bytes']++;
        $this->writeJson($performancePath, $performance);
        $tamperedPerformance = $this->verify($build);
        self::assertSame(1, $tamperedPerformance->getExitCode(), $tamperedPerformance->getOutput());
        self::assertStringContainsString('Performance receipt', $tamperedPerformance->getOutput());
        file_put_contents($performancePath, $originalPerformance);

        $plans = json_decode($originalPlans, true, flags: JSON_THROW_ON_ERROR);
        foreach ($plans['pages'] as &$page) {
            $page['resolved_page_plan']['framework_lock']['unexpected'] = 'accepted';
        }
        unset($page);
        $this->writeJson($plansPath, $plans);
        $unknownLockField = $this->verify($build);
        self::assertSame(1, $unknownLockField->getExitCode(), $unknownLockField->getOutput());
        self::assertStringContainsString('@resolved-page-plans', $unknownLockField->getOutput());
        self::assertStringContainsString('Framework tuple does not match', $unknownLockField->getOutput());
        file_put_contents($plansPath, $originalPlans);

        $plans = json_decode($originalPlans, true, flags: JSON_THROW_ON_ERROR);
        $plans['build']['framework']['portable_smart_asset_projection']['files']['smart/inputs/js/inputs.js']['sha256'] = str_repeat('f', 64);
        $this->writeJson($plansPath, $plans);
        $tamperedPortableProjection = $this->verify($build);
        self::assertSame(1, $tamperedPortableProjection->getExitCode(), $tamperedPortableProjection->getOutput());
        self::assertStringContainsString('portable Framework assets do not match exact page usage', $tamperedPortableProjection->getOutput());
        file_put_contents($plansPath, $originalPlans);

        $inputAssetPath = $build . '/_docara/framework/smart/inputs/js/inputs.js';
        $inputAsset = (string) file_get_contents($inputAssetPath);
        file_put_contents($inputAssetPath, $inputAsset . "\n/* tampered */\n");
        $tamperedPortableAsset = $this->verify($build);
        self::assertSame(1, $tamperedPortableAsset->getExitCode(), $tamperedPortableAsset->getOutput());
        self::assertStringContainsString('incorrect SHA-256', $tamperedPortableAsset->getOutput());
        file_put_contents($inputAssetPath, $inputAsset);

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

        $plans = json_decode($originalPlans, true, flags: JSON_THROW_ON_ERROR);
        $shellRecord = $plans['build']['framework']['shell']['plans']['index.html']['generated_assets'][0];
        $shellPath = $build . '/_docara/' . $shellRecord['filename'];
        $shellBytes = (string) file_get_contents($shellPath);
        file_put_contents($shellPath, $shellBytes . "\n/* tampered */\n");
        $tamperedShell = $this->verify($build, false);
        self::assertSame(1, $tamperedShell->getExitCode(), $tamperedShell->getOutput());
        self::assertStringContainsString('@framework-shell-asset', $tamperedShell->getOutput());
        file_put_contents($shellPath, $shellBytes);

        $indexPath = $build . '/index.html';
        $indexHtml = (string) file_get_contents($indexPath);
        file_put_contents(
            $indexPath,
            str_replace(
                'data-docara-framework-asset="simai.framework.preloaded"',
                'data-docara-framework-asset="simai.framework.preloaded-tampered"',
                $indexHtml,
            ),
        );
        $missingPreload = $this->verify($build, false);
        self::assertSame(1, $missingPreload->getExitCode(), $missingPreload->getOutput());
        self::assertStringContainsString('@framework-preload-contract', $missingPreload->getOutput());
        file_put_contents($indexPath, $indexHtml);

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
        self::assertCount(33, $supportedEntries);
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
    public function component_catalogue_verification_uses_the_exact_resolved_locale(): void
    {
        $source = $this->tmpPath('component-catalogue-english-source');
        $build = $source . '/build_catalogue';
        $this->copyPortableFixtureLegacy($source);
        $this->removeAuthoredComponentPages($source);
        $configuration = $this->readJson($source . '/docara.json');
        $configuration['default_locale'] = 'en';
        $configuration['search'] = ['enabled' => false, 'indexed' => false];
        $this->writeJson($source . '/docara.json', $configuration);
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
        ))->build($source, $build);

        $valid = $this->verify($build);
        self::assertSame(0, $valid->getExitCode(), $valid->getOutput());
        $manifestPath = $build . '/.docara/resolved-page-plans.json';
        $manifest = $this->readJson($manifestPath);
        $manifest['pages'][0]['resolved_page_plan']['configuration']['default_locale'] = 'ru';
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
                file_put_contents($path, str_replace('lang="en"', 'lang="ru"', $html));
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
    public function redirect_receipts_and_redirect_html_are_verified_fail_closed(): void
    {
        $validBuild = $this->createGeneratedCatalogBuild('redirect-contract-valid', true);
        $valid = $this->verify($validBuild);
        self::assertSame(0, $valid->getExitCode(), $valid->getOutput());

        $missingBuild = $this->createGeneratedCatalogBuild('redirect-contract-missing', true);
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

        $htmlBuild = $this->createGeneratedCatalogBuild('redirect-contract-html-tamper', true);
        $htmlPath = $htmlBuild . '/old-button/index.html';
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

        $hashBuild = $this->createGeneratedCatalogBuild('redirect-contract-hash-tamper', true);
        $receiptPath = $hashBuild . '/.docara/redirects.json';
        $receipt = $this->readJson($receiptPath);
        $receipt['content_sha256'] = str_repeat('f', 64);
        $this->writeJson($receiptPath, $receipt);
        $hashTamper = $this->verify($hashBuild);
        self::assertSame(1, $hashTamper->getExitCode(), $hashTamper->getOutput());
        self::assertStringContainsString('@redirect-contract', $hashTamper->getOutput());
        self::assertStringContainsString('content_sha256', $hashTamper->getOutput());

        $sourceHashBuild = $this->createGeneratedCatalogBuild('redirect-contract-source-hash-tamper', true);
        $receiptPath = $sourceHashBuild . '/.docara/redirects.json';
        $receipt = $this->readJson($receiptPath);
        $receipt['source_sha256'] = str_repeat('f', 64);
        $this->writeJson($receiptPath, $receipt);
        $sourceHashTamper = $this->verify($sourceHashBuild);
        self::assertSame(1, $sourceHashTamper->getExitCode(), $sourceHashTamper->getOutput());
        self::assertStringContainsString('@redirect-contract', $sourceHashTamper->getOutput());
        self::assertStringContainsString('source_sha256', $sourceHashTamper->getOutput());

        $routeBuild = $this->createGeneratedCatalogBuild('redirect-contract-route-tamper', true);
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

    private function createGeneratedCatalogBuild(string $name, bool $withRedirect = false): string
    {
        $source = $this->tmpPath($name . '-source');
        $build = $source . '/build_catalogue';
        $this->copyPortableFixtureLegacy($source);
        $configuration = $this->readJson($source . '/docara.json');
        $configuration['default_locale'] = 'en';
        $configuration['search'] = ['enabled' => false, 'indexed' => false];
        $this->writeJson($source . '/docara.json', $configuration);
        if ($withRedirect) {
            $redirects = $this->readJson($source . '/redirects.json');
            $redirects['redirects'] = [[
                'from' => 'old-button',
                'to' => 'components/button',
            ]];
            $this->writeJson($source . '/redirects.json', $redirects);
        }
        (new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
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

    /** @param array<string, mixed> $manifest */
    private function writeManifest(string $build, array $manifest): void
    {
        $frameworkAssetPlanner = null;
        $frameworkAssetPlans = [];
        $decorateSyntheticPages = false;
        if (is_array($manifest['pages'] ?? null)) {
            foreach ($manifest['pages'] as &$page) {
                if (! is_array($page)) {
                    continue;
                }
                $page['component_runtime'] ??= [
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
                $resolved = $page['resolved_page_plan'] ?? null;
                if (! is_array($resolved) || ! is_array($resolved['configuration'] ?? null)) {
                    continue;
                }
                $resolved['contract_version'] ??= 1;
                $resolved['page'] ??= 'content/index.md';
                $resolved['markdown'] ??= '# Fixture';
                $resolved['provenance'] ??= [];
                $resolved['trace'] ??= [[
                    'role' => 'content',
                    'source' => $resolved['page'],
                    'sha256' => hash('sha256', (string) $resolved['markdown']),
                ]];
                $page['resolved_page_plan'] = $resolved;
                $page['input_chain'] = [
                    'resolved_plan_sha256' => hash('sha256', CanonicalJson::encode([
                        'contract_version' => $resolved['contract_version'],
                        'page' => $resolved['page'],
                        'markdown' => $resolved['markdown'],
                        'configuration' => $resolved['configuration'],
                        'framework_lock' => $resolved['framework_lock'],
                        'provenance' => $resolved['provenance'],
                    ])),
                    'trace' => $resolved['trace'],
                    'document_ir_sha256' => hash('sha256', 'synthetic-document-ir'),
                    'framework_lock_sha256' => hash('sha256', CanonicalJson::encode($resolved['framework_lock'])),
                    'component_runtime_sha256' => hash('sha256', CanonicalJson::encode($page['component_runtime'])),
                ];
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
            if (is_array($configuration)
                && is_string($configuration['base_url'] ?? null)
                && (($configuration['base_url'] ?? null) === '/'
                    || preg_match(
                        '#^/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]+/?$#D',
                        (string) $configuration['base_url'],
                    ) === 1)
            ) {
                $metadata = new PortableRuntimeMetadata(dirname(__DIR__, 2));
                $frameworkLock = $this->frameworkLock();
                $catalog = EffectiveComponentCatalogBuilder::bundled(
                    FrameworkLock::fromArray($frameworkLock),
                )->build();
                $locale = $configuration['default_locale']
                    ?? $configuration['locale']
                    ?? 'en';
                $requiredPortableAssets = $this->requiredPortableAssets($manifest['pages']);
                $deploymentBase = ($configuration['base_url'] ?? '/') === '/'
                    ? ''
                    : '/' . trim((string) $configuration['base_url'], '/');
                $frameworkAssetPlanner = new FrameworkAssetPlanner(
                    FrameworkManifestRepository::bundled(FrameworkLock::fromArray($frameworkLock)),
                    $deploymentBase . '/_docara/framework',
                );
                $decorateSyntheticPages = true;
                $manifest['build'] = [
                    'purpose' => 'production',
                    'documentation_version' => $configuration['documentation_version'] ?? 'current',
                    'locale' => $locale,
                    'engine' => $metadata->package(),
                    'dependencies' => $metadata->dependencies(),
                    'framework' => [
                        'lock_sha256' => hash('sha256', CanonicalJson::encode($frameworkLock)),
                        'runtime' => $frameworkLock['runtime'],
                        'manifests' => $frameworkLock['manifests'],
                        'asset_projection' => $frameworkLock['asset_projection'],
                        'portable_smart_asset_projection' => (new FrameworkPortableAssetProjection(SmartRegistry::bundled()))
                            ->forKeys($requiredPortableAssets),
                        'shell' => [],
                    ],
                    'production_inputs' => $metadata->productionInputGroups(),
                    'component_catalog_sha256' => hash('sha256', CanonicalJson::encode($catalog)),
                    'publisher' => 'test.static_fixture',
                    'locale_sources' => [
                        $locale => [
                            'path' => 'content/' . $locale . '/lang.json',
                            'sha256' => hash('sha256', 'synthetic-locale-source:' . $locale),
                        ],
                    ],
                ];
            }
        }
        if ($decorateSyntheticPages && $frameworkAssetPlanner instanceof FrameworkAssetPlanner) {
            $receipts = [];
            foreach ($manifest['pages'] as $page) {
                $output = is_array($page) ? ($page['output'] ?? null) : null;
                if (! is_string($output) || ! is_file($build . '/' . $output)) {
                    continue;
                }
                $html = (string) file_get_contents($build . '/' . $output);
                $html = preg_replace(
                    '~<(?:script|style)\b[^>]*data-docara-framework-(?:asset|boot|preloaded-smart)\b[^>]*>.*?</(?:script|style)>~is',
                    '',
                    $html,
                ) ?? $html;
                $html = preg_replace(
                    '~<link\b[^>]*(?:data-docara-declarative-shell-style|data-docara-framework-asset)\b[^>]*>~is',
                    '',
                    $html,
                ) ?? $html;
                if (preg_match('/<html\b/i', $html) !== 1) {
                    $configuration = $page['resolved_page_plan']['configuration'] ?? [];
                    $locale = is_array($configuration)
                        ? ($configuration['locale'] ?? $configuration['default_locale'] ?? 'en')
                        : 'en';
                    $documentationVersion = is_array($configuration)
                        ? ($configuration['documentation_version'] ?? 'current')
                        : 'current';
                    $html = '<!doctype html><html lang="'
                        . htmlspecialchars((string) $locale, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . '" data-docara-documentation-version="'
                        . htmlspecialchars((string) $documentationVersion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . '"><head><meta name="docara:documentation-version" content="'
                        . htmlspecialchars((string) $documentationVersion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . '"></head><body>' . $html . '</body></html>';
                }
                $plan = $frameworkAssetPlanner->planForHtml($html, []);
                $frameworkAssetPlans[] = $plan;
                $receipts[$output] = $plan->receipt();
                $shellCssUrl = $plan->shellCssUrl();
                self::assertIsString($shellCssUrl);
                $frameworkHead = '<link rel="stylesheet" href="'
                    . htmlspecialchars($shellCssUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '" data-docara-declarative-shell-style>'
                    . $plan->headHtml();
                file_put_contents(
                    $build . '/' . $output,
                    preg_replace('/<\/head>/i', $frameworkHead . '</head>', $html, 1),
                );
            }
            ksort($receipts, SORT_STRING);
            $manifest['build']['framework']['shell'] = [
                'schema' => 'docara.framework_asset_plans.v1',
                'mode' => 'production_exact',
                'plans' => $receipts,
            ];
        }
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        $this->writeJson($build . '/.docara/resolved-page-plans.json', $manifest);
        $this->writeComponentCatalog($build);
        $requiredPortableAssets = $this->requiredPortableAssets($manifest['pages'] ?? []);
        $this->writeFrameworkAssets($build, $requiredPortableAssets);
        (new PortablePublisherAssetPublisher($this->filesystem))->publish(
            $build,
            $requiredPortableAssets,
            null,
        );
        if ($frameworkAssetPlans !== []) {
            (new PortablePublisherAssetPublisher($this->filesystem))
                ->publishFrameworkAssetPlans($build, $frameworkAssetPlans);
        }
    }

    /** @param list<array<string, mixed>> $pages */
    private function decorateSyntheticPagesWithFrameworkShell(
        string $build,
        array $pages,
        FrameworkAssetPlan $plan,
    ): void {
        $shellCssUrl = $plan->shellCssUrl();
        self::assertIsString($shellCssUrl);
        $head = '<link rel="stylesheet" href="'
            . htmlspecialchars($shellCssUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" data-docara-declarative-shell-style>' . $plan->headHtml();
        foreach ($pages as $page) {
            $output = $page['output'] ?? null;
            if (! is_string($output) || $output === '') {
                continue;
            }
            $path = $build . '/' . $output;
            if (! is_file($path) || is_link($path)) {
                continue;
            }
            $html = (string) file_get_contents($path);
            if (str_contains($html, 'data-docara-declarative-shell-style')) {
                continue;
            }
            file_put_contents($path, $head . $html);
        }
    }

    private function writeComponentCatalog(string $build): void
    {
        $catalog = EffectiveComponentCatalogBuilder::bundled(
            FrameworkLock::fromArray($this->frameworkLock()),
        )->build();
        $this->filesystem->ensureDirectoryExists($build . '/_docara');
        $this->writeJson($build . '/_docara/component-catalog.json', $catalog);
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

    /** @param list<string> $requiredPortableAssets */
    private function writeFrameworkAssets(string $build, array $requiredPortableAssets): void
    {
        $root = dirname(__DIR__, 2);
        foreach (array_keys($this->frameworkLock()['asset_projection']['files']) as $relativePath) {
            $source = $root . '/resources/framework/assets/' . $relativePath;
            $target = $build . '/_docara/framework/' . $relativePath;
            $this->filesystem->ensureDirectoryExists(dirname($target));
            file_put_contents($target, (string) file_get_contents($source));
        }
        foreach (['declarative-shell.css', 'declarative-shell.js'] as $name) {
            $target = $build . '/_docara/' . $name;
            $this->filesystem->ensureDirectoryExists(dirname($target));
            file_put_contents($target, (string) file_get_contents($root . '/resources/portable/' . $name));
        }
        foreach (SmartRegistry::bundled()->assets() as $key => $asset) {
            if (str_starts_with((string) $key, 'framework.portable.')
                && ! in_array($key, $requiredPortableAssets, true)
            ) {
                continue;
            }
            $target = $build . '/_docara/' . $asset['public'];
            $this->filesystem->ensureDirectoryExists(dirname($target));
            file_put_contents($target, (string) file_get_contents($root . '/resources/' . $asset['path']));
        }
    }

    /** @param list<array<string, mixed>> $pages @return list<string> */
    private function requiredPortableAssets(array $pages): array
    {
        $required = [];
        foreach ($pages as $page) {
            foreach (($page['declarative_pipeline']['assets'] ?? []) as $asset) {
                if (is_string($asset) && str_starts_with($asset, 'framework.portable.')) {
                    $required[] = $asset;
                }
            }
        }

        return array_values(array_unique($required));
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
        if ($normalizeBuildIdentity && ! isset($this->normalizedBuilds[$build])) {
            $this->normalizeBuildIdentityFixture($build);
            $this->normalizedBuilds[$build] = true;
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
        $firstConfiguration = $pages[0]['resolved_page_plan']['configuration'] ?? null;
        $firstLock = $pages[0]['resolved_page_plan']['framework_lock'] ?? null;
        $fixturePlanner = null;
        $firstBaseUrl = is_array($firstConfiguration) ? ($firstConfiguration['base_url'] ?? null) : null;
        if (is_array($firstConfiguration)
            && is_array($firstLock)
            && is_string($firstBaseUrl)
            && ($firstBaseUrl === '/'
                || preg_match('#^/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]+/?$#D', $firstBaseUrl) === 1)
        ) {
            $deploymentBase = ($firstConfiguration['base_url'] ?? '/') === '/'
                ? ''
                : '/' . trim((string) $firstConfiguration['base_url'], '/');
            try {
                $fixturePlanner = new FrameworkAssetPlanner(
                    FrameworkManifestRepository::bundled(FrameworkLock::fromArray($firstLock)),
                    $deploymentBase . '/_docara/framework',
                );
            } catch (\Throwable) {
                // The verifier, rather than fixture normalization, owns the
                // fail-closed diagnostic for intentionally malformed locks.
            }
        }
        $fixturePlans = [];
        $fixtureReceipts = [];
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
            $html = preg_replace(
                '~<(?:script|style)\b[^>]*data-docara-framework-(?:asset|boot|preloaded-smart)\b[^>]*>.*?</(?:script|style)>~is',
                '',
                $html,
            ) ?? $html;
            $html = preg_replace(
                '~<link\b[^>]*(?:data-docara-declarative-shell-style|data-docara-framework-asset)\b[^>]*>~is',
                '',
                $html,
            ) ?? $html;
            $configuration = $page['resolved_page_plan']['configuration'] ?? [];
            $locale = is_array($configuration)
                ? ($configuration['locale'] ?? $configuration['default_locale'] ?? 'en')
                : 'en';
            $documentationVersion = is_array($configuration)
                ? ($configuration['documentation_version'] ?? 'current')
                : 'current';
            if (preg_match('/<html\b/i', $html) !== 1) {
                $html = '<!doctype html><html lang="' . htmlspecialchars((string) $locale, ENT_QUOTES)
                . '" data-docara-documentation-version="'
                . htmlspecialchars((string) $documentationVersion, ENT_QUOTES)
                . '"><head><meta name="docara:documentation-version" content="'
                . htmlspecialchars((string) $documentationVersion, ENT_QUOTES)
                . '"></head><body>' . $html . '</body></html>';
            }
            if ($fixturePlanner instanceof FrameworkAssetPlanner) {
                $plan = $fixturePlanner->planForHtml($html, []);
                $shellCssUrl = $plan->shellCssUrl();
                if (is_string($shellCssUrl)) {
                    $frameworkHead = '<link rel="stylesheet" href="'
                        . htmlspecialchars($shellCssUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . '" data-docara-declarative-shell-style>'
                        . $plan->headHtml();
                    $html = preg_replace('/<\/head>/i', $frameworkHead . '</head>', $html, 1) ?? $html;
                    $fixturePlans[] = $plan;
                    $fixtureReceipts[(string) $page['output']] = $plan->receipt();
                }
            }
            file_put_contents($path, $html);
        }
        if ($fixturePlans !== []) {
            ksort($fixtureReceipts, SORT_STRING);
            $manifest['build']['framework']['shell'] = [
                'schema' => 'docara.framework_asset_plans.v1',
                'mode' => 'production_exact',
                'plans' => $fixtureReceipts,
            ];
            $this->writeJson($manifestPath, $manifest);
            (new PortablePublisherAssetPublisher($this->filesystem))
                ->publishFrameworkAssetPlans($build, $fixturePlans);
        }
        if (is_file($build . '/.docara/performance.json')) {
            $performance = (new PortablePerformanceReceipt($this->filesystem))->publish(
                $build,
                is_string($firstBaseUrl) ? $firstBaseUrl : '/',
                $pages,
            );
            $manifest['build']['public_projections']['performance_sha256'] = $performance['content_sha256'];
            $this->writeJson($manifestPath, $manifest);
        }
    }

    private function copyPortableFixtureLegacy(string $target): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $target);
        rename($target . '/content/ru', $target . '/content-legacy');
        rmdir($target . '/content');
        rename($target . '/content-legacy', $target . '/content');
        $codePage = $target . '/content/components/code-from-file.md';
        file_put_contents(
            $codePage,
            str_replace(
                '../../../snippets/install.php',
                '../../snippets/install.php',
                (string) file_get_contents($codePage),
            ),
        );
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

    private function removeAuthoredComponentPages(string $source): void
    {
        $this->filesystem->deleteDirectory($source . '/content/components');
        if (is_file($source . '/content/components.md')) {
            unlink($source . '/content/components.md');
        }
    }
}
