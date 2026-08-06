<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class SurfaceRuntimeTest extends TestCase
{
    private string $site;

    protected function setUp(): void
    {
        $this->site = dirname(__DIR__, 2) . '/docs/site';
    }

    #[Test]
    public function surface_renders_bounded_geometry_tokens_and_local_decorative_media(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::surface {width=full content_width=container background_image=/assets/docara-screen.png background_fit=contain background_x=right background_y=bottom overlay=dark overlay_strength=strong padding=xl tone=contrast}
## Surface title

Visible content.
:::
MD, $this->site, $this->site . '/content/ru/components/surface.md');

        self::assertStringContainsString('data-docara-surface data-docara-width="full"', $html);
        self::assertStringContainsString('data-docara-content-width="container"', $html);
        self::assertStringContainsString('data-docara-surface-background alt="" aria-hidden="true"', $html);
        self::assertStringContainsString('src="../../assets/docara-screen.png" data-fit="contain" data-x="right" data-y="bottom"', $html);
        self::assertStringContainsString('data-overlay="dark" data-strength="strong" aria-hidden="true"', $html);
        self::assertStringContainsString('container m-inline-auto p-4', $html);
    }

    #[Test]
    public function registry_owned_capability_admits_grid_and_card_but_rejects_surface_and_landing_children(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $html = $renderer->render(<<<'MD'
:::::surface {width=full}
::::grid {columns=1}
:::card
#### Card

Body.
:::
::::
:::::
MD);
        self::assertStringContainsString('data-docara-block="surface"', $html);
        self::assertStringContainsString('data-docara-block="grid"', $html);
        self::assertStringContainsString('data-docara-block="card"', $html);

        foreach (['surface', 'hero', 'showcase', 'promo'] as $child) {
            try {
                $renderer->render(":::::surface\n:::$child\n## Child\n\nBody.\n:::\n:::::\n");
                self::fail("Unsafe Surface child [$child] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_BLOCK_NESTING_UNSUPPORTED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function unsafe_missing_remote_traversal_and_invalid_surface_props_fail_closed(): void
    {
        $cases = [
            ['background_image=https://example.test/a.png', 'MARKDOWN_SURFACE_BACKGROUND_UNSAFE'],
            ['background_image=/assets/../docara-screen.png', 'MARKDOWN_SURFACE_BACKGROUND_UNSAFE'],
            ['background_image=/assets/missing.png', 'MARKDOWN_SURFACE_BACKGROUND_UNSAFE'],
            ['background_x=right', 'MARKDOWN_SURFACE_BACKGROUND_REQUIRED'],
            ['overlay=dark', 'MARKDOWN_SURFACE_BACKGROUND_REQUIRED'],
            ['overlay_strength=strong', 'MARKDOWN_SURFACE_OVERLAY_REQUIRED'],
            ['padding=huge', 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID'],
            ['class=unsafe', 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN'],
        ];
        foreach ($cases as [$attributes, $code]) {
            try {
                (new PortableMarkdownRenderer)->render(
                    ":::surface {{$attributes}}\n## Title\n\nBody.\n:::\n",
                    $this->site,
                    $this->site . '/content/ru/components/surface.md',
                );
                self::fail("Unsafe Surface input [$attributes] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $exception->getMessage());
            }
        }
    }

    #[Test]
    public function symlinked_asset_roots_files_and_hardlinks_fail_before_external_mutation(): void
    {
        $filesystem = new Filesystem;
        $temporary = sys_get_temp_dir() . '/docara-surface-security-' . bin2hex(random_bytes(8));
        $outside = $temporary . '-outside';
        $site = $temporary . '/site';
        $source = $site . '/content/ru/page.md';
        foreach ([$site . '/content/ru', $site . '/assets', $outside] as $directory) {
            self::assertTrue($filesystem->makeDirectory($directory, 0700, true));
        }
        file_put_contents($source, '# Page');
        file_put_contents($outside . '/outside.png', 'immutable-outside');
        $outsideHash = hash_file('sha256', $outside . '/outside.png');

        try {
            self::assertTrue(symlink($outside . '/outside.png', $site . '/assets/link.png'));
            self::assertTrue(link($outside . '/outside.png', $site . '/assets/hard.png'));
            foreach (['/assets/link.png', '/assets/hard.png'] as $asset) {
                try {
                    (new PortableMarkdownRenderer)->render(
                        ":::surface {background_image=$asset}\n## Title\n\nBody.\n:::\n",
                        $site,
                        $source,
                    );
                    self::fail("Unsafe Surface asset [$asset] was accepted.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame('MARKDOWN_SURFACE_BACKGROUND_UNSAFE', $exception->errorCode);
                }
                self::assertSame($outsideHash, hash_file('sha256', $outside . '/outside.png'));
            }

            self::assertTrue($filesystem->deleteDirectory($site . '/assets'));
            self::assertTrue(symlink($outside, $site . '/assets'));
            try {
                (new PortableMarkdownRenderer)->render(
                    ":::surface {background_image=/assets/outside.png}\n## Title\n\nBody.\n:::\n",
                    $site,
                    $source,
                );
                self::fail('A symlinked Surface asset root was accepted.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_SURFACE_BACKGROUND_UNSAFE', $exception->errorCode);
            }
            self::assertSame($outsideHash, hash_file('sha256', $outside . '/outside.png'));
        } finally {
            $filesystem->deleteDirectory($temporary);
            $filesystem->deleteDirectory($outside);
        }
    }

    #[Test]
    public function atlas_exposes_surface_container_contract_capability_and_provenance(): void
    {
        $atlas = (new DesignAtlasService)->atlas($this->site)->data;
        $surface = array_values(array_filter(
            $atlas['entries'],
            static fn (array $entry): bool => ($entry['id'] ?? null) === 'docara.surface',
        ))[0] ?? null;

        self::assertIsArray($surface);
        self::assertSame('container', $surface['authoring_kind']);
        self::assertSame(['content.embeddable'], $surface['container_contract']['allowed_child_capabilities']);
        self::assertSame(['content'], $surface['container_contract']['slots']);
        self::assertSame(1, $surface['container_contract']['min_children']);
        self::assertSame(64, $surface['container_contract']['max_children']);
        self::assertSame('declared', $surface['container_contract']['order']);
        self::assertSame(3, $surface['container_contract']['max_depth']);
        self::assertSame(
            'resources/component-catalog/typed/docara.surface.json',
            $surface['provenance']['definition_ref'],
        );
    }
}
