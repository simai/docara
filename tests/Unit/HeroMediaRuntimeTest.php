<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\PortableSite\PageBuilder;
use Simai\Docara\PortableSite\PageBuilderResult;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Smart\Runtime\ProjectSmartRuntime;

final class HeroMediaRuntimeTest extends TestCase
{
    private string $site;

    protected function setUp(): void
    {
        $this->site = dirname(__DIR__, 2) . '/docs/site';
    }

    #[Test]
    public function absent_media_and_explicit_auto_are_byte_identical_to_the_accepted_hero(): void
    {
        $body = "# Hero\n\nDescription.\n\n![Meaningful image](/assets/docara-screen.png)";
        $renderer = new PortableMarkdownRenderer;

        $accepted = $renderer->render(":::hero\n$body\n:::\n");
        self::assertSame('886d1e4b0b2066004431427c1f69e2b0d34b2ce71b4960db16ebf1e667cb9684', hash('sha256', $accepted));
        self::assertSame($accepted, $renderer->render(":::hero {media=auto}\n$body\n:::\n"));
    }

    #[Test]
    public function side_background_and_none_follow_one_typed_semantic_contract(): void
    {
        $side = $this->render(":::hero {media=side}\n# Side\n\nDescription.\n\n![Meaningful image](/assets/docara-screen.png)\n:::");
        self::assertStringContainsString('data-docara-media="hero"', $side);
        self::assertStringContainsString('alt="Meaningful image"', $side);
        self::assertStringNotContainsString('data-docara-surface-background', $side);

        $background = $this->render(":::hero {media=background}\n# Background\n\nDescription.\n\n![](/assets/docara-screen.png)\n:::");
        self::assertSame(1, substr_count($background, 'data-docara-surface-background'));
        self::assertStringContainsString('alt="" aria-hidden="true"', $background);
        self::assertStringContainsString('data-overlay="dark" data-strength="medium"', $background);
        self::assertStringContainsString('data-fit="cover" data-x="center" data-y="center"', $background);
        self::assertStringNotContainsString('data-docara-media="hero"', $background);
        self::assertSame(1, substr_count($background, 'docara-screen.png'));

        $none = $this->render(":::hero {media=none}\n# Text only\n\nDescription.\n:::");
        self::assertStringNotContainsString('<img', $none);
        self::assertStringContainsString('data-docara-block="hero"', $none);
    }

    #[Test]
    public function invalid_modes_and_background_assets_fail_closed_with_source_locations(): void
    {
        $cases = [
            [":::hero {media=side}\n# Hero\n\nDescription.\n:::", 'MARKDOWN_HERO_MEDIA_IMAGE_REQUIRED', 1],
            [":::hero {media=side variant=centered}\n# Hero\n\nDescription.\n\n![Meaningful](/assets/docara-screen.png)\n:::", 'MARKDOWN_HERO_MEDIA_VARIANT_INCOMPATIBLE', 1],
            [":::hero {media=side}\n# Hero\n\nDescription.\n\n![](/assets/docara-screen.png)\n:::", 'MARKDOWN_HERO_SIDE_ALT_REQUIRED', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![Meaningful](/assets/docara-screen.png)\n:::", 'MARKDOWN_HERO_BACKGROUND_ALT_FORBIDDEN', 5],
            [":::hero {media=none}\n# Hero\n\nDescription.\n\n![Meaningful](/assets/docara-screen.png)\n:::", 'MARKDOWN_HERO_MEDIA_IMAGE_FORBIDDEN', 5],
            [":::hero {media=background unsupported=value}\n# Hero\n\nDescription.\n\n![](/assets/docara-screen.png)\n:::", 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN', 1],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](/assets/docara-screen.png)\n\n![](/assets/docara-screen.png)\n:::", 'MARKDOWN_HERO_STRUCTURE_INVALID', 5],
            [":::hero {background_x=right}\n# Hero\n\nDescription.\n:::", 'MARKDOWN_HERO_BACKGROUND_MODE_REQUIRED', 1],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](https://example.test/hero.png)\n:::", 'MARKDOWN_HERO_BACKGROUND_UNSAFE', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](//example.test/hero.png)\n:::", 'MARKDOWN_HERO_BACKGROUND_UNSAFE', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](data:image/png;base64,AA)\n:::", 'MARKDOWN_HERO_IMAGE_UNSAFE', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](javascript:alert(1))\n:::", 'MARKDOWN_HERO_IMAGE_UNSAFE', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](/assets/missing.png)\n:::", 'MARKDOWN_HERO_BACKGROUND_UNSAFE', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](/assets/../docara-screen.png)\n:::", 'MARKDOWN_HERO_BACKGROUND_UNSAFE', 5],
            [":::hero {media=background}\n# Hero\n\nDescription.\n\n![](/assets/Docara-screen.png)\n:::", 'MARKDOWN_HERO_BACKGROUND_CASE_MISMATCH', 5],
        ];

        foreach ($cases as [$source, $code, $line]) {
            try {
                $this->build($source);
                self::fail("Expected [$code].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $exception->getMessage());
                self::assertTrue($exception->hasFileLocation(), $exception->getMessage());
                self::assertSame('content/ru/components/hero-s2-contract.md', $exception->sourcePath());
                self::assertSame($line, $exception->sourceLine(), $exception->getMessage());
                self::assertSame(1, $exception->sourceColumn());
            }
        }
    }

    #[Test]
    public function hero_background_uses_typed_ir_and_the_shared_surface_frame_in_page_builder(): void
    {
        $built = $this->build(":::hero {media=background background_fit=contain background_x=right background_y=bottom overlay=light overlay_strength=soft}\n# Hero\n\nDescription.\n\n![](/assets/docara-screen.png)\n:::");
        $node = $built->document->toArray()['nodes'][0] ?? null;

        self::assertSame('typed_directive', $node['type'] ?? null);
        self::assertSame('docara.hero', $node['data']['component'] ?? null);
        self::assertSame('background', $node['data']['props']['media'] ?? null);
        self::assertSame(1, substr_count($built->contentHtml, 'data-docara-surface-background'));
        self::assertStringContainsString('data-fit="contain" data-x="right" data-y="bottom"', $built->contentHtml);
        self::assertStringContainsString('data-overlay="light" data-strength="soft"', $built->contentHtml);
        self::assertSame(['/assets/docara-screen.png'], $built->documentArtifact->hydration['local_public_assets']);
    }

    #[Test]
    public function production_build_publishes_a_receipted_root_asset_without_locale_duplication(): void
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir() . '/docara-hero-build-' . bin2hex(random_bytes(8));
        self::assertTrue($files->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $root));
        $root = (string) realpath($root);
        self::assertTrue($files->copy(
            $this->site . '/assets/docara-screen.png',
            $root . '/assets/hero.png',
        ));
        file_put_contents(
            $root . '/content/ru/components/hero.md',
            "# Hero contract\n\n:::hero {media=background}\n# Background\n\nDescription.\n\n![](/assets/hero.png)\n:::\n",
        );

        try {
            (new PortableSiteBuilder($files, new PortableMarkdownRenderer))->build($root, $root . '/build_test');

            self::assertFileExists($root . '/build_test/ru/assets/hero.png');
            self::assertSame(
                hash_file('sha256', $root . '/assets/hero.png'),
                hash_file('sha256', $root . '/build_test/ru/assets/hero.png'),
            );
            $html = (string) file_get_contents($root . '/build_test/ru/components/hero/index.html');
            self::assertSame(1, substr_count($html, 'src="../../assets/hero.png"'));
            self::assertStringNotContainsString('data-docara-publish-local-asset', $html);
        } finally {
            $files->deleteDirectory($root);
        }
    }

    #[Test]
    public function a_receipted_root_asset_cannot_shadow_a_different_locale_owned_asset(): void
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir() . '/docara-hero-collision-' . bin2hex(random_bytes(8));
        self::assertTrue($files->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $root));
        $root = (string) realpath($root);
        $files->ensureDirectoryExists($root . '/content/ru/assets');
        file_put_contents($root . '/assets/hero.png', 'root');
        file_put_contents($root . '/content/ru/assets/hero.png', 'locale');
        file_put_contents(
            $root . '/content/ru/components/hero.md',
            "# Hero contract\n\n:::hero {media=background}\n# Background\n\nDescription.\n\n![](/assets/hero.png)\n:::\n",
        );

        try {
            $this->expectException(PortableConfigurationException::class);
            $this->expectExceptionMessage('[PORTABLE_ASSET_SOURCE_COLLISION]');
            (new PortableSiteBuilder($files, new PortableMarkdownRenderer))->build($root, $root . '/build_test');
        } finally {
            $files->deleteDirectory($root);
        }
    }

    #[Test]
    public function hero_asset_symlink_hardlink_case_and_traversal_paths_fail_closed(): void
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir() . '/docara-hero-media-' . bin2hex(random_bytes(8));
        $outside = $root . '-outside';
        foreach ([$root . '/content/ru', $root . '/assets', $outside] as $directory) {
            self::assertTrue($files->makeDirectory($directory, 0700, true));
        }
        file_put_contents($root . '/assets/Safe.png', 'safe');
        file_put_contents($outside . '/outside.png', 'outside');
        self::assertTrue(symlink($outside . '/outside.png', $root . '/assets/link.png'));
        self::assertTrue(link($outside . '/outside.png', $root . '/assets/hard.png'));

        try {
            $cases = [
                ['/assets/link.png', 'MARKDOWN_HERO_BACKGROUND_UNSAFE'],
                ['/assets/hard.png', 'MARKDOWN_HERO_BACKGROUND_UNSAFE'],
                ['/assets/safe.png', 'MARKDOWN_HERO_BACKGROUND_CASE_MISMATCH'],
                ['/assets/../outside.png', 'MARKDOWN_HERO_BACKGROUND_UNSAFE'],
                ['data:image/png;base64,AA', 'MARKDOWN_HERO_IMAGE_UNSAFE'],
            ];
            foreach ($cases as [$asset, $code]) {
                try {
                    (new PortableMarkdownRenderer)->render(
                        ":::hero {media=background}\n# Hero\n\nDescription.\n\n![]($asset)\n:::",
                        $root,
                        $root . '/content/ru/page.md',
                    );
                    self::fail("Unsafe Hero asset [$asset] was accepted.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame($code, $exception->errorCode, $exception->getMessage());
                }
            }
        } finally {
            $files->deleteDirectory($root);
            $files->deleteDirectory($outside);
        }
    }

    #[Test]
    public function production_page_builder_locates_symlink_and_hardlink_hero_images(): void
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir() . '/docara-hero-page-builder-' . bin2hex(random_bytes(8));
        $outside = $root . '-outside';
        self::assertTrue($files->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $root));
        self::assertTrue($files->makeDirectory($outside, 0700, true));
        file_put_contents($outside . '/outside.png', 'outside');
        self::assertTrue(symlink($outside . '/outside.png', $root . '/assets/link.png'));
        self::assertTrue(link($outside . '/outside.png', $root . '/assets/hard.png'));

        try {
            foreach (['/assets/link.png', '/assets/hard.png'] as $asset) {
                try {
                    $this->buildAt(
                        $root,
                        'content/ru/components/hero-s2-security.md',
                        ":::hero {media=background}\n# Hero\n\nDescription.\n\n![]($asset)\n:::",
                    );
                    self::fail("Unsafe Hero asset [$asset] was accepted.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame('MARKDOWN_HERO_BACKGROUND_UNSAFE', $exception->errorCode);
                    self::assertSame('content/ru/components/hero-s2-security.md', $exception->sourcePath());
                    self::assertSame('/document/hero/image', $exception->sourcePointer());
                    self::assertSame(5, $exception->sourceLine());
                    self::assertSame(1, $exception->sourceColumn());
                }
            }
        } finally {
            $files->deleteDirectory($root);
            $files->deleteDirectory($outside);
        }
    }

    #[Test]
    public function atlas_derives_all_hero_media_states_and_conditional_props_from_the_definition(): void
    {
        $atlas = (new DesignAtlasService)->atlas($this->site)->data;
        $hero = array_values(array_filter(
            $atlas['entries'],
            static fn (array $entry): bool => ($entry['id'] ?? null) === 'docara.hero',
        ))[0] ?? null;
        self::assertIsArray($hero);
        self::assertContains('background_media', $hero['states']);
        $parameters = array_column($hero['parameters'], null, 'name');
        self::assertSame(['auto', 'side', 'background', 'none'], $parameters['media']['values']);
        foreach (['background_fit', 'background_x', 'background_y', 'overlay', 'overlay_strength'] as $name) {
            self::assertSame(['media' => 'background'], $parameters[$name]['available_when']);
        }
    }

    private function render(string $source): string
    {
        return (new PortableMarkdownRenderer)->render(
            $source,
            $this->site,
            $this->site . '/content/ru/components/hero.md',
        );
    }

    private function build(string $markdown): PageBuilderResult
    {
        $base = (new PortableConfigurationLoader($this->site))->resolve('content/ru/components/hero.md');
        $site = json_decode((string) file_get_contents($this->site . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
        $project = ProjectSmartRuntime::fromSite($this->site, $site, $base->frameworkLock);
        self::assertNotNull($project);
        $plan = new ResolvedPagePlan(
            'content/ru/components/hero-s2-contract.md',
            $markdown,
            $base->configuration,
            $base->frameworkLock,
            $base->trace,
            $base->provenance,
        );
        $renderer = new PortableMarkdownRenderer(components: $project->gateway, smartRenderer: $project->renderer);

        return (new PageBuilder($renderer, smartRenderer: $project->renderer))->build(
            $plan,
            $this->site,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );
    }

    private function buildAt(string $site, string $source, string $markdown): PageBuilderResult
    {
        file_put_contents($site . '/' . $source, $markdown);
        $base = (new PortableConfigurationLoader($site))->resolve($source);
        $plan = new ResolvedPagePlan(
            $source,
            $markdown,
            $base->configuration,
            $base->frameworkLock,
            $base->trace,
            $base->provenance,
        );

        return (new PageBuilder(new PortableMarkdownRenderer))->build(
            $plan,
            $site,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );
    }
}
