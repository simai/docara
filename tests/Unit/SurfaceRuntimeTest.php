<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;
use Simai\Docara\Document\MarkdownCompiler;
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
        self::assertSame('relative_subtree_root_level_1', $surface['container_contract']['depth_semantics']);
        self::assertSame(
            'resources/component-catalog/typed/docara.surface.json',
            $surface['provenance']['definition_ref'],
        );
    }

    #[Test]
    public function actual_surface_document_uses_nested_typed_ir_and_preserves_project_smart_artifacts_once(): void
    {
        $destination = $this->site . '/build_surface-pagebuilder-' . bin2hex(random_bytes(8));
        try {
            $plan = (new PortableConfigurationLoader($this->site))->resolve('content/ru/components/surface.md');
            $site = json_decode((string) file_get_contents($this->site . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
            $project = ProjectSmartRuntime::fromSite($this->site, $site, $plan->frameworkLock);
            self::assertNotNull($project);
            $markdown = new PortableMarkdownRenderer(components: $project->gateway, smartRenderer: $project->renderer);
            $built = (new PageBuilder($markdown, smartRenderer: $project->renderer))->build(
                $plan,
                $this->site,
                FrameworkComponentRuntime::fromLock($plan->frameworkLock),
                3,
            );
            $document = $built->document->toArray();
            $nodes = $document['nodes'] ?? [];
            $containers = array_values(array_filter($nodes, static fn (array $node): bool => ($node['type'] ?? null) === 'container'));
            self::assertNotEmpty($containers);
            $nested = [];
            $walk = function (array $node) use (&$walk, &$nested): void {
                if (($node['type'] ?? null) === 'smart_component') {
                    $nested[] = $node;
                }
                foreach ($node['children'] ?? [] as $child) {
                    if (is_array($child)) {
                        $walk($child);
                    }
                }
            };
            foreach ($containers as $container) {
                $walk($container);
            }
            self::assertCount(1, array_filter($nested, static fn (array $node): bool => ($node['smart'] ?? null) === 'project.product-configurator'));
            $projectArtifacts = array_values(array_filter(
                $built->componentArtifacts,
                static fn ($artifact): bool => ($artifact->hydration['smart'] ?? null) === 'project.product-configurator',
            ));
            self::assertCount(1, $projectArtifacts);
            self::assertCount(2, array_filter(
                $projectArtifacts[0]->assets,
                static fn (string $asset): bool => str_contains($asset, 'project.product-configurator'),
            ));
            self::assertSame('project.project', $projectArtifacts[0]->provenance['provider']);

            (new PortableSiteBuilder(new Filesystem, new PortableMarkdownRenderer))->build($this->site, $destination);

            $html = (string) file_get_contents($destination . '/ru/components/surface/index.html');
            self::assertSame(1, substr_count($html, 'data-project-product-configurator'));
            self::assertSame(1, substr_count($html, '/_docara/smart/project.product-configurator/assets/product-configurator.js'));
            self::assertFileExists($destination . '/_docara/smart/project.product-configurator/assets/product-configurator.js');
            self::assertFileExists($destination . '/_docara/smart/project.product-configurator/assets/product-configurator.css');
        } finally {
            (new Filesystem)->deleteDirectory($destination);
        }
    }

    #[Test]
    public function registry_container_contract_accepts_exact_bounds_and_reports_precise_failures(): void
    {
        foreach ([1, 64] as $count) {
            $cards = implode("\n", array_fill(0, $count, ":::card\nBody.\n:::"));
            $result = $this->buildMarkdown(":::::surface\n$cards\n:::::");
            self::assertSame($count, substr_count($result->contentHtml, 'data-docara-block="card"'));
        }

        $cases = [
            [":::::surface\n:::::", 'DOCUMENT_CONTAINER_CHILD_COUNT_MIN', 1],
            [":::::surface\n" . implode("\n", array_fill(0, 65, ":::card\nBody.\n:::")) . "\n:::::", 'DOCUMENT_CONTAINER_CHILD_COUNT_MAX', 1],
            [":::::surface\n:::hero\n# Hero\n:::\n:::::", 'DOCUMENT_CONTAINER_CHILD_FORBIDDEN', 2],
            [":::::surface\n:::surface\nText\n:::\n:::::", 'DOCUMENT_CONTAINER_CHILD_FORBIDDEN', 2],
            [":::::surface\n:::docara.navigation\n{}\n:::\n:::::", 'DOCUMENT_CONTAINER_SMART_CHILD_FORBIDDEN', 2],
            [":::::surface {padding=huge}\nText\n:::::", 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID', 1],
            [":::::surface {slot=aside}\nText\n:::::", 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN', 1],
            [":::::surface {order=reverse}\nText\n:::::", 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN', 1],
            [":::::surface\n:::project.product-configurator\n{bad}\n:::\n:::::", 'DOCUMENT_SMART_PROPS_JSON_INVALID', 2],
            [":::::surface\nText\n::::", 'DOCUMENT_TYPED_DIRECTIVE_UNCLOSED', 1],
        ];
        foreach ($cases as [$source, $code, $line]) {
            try {
                $this->buildMarkdown($source);
                self::fail("Expected [$code].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $exception->getMessage());
                self::assertTrue($exception->hasFileLocation(), $exception->getMessage());
                self::assertSame('content/ru/components/surface-contract.md', $exception->sourcePath());
                self::assertSame($line, $exception->sourceLine());
                self::assertSame(1, $exception->sourceColumn());
            }
        }

        self::assertStringNotContainsString(
            "supportsCapability(\$child->smart, 'content.embeddable')",
            (string) file_get_contents(dirname(__DIR__, 2) . '/src/Document/ContainerContractValidator.php'),
        );
    }

    #[Test]
    public function production_page_builder_accepts_canonical_surface_grid_card_relative_depth(): void
    {
        $source = implode("\n", [
            '::::::surface',
            ':::::grid {columns=1}',
            '::::card',
            'Body.',
            '::::',
            ':::::',
            '::::::',
        ]);
        $built = $this->buildMarkdown($source);

        $document = $built->document->toArray();
        self::assertSame('container', $document['nodes'][0]['type'] ?? null);
        self::assertSame('surface', $document['nodes'][0]['alias'] ?? null);
        self::assertSame('grid', $document['nodes'][0]['children'][0]['alias'] ?? null);
        self::assertSame('card', $document['nodes'][0]['children'][0]['children'][0]['alias'] ?? null);
        self::assertStringContainsString('data-docara-surface', $built->contentHtml);
        self::assertStringContainsString('data-docara-block="grid"', $built->contentHtml);
        self::assertStringContainsString('data-docara-block="card"', $built->contentHtml);
        self::assertStringContainsString('Body.', $built->contentHtml);

        $direct = (new PortableMarkdownRenderer)->render($source);
        self::assertStringContainsString('data-docara-block="surface"', $direct);
        self::assertStringContainsString('data-docara-block="grid"', $direct);
        self::assertStringContainsString('data-docara-block="card"', $direct);
        self::assertSame(
            ['content.embeddable'],
            TypedComponentDefinitionRepository::bundled()->allowedChildCapabilities('surface'),
        );
    }

    #[Test]
    public function production_page_builder_rejects_one_level_beyond_registry_relative_depth(): void
    {
        $filesystem = new Filesystem;
        $root = sys_get_temp_dir() . '/docara-container-depth-' . bin2hex(random_bytes(8));
        $definitionsRoot = $root . '/typed';
        self::assertTrue($filesystem->copyDirectory(
            dirname(__DIR__, 2) . '/resources/component-catalog/typed',
            $definitionsRoot,
        ));
        try {
            $gridPath = $definitionsRoot . '/docara.grid.json';
            $grid = json_decode((string) file_get_contents($gridPath), true, 512, JSON_THROW_ON_ERROR);
            $grid['container_contract']['max_depth'] = 1;
            file_put_contents($gridPath, json_encode($grid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            $definitions = new TypedComponentDefinitionRepository($definitionsRoot);
            $project = $this->projectRuntime();
            $compiler = new MarkdownCompiler(typedComponents: $definitions, smarts: $project->gateway);

            try {
                $this->buildMarkdown(implode("\n", [
                    '::::::surface',
                    ':::::grid {columns=1}',
                    '::::card',
                    'Body.',
                    '::::',
                    ':::::',
                    '::::::',
                ]), $compiler, $definitions);
                self::fail('Expected relative max_depth overflow.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('DOCUMENT_CONTAINER_DEPTH_EXCEEDED', $exception->errorCode);
                self::assertSame('content/ru/components/surface-contract.md', $exception->sourcePath());
                self::assertSame(3, $exception->sourceLine());
                self::assertSame(1, $exception->sourceColumn());
            }
        } finally {
            $filesystem->deleteDirectory($root);
        }
    }

    private function buildMarkdown(
        string $markdown,
        ?MarkdownCompiler $compiler = null,
        ?TypedComponentDefinitionRepository $definitions = null,
    ): PageBuilderResult {
        $base = (new PortableConfigurationLoader($this->site))->resolve('content/ru/components/surface.md');
        $site = json_decode((string) file_get_contents($this->site . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
        $project = $this->projectRuntime($base->frameworkLock);
        $plan = new ResolvedPagePlan(
            'content/ru/components/surface-contract.md',
            $markdown,
            $base->configuration,
            $base->frameworkLock,
            $base->trace,
            $base->provenance,
        );
        $renderer = new PortableMarkdownRenderer(
            definitions: $definitions,
            components: $project->gateway,
            smartRenderer: $project->renderer,
        );

        return (new PageBuilder($renderer, compiler: $compiler, smartRenderer: $project->renderer))->build(
            $plan,
            $this->site,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );
    }

    /** @param array<string,mixed>|null $frameworkLock */
    private function projectRuntime(?array $frameworkLock = null): ProjectSmartRuntime
    {
        $site = json_decode((string) file_get_contents($this->site . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
        $frameworkLock ??= (new PortableConfigurationLoader($this->site))
            ->resolve('content/ru/components/surface.md')->frameworkLock;
        $project = ProjectSmartRuntime::fromSite($this->site, $site, $frameworkLock);
        self::assertNotNull($project);

        return $project;
    }
}
