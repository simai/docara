<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\DocumentRenderContext;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\MarkdownCompiler;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\Smart\SmartRegistry;

final class ProjectLocalSmartRuntimeTest extends TestCase
{
    public function test_portable_project_artifact_renders_through_typed_ir_registry_and_gateway(): void
    {
        $root = dirname(__DIR__) . '/fixtures/smart/portable';
        $registry = SmartRegistry::withProject('fixture', $root, 'fixture-revision-v1');
        $gateway = SmartComponentGateway::withProject($registry, 'project.fixture', $this->frameworkLock());
        $templates = new TrustedTemplateRegistry(smarts: $registry);
        $smartRenderer = new SmartRenderer($templates);
        $markdown = new PortableMarkdownRenderer(components: $gateway);
        $renderers = DocumentRendererRegistry::bundled($markdown, $smartRenderer);
        $document = (new MarkdownCompiler)->compile(<<<'MD'
# Local component

:::fixture.notice
{"title":"Portable","text":"No engine registration."}
:::
MD, 'content/local.md');

        $rendered = $renderers->render($document, new DocumentRenderContext($root, $root . '/content/local.md'));

        self::assertSame(['heading', 'smart_component'], array_map(static fn ($node): string => $node->type(), $document->nodes));
        self::assertStringContainsString('data-fixture-notice', $rendered['document']->html);
        self::assertStringContainsString('Portable', $rendered['document']->html);
        self::assertStringContainsString('No engine registration.', $rendered['document']->html);
        self::assertContains('project.fixture.fixture.notice.css.notice.css', $rendered['document']->assets);
        self::assertSame('project.fixture', $rendered['components'][0]->hydration['owner']);
    }

    /** @return array<string,mixed> */
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
