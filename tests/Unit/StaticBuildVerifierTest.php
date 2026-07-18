<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class StaticBuildVerifierTest extends TestCase
{
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

    /** @param array<string, mixed> $manifest */
    private function writeManifest(string $build, array $manifest): void
    {
        $this->filesystem->ensureDirectoryExists($build . '/.docara');
        file_put_contents(
            $build . '/.docara/resolved-page-plans.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
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
}
