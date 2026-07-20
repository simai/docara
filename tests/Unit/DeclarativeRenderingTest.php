<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class DeclarativeRenderingTest extends TestCase
{
    public function test_it_renders_the_resolved_plan_through_trusted_presentation_templates(): void
    {
        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse(<<<'MD'
# Installation

Read the [guide](/guide/).

:::ui.alert
{"type":"info","title":"Before <install>","supporting-text":"Create \"a backup\"."}
:::

## Next

Run the installer.
MD, 'content/install.md'),
            'install',
            'Installation',
        );

        $artifact = (new DeclarativePageRenderer(new PortableMarkdownRenderer))->render($plan);

        self::assertStringContainsString('data-docara-declarative-page="install"', $artifact->html);
        foreach (['header', 'sidebar', 'main', 'outline', 'footer'] as $region) {
            self::assertSame(1, substr_count($artifact->html, 'data-docara-region="' . $region . '"'));
        }
        self::assertStringContainsString('data-docara-section="docara.article"', $artifact->html);
        self::assertStringContainsString('<h1 id="installation">Installation</h1>', $artifact->html);
        self::assertStringContainsString('href="/guide/"', $artifact->html);
        self::assertStringContainsString('<sf-alert', $artifact->html);
        self::assertStringContainsString('title="Before &lt;install&gt;"', $artifact->html);
        self::assertStringContainsString('supporting-text=', $artifact->html);
        self::assertStringContainsString('Create "a backup".', $artifact->html);
        self::assertStringNotContainsString('simai.framework.bridge.js', implode(',', $artifact->assets));
        self::assertContains('simai.framework.sf_alert.js', $artifact->assets);
        self::assertSame($plan->canonicalHash(), $artifact->provenance['plan_hash']);
    }

    public function test_template_registry_fails_closed_for_unregistered_template_ids(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_TEMPLATE_NOT_ALLOWED');

        (new TrustedTemplateRegistry)->render('../../etc/passwd', ['view' => new \stdClass]);
    }

    public function test_template_registry_rejects_untyped_context_values(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_TEMPLATE_CONTEXT_INVALID');

        (new TrustedTemplateRegistry)->render('smart.ui.alert.default', ['view' => []]);
    }

    public function test_templates_are_presentation_only_and_assets_are_not_embedded(): void
    {
        $root = dirname(__DIR__, 2) . '/resources';
        $templates = [
            $root . '/layouts/templates/docara.docs.php',
            $root . '/sections/templates/docara.article.php',
            $root . '/smart/ui.alert/templates/default.php',
        ];
        foreach ($templates as $template) {
            $source = (string) file_get_contents($template);
            self::assertStringNotContainsString('<style', $source);
            self::assertStringNotContainsString('<script', $source);
            self::assertStringNotContainsString('function ', $source);
            self::assertStringNotContainsString('new ', $source);
            self::assertStringNotContainsString('require ', $source);
            self::assertStringNotContainsString('include ', $source);
            self::assertStringNotContainsString('file_get_contents', $source);
            self::assertStringNotContainsString('htmlspecialchars', $source);
        }
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
}
