<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Portable\CanonicalJson;
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
        self::assertStringContainsString('"html_pages": 1', $complete->getOutput());
        self::assertStringContainsString('"local_references_checked": 1', $complete->getOutput());

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
    public function generated_component_catalogue_is_required_hash_bound_and_fail_closed(): void
    {
        $source = $this->tmpPath('component-catalogue-source');
        $build = $source . '/build_catalogue';
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $source);
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
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        $this->writeJson($build . '/.docara/resolved-page-plans.json', $manifest);
        $this->writeComponentCatalog($build);
        $this->writeFrameworkAssets($build);
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

    private function verify(string $build): Process
    {
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
}
