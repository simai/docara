<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewTarget;
use Tests\TestCase;

final class ProjectExtensionDemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function starter_project_demos_render_through_the_project_registry_and_page_builder(): void
    {
        $runtime = ProjectRuntime::load($this->tmp);

        self::assertSame('project.install-builder', $runtime->smarts->canonicalKey('project.install_builder'));
        self::assertSame('project.product-configurator', $runtime->smarts->canonicalKey('project.product_configurator'));
        self::assertSame('project.footer-links', $runtime->smarts->canonicalKey('project.footer_links'));
        foreach (['project.install-builder', 'project.product-configurator', 'project.footer-links'] as $smart) {
            self::assertSame('project.project', $runtime->smarts->definition($smart)->provenance['provider']);
        }

        $build = $this->tmpPath('build_demo');
        $pages = (new PortableSiteBuilder(new Filesystem, new PortableMarkdownRenderer))->build($this->tmp, $build);
        $html = (string) file_get_contents($build . '/ru/project-demos/index.html');

        self::assertGreaterThan(0, $pages->count());
        self::assertStringContainsString('data-project-install-builder', $html);
        self::assertStringContainsString('data-project-product-configurator', $html);
        self::assertStringContainsString('data-project-footer-links', $html);
        self::assertStringContainsString('<sf-input', $html);
        self::assertStringContainsString('<sf-dropdown', $html);
        self::assertSame(3, substr_count($html, '<sf-list-item'));
        self::assertGreaterThanOrEqual(4, substr_count($html, '<sf-checkbox'));
        self::assertStringContainsString('type="text"', $html);
        self::assertStringNotContainsString(':::project.', $html);
        foreach ([
            'project.install-builder/assets/install-builder.css',
            'project.install-builder/assets/install-builder.js',
            'project.product-configurator/assets/product-configurator.css',
            'project.product-configurator/assets/product-configurator.js',
            'project.footer-links/assets/footer-links.css',
        ] as $asset) {
            self::assertFileExists($build . '/_docara/smart/' . $asset);
        }
        foreach ([
            'framework/smart/inputs/css/inputs.css',
            'framework/smart/inputs/css/inputs.min.css',
            'framework/smart/inputs/js/inputs.js',
            'framework/smart/dropdown/js/dropdown.js',
            'framework/smart/checkbox/css/checkbox.css',
            'framework/smart/checkbox/css/checkbox.min.css',
            'framework/smart/checkbox/js/checkbox.js',
            'framework/smart/list-item/js/list-item.js',
        ] as $asset) {
            self::assertFileExists($build . '/_docara/' . $asset);
        }
        self::assertStringNotContainsString('data-docara-smart-asset="framework.portable.', $html);
    }

    #[Test]
    public function project_demo_behavior_is_local_only_and_has_no_backend_side_effect_api(): void
    {
        foreach ([
            'smart/project.install-builder/assets/install-builder.js',
            'smart/project.product-configurator/assets/product-configurator.js',
        ] as $relative) {
            $javascript = (string) file_get_contents($this->tmpPath($relative));
            self::assertDoesNotMatchRegularExpression(
                '/\b(?:fetch|XMLHttpRequest|WebSocket|EventSource|sendBeacon)\s*\(/',
                $javascript,
            );
            self::assertDoesNotMatchRegularExpression(
                '/\b(?:exec|spawn|system|shell_exec|paymentRequest)\s*\(/i',
                $javascript,
            );
        }
    }

    #[Test]
    public function project_footer_preview_is_extracted_from_the_same_production_page(): void
    {
        $files = new Filesystem;
        $artifact = (new PreviewKernel(
            new PortableSiteBuilder($files, new PortableMarkdownRenderer),
            $files,
        ))->render($this->tmp, '/ru/project-demos/', PreviewTarget::Region, 'footer');

        self::assertStringContainsString('data-project-footer-links', $artifact->html);
        self::assertStringContainsString($artifact->html, $artifact->pageHtml);
        self::assertSame('portable_site_builder', $artifact->provenance['runtime']);
        self::assertContains('@project-tree:smart/project.footer-links', $artifact->dependencies);
        self::assertContains('design/blocks/project.footer-smart.json', $artifact->dependencies);
        self::assertContains('design/sections/project.footer.json', $artifact->dependencies);
        self::assertContains('design/views/section.project.footer.json', $artifact->dependencies);
    }
}
