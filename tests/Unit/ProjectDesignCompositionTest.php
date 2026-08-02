<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\Design\Registry\DesignRegistry;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Tests\TestCase;

final class ProjectDesignCompositionTest extends TestCase
{
    #[Test]
    public function active_composition_sources_do_not_select_concrete_design_or_smart_ids(): void
    {
        foreach ([
            'src/Declarative/Composition/RegionCompositionResolver.php',
            'src/Declarative/DeclarativePageCompiler.php',
            'src/Declarative/Layout/LayoutDescriptor.php',
            'src/Declarative/Plan/ResolvedBlockFactory.php',
        ] as $relative) {
            $source = (string) file_get_contents(dirname(__DIR__, 2) . '/' . $relative);
            foreach (['docara.docs', 'docara.article', 'shell.element', 'shell.smart', 'ui.alert', 'ui.button'] as $id) {
                self::assertStringNotContainsString($id, $source, "$relative contains central ID [$id].");
            }
        }
    }

    #[Test]
    public function project_layout_section_and_block_run_through_the_production_compiler(): void
    {
        $this->filesystem->copyDirectory(
            dirname(__DIR__) . '/fixtures/design/project',
            $this->tmpPath('design'),
        );
        $definitions = new DefinitionRepository(
            designs: DesignRegistry::bundled($this->tmp, 'acme'),
        );
        $layout = RegionCompositionResolver::defaultsFor('acme.docs', $definitions);
        $plan = DeclarativePageCompiler::bundled(
            $this->frameworkLock(),
            definitions: $definitions,
        )->compile(
            (new DocumentParser)->parse("# Project design\n\nArtifact-only composition.", 'content/acme.md'),
            'acme',
            'Project design',
            layoutConfiguration: $layout,
        );
        $html = (new DeclarativePageRenderer(new PortableMarkdownRenderer))->render($plan)->html;

        self::assertSame('acme.docs', $plan->layout->key);
        self::assertSame(['stage'], array_keys($plan->regions));
        self::assertSame('acme.article', $plan->regions['stage'][0]->section);
        self::assertSame('acme.document', $plan->regions['stage'][0]->blocks[0]->block);
        self::assertStringContainsString('data-docara-region="stage"', $html);
        self::assertStringContainsString('Artifact-only composition.', $html);
        self::assertStringNotContainsString('/Users/', json_encode($plan->provenance, JSON_THROW_ON_ERROR));
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
