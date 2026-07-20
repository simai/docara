<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Portable\PortableConfigurationException;

final class DeclarativePageCompilerTest extends TestCase
{
    public function test_it_resolves_page_section_block_and_smart_into_an_immutable_plan(): void
    {
        $document = (new DocumentParser)->parse(<<<'MD'
# Installation

Read the [guide](/guide/).

:::ui.alert
{"type":"info","title":"Before you begin","supporting-text":"Create a backup."}
:::

## Next

Run the installer.
MD, 'content/install.md');

        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())
            ->compile($document, 'install', 'Installation');

        self::assertSame('docara.docs', $plan->layout->key);
        self::assertSame(
            ['header', 'sidebar', 'main', 'outline', 'footer'],
            array_keys($plan->regions),
        );
        self::assertCount(1, $plan->regions['main']);
        $section = $plan->regions['main'][0];
        self::assertSame('docara.article', $section->section);
        self::assertSame('main', $section->region);
        self::assertSame(
            ['content.markdown', 'content.smart', 'content.markdown'],
            array_map(static fn ($block): string => $block->block, $section->blocks),
        );

        $smart = $section->blocks[1]->smart;
        self::assertNotNull($smart);
        self::assertSame('ui.alert', $smart->smart);
        self::assertSame('default', $smart->view);
        self::assertSame('smart.ui.alert.default', $smart->template);
        self::assertSame('Before you begin', $smart->props['aria-label']);
        self::assertMatchesRegularExpression('/^docara-alert-[a-f0-9]{16}$/', $smart->props['id']);
        self::assertContains('simai.framework.sf_alert.js', $plan->assets);
        self::assertNotContains('simai.framework.bridge.js', $plan->assets);
        self::assertSame('docara.resolved_render_plan.v1', $plan->toArray()['schema']);
        self::assertSame(
            $plan->canonicalHash(),
            DeclarativePageCompiler::bundled($this->frameworkLock())
                ->compile($document, 'install', 'Installation')
                ->canonicalHash(),
        );

        $this->expectException(\Error::class);
        $plan->regions['main'] = [];
    }

    public function test_definition_repository_rejects_unregistered_ids(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('DECLARATIVE_DEFINITION_NOT_ALLOWED');

        (new DefinitionRepository)->smartView('ui.alert', '../../template');
    }

    public function test_author_cannot_override_a_managed_smart_property(): void
    {
        $document = (new DocumentParser)->parse(<<<'MD'
:::ui.alert
{"type":"info","title":"Managed","supporting-text":"Invalid.","id":"author-id"}
:::
MD, 'content/managed.md');

        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_PROP_MANAGED');

        DeclarativePageCompiler::bundled($this->frameworkLock())
            ->compile($document, 'managed', 'Managed');
    }

    public function test_bounded_runtime_rejects_an_unsupported_smart_state(): void
    {
        $document = (new DocumentParser)->parse(<<<'MD'
:::ui.alert
{"type":"info","title":"Closable","supporting-text":"Invalid.","closable":true}
:::
MD, 'content/closable.md');

        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME');

        DeclarativePageCompiler::bundled($this->frameworkLock())
            ->compile($document, 'closable', 'Closable');
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
