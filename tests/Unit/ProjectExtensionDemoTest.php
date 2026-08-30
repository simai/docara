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
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
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
        self::assertSame(3, substr_count($html, '<sf-dropdown'));
        self::assertSame(8, substr_count($html, '<sf-list-item'));
        self::assertSame(5, substr_count($html, '<sf-checkbox'));
        self::assertSame(2, substr_count($html, '<sf-input'));
        self::assertStringContainsString('type="text"', $html);
        self::assertStringNotContainsString('<select', $html);
        self::assertStringNotContainsString(' readonly', $html);
        self::assertStringContainsString('name="operating-system"', $html);
        self::assertStringContainsString('name="install-method"', $html);
        self::assertStringContainsString('name="package"', $html);
        self::assertStringContainsString('name="version"', $html);
        self::assertStringContainsString('name="development"', $html);
        self::assertStringContainsString('name="prefer-dist"', $html);
        self::assertStringContainsString('data-team-price="4500"', $html);
        self::assertStringContainsString('data-business-price="8000"', $html);
        self::assertStringContainsString('data-config-summary', $html);
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
    public function project_demo_semantic_contracts_cover_every_control_and_fail_closed(): void
    {
        $node = (new ExecutableFinder)->find('node');
        if (! is_string($node)) {
            self::markTestSkipped('Node is optional for consumers and required only for this developer semantic contract test.');
        }
        $probe = new Process([$node, '--version']);
        try {
            $probe->run();
        } catch (ProcessSignaledException) {
            self::markTestSkipped('The discovered optional Node executable is not runnable in this developer environment.');
        }
        if (! $probe->isSuccessful()) {
            self::markTestSkipped('The discovered optional Node executable is not runnable in this developer environment.');
        }

        $runner = $this->tmpPath('project-demo-contract.mjs');
        $install = json_encode($this->tmpPath('smart/project.install-builder/assets/install-builder.js'), JSON_THROW_ON_ERROR);
        $configurator = json_encode($this->tmpPath('smart/project.product-configurator/assets/product-configurator.js'), JSON_THROW_ON_ERROR);
        file_put_contents($runner, <<<JS
import {$install};
import {$configurator};
const api = globalThis.DocaraProjectDemoContracts;
const payload = {
  installDefault: api.buildInstallCommand({os:'macOS',method:'Composer',packageName:'simai/docara',version:'^2.0',development:false,preferDist:false}),
  installAll: api.buildInstallCommand({os:'Linux',method:'Composer PHAR',packageName:'acme/docs',version:'~3.1',development:true,preferDist:true}),
  installWindows: api.buildInstallCommand({os:'Windows PowerShell',method:'Composer',packageName:'acme/docs',version:'3.0.0',development:false,preferDist:true}),
  installInvalid: api.buildInstallCommand({os:'macOS',method:'Composer',packageName:'acme/docs;rm',version:'^2.0',development:false,preferDist:false}),
  starter: api.calculateProductConfiguration({variant:'Стартовый',prices:{starter:2500,team:4500,business:8000},options:[]}),
  team: api.calculateProductConfiguration({variant:'Командный',prices:{starter:2500,team:4500,business:8000},options:[{label:'Аналитика',value:1200,checked:true}]}),
  business: api.calculateProductConfiguration({variant:'Бизнес',prices:{starter:2500,team:4500,business:8000},options:[{label:'Аналитика',value:1200,checked:true},{label:'Команда',value:800,checked:true},{label:'Экспорт',value:500,checked:true}]}),
  productInvalid: api.calculateProductConfiguration({variant:'Чужой',prices:{starter:2500,team:4500,business:8000},options:[]}),
  optionInvalid: api.calculateProductConfiguration({variant:'Стартовый',prices:{starter:2500,team:4500,business:8000},options:[{label:'Bad',value:'NaN',checked:true}]})
};
process.stdout.write(JSON.stringify(payload));
JS);

        $process = new Process([$node, $runner]);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame("# macOS\ncomposer require 'simai/docara:^2.0'", $result['installDefault']['command']);
        self::assertSame("# Linux\nphp composer.phar require 'acme/docs:~3.1' --dev --prefer-dist", $result['installAll']['command']);
        self::assertSame("# Windows PowerShell\ncomposer require 'acme/docs:3.0.0' --prefer-dist", $result['installWindows']['command']);
        self::assertSame('PROJECT_INSTALL_INPUT_INVALID', $result['installInvalid']['code']);
        self::assertFalse($result['installInvalid']['ok']);
        self::assertSame(2500, $result['starter']['total']);
        self::assertSame(5700, $result['team']['total']);
        self::assertSame(['Аналитика'], $result['team']['selected']);
        self::assertSame(10500, $result['business']['total']);
        self::assertSame('PROJECT_CONFIG_VARIANT_INVALID', $result['productInvalid']['code']);
        self::assertSame('PROJECT_CONFIG_OPTION_INVALID', $result['optionInvalid']['code']);
        self::assertFalse($result['productInvalid']['ok']);
        self::assertFalse($result['optionInvalid']['ok']);
        self::assertSame('', $process->getErrorOutput());
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
