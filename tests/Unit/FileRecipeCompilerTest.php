<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeCompiler;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeSnapshotPublisher;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

final class FileRecipeCompilerTest extends TestCase
{
    #[Test]
    public function portable_site_pipeline_publishes_a_recipe_page_transactionally(): void
    {
        $node = (new ExecutableFinder)->find('node');
        $frameworkRoot = getenv('SIMAI_UI_ROOT');
        if (! is_string($node) || ! is_string($frameworkRoot) || $frameworkRoot === '') {
            self::markTestSkipped('The exact Framework candidate is required for the cross-product Recipe check.');
        }

        $project = $this->tmpPath('portable-recipe-project');
        (new Filesystem)->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $project);
        file_put_contents($project . '/content/ru/index.md', <<<'MD'
---
title: Страница из Recipe
description: Проверка штатной сборки.
composition_recipe: composition/descriptor.json
---
MD);
        mkdir($project . '/composition', 0777, true);
        $this->writeJson($project . '/composition/recipe.json', $this->recipe());
        $this->writeJson($project . '/composition/inputs.json', $this->inputs('compact'));
        $this->writeJson($project . '/composition/page.json', $this->template());
        $this->writeJson($project . '/composition/header-compact.json', $this->header('Краткая шапка'));
        $this->writeJson($project . '/composition/header-expanded.json', $this->header('Расширенная шапка'));
        $this->writeJson($project . '/composition/descriptor.json', $this->descriptor());
        $destination = $project . '/build_local';
        $builder = new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            recipeCompiler: FileRecipeCompiler::fromFrameworkDistribution($node, $frameworkRoot),
        );

        $result = $builder->build($project, $destination);
        $page = $result->get('/ru/');
        self::assertIsArray($page);
        self::assertSame('composition_recipe', $page['page_source_kind']);
        $html = (string) file_get_contents($destination . '/ru/index.html');
        self::assertStringContainsString('Краткая шапка', $html);
        self::assertStringContainsString('Файловый контент Docara.', $html);
        self::assertStringNotContainsString('Расширенная шапка', $html);
        $receipt = json_decode(
            (string) file_get_contents($destination . '/.docara/resolved-page-plans.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $home = array_values(array_filter(
            $receipt['pages'],
            static fn (array $record): bool => ($record['url'] ?? null) === '/ru/',
        ))[0];
        self::assertSame('composition/descriptor.json', $home['composition_recipe']['descriptor']);
        self::assertSame(2, count($home['composition_recipe']['dependency_receipt']['references']));
        self::assertDirectoryDoesNotExist($project . '/.docara/composition-recipe');

        $accepted = hash_file('sha256', $destination . '/ru/index.html');
        file_put_contents($project . '/composition/descriptor.json', '{}');
        try {
            $builder->build($project, $destination);
            self::fail('An invalid Recipe unexpectedly replaced the accepted portable site.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PAGE_BUILDER_FAILED', $exception->errorCode);
        }
        self::assertSame($accepted, hash_file('sha256', $destination . '/ru/index.html'));
    }

    #[Test]
    public function it_compiles_only_the_selected_file_variant_with_the_framework_recipe_runtime(): void
    {
        $node = (new ExecutableFinder)->find('node');
        $frameworkRoot = getenv('SIMAI_UI_ROOT');
        if (! is_string($node) || ! is_string($frameworkRoot) || $frameworkRoot === '') {
            self::markTestSkipped('The exact Framework candidate is required for the cross-product Recipe check.');
        }
        $entry = realpath($frameworkRoot . '/distr/core/js/composition/index.mjs');
        if (! is_string($entry)) {
            self::markTestSkipped('The exact Framework candidate entry is unavailable.');
        }

        $project = $this->tmpPath('recipe-project');
        mkdir($project . '/composition', 0777, true);
        $this->writeJson($project . '/composition/recipe.json', $this->recipe());
        $this->writeJson($project . '/composition/inputs.json', $this->inputs('compact'));
        $this->writeJson($project . '/composition/page.json', $this->template());
        $this->writeJson($project . '/composition/header-compact.json', $this->header('Краткая шапка'));
        $this->writeJson($project . '/composition/header-expanded.json', $this->header('Расширенная шапка'));
        $this->writeJson($project . '/composition/descriptor.json', $this->descriptor());

        $compiler = FileRecipeCompiler::fromFrameworkDistribution($node, $frameworkRoot);
        $compact = $compiler->compile($project, 'composition/descriptor.json');
        self::assertStringContainsString('Краткая шапка', $compact['html']);
        self::assertStringNotContainsString('Расширенная шапка', $compact['html']);
        self::assertCount(2, $compact['dependencyReceipt']['references']);

        $this->writeJson($project . '/composition/header-expanded.json', $this->header('Неиспользованный файл изменён'));
        $same = $compiler->compile($project, 'composition/descriptor.json');
        self::assertSame($compact['dependencyReceipt'], $same['dependencyReceipt']);
        self::assertSame($compact['document'], $same['document']);

        $this->writeJson($project . '/composition/inputs.json', $this->inputs('expanded'));
        $expanded = $compiler->compile($project, 'composition/descriptor.json');
        self::assertStringContainsString('Неиспользованный файл изменён', $expanded['html']);
        self::assertNotSame($compact['dependencyReceipt'], $expanded['dependencyReceipt']);
        self::assertNotSame($compact['document'], $expanded['document']);
    }

    #[Test]
    public function it_fails_closed_for_a_stale_framework_or_invalid_selected_source(): void
    {
        $node = (new ExecutableFinder)->find('node');
        $frameworkRoot = getenv('SIMAI_UI_ROOT');
        if (! is_string($node) || ! is_string($frameworkRoot) || $frameworkRoot === '') {
            self::markTestSkipped('The exact Framework candidate is required for the cross-product Recipe check.');
        }
        $entry = realpath($frameworkRoot . '/distr/core/js/composition/index.mjs');
        if (! is_string($entry)) {
            self::markTestSkipped('The exact Framework candidate entry is unavailable.');
        }

        $project = $this->tmpPath('invalid-recipe-project');
        mkdir($project . '/composition', 0777, true);
        $this->writeJson($project . '/composition/recipe.json', $this->recipe());
        $this->writeJson($project . '/composition/inputs.json', $this->inputs('compact'));
        $this->writeJson($project . '/composition/page.json', $this->template());
        file_put_contents($project . '/composition/header-compact.json', '{"id":"header","id":"duplicate"}');
        $this->writeJson($project . '/composition/header-expanded.json', $this->header('Расширенная шапка'));
        $this->writeJson($project . '/composition/descriptor.json', $this->descriptor());

        try {
            (new FileRecipeCompiler($node, $entry, 'sha256:' . str_repeat('0', 64)))->compile($project, 'composition/descriptor.json');
            self::fail('A stale Framework entry was accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('docara_composition_recipe_framework_digest_mismatch', $exception->getMessage());
        }

        $this->expectException(\RuntimeException::class);
        FileRecipeCompiler::fromFrameworkDistribution($node, $frameworkRoot)->compile($project, 'composition/descriptor.json');
    }

    #[Test]
    public function it_activates_and_rolls_back_a_complete_recipe_snapshot_with_revision_control(): void
    {
        $node = (new ExecutableFinder)->find('node');
        $frameworkRoot = getenv('SIMAI_UI_ROOT');
        if (! is_string($node) || ! is_string($frameworkRoot) || $frameworkRoot === '') {
            self::markTestSkipped('The exact Framework candidate is required for the cross-product Recipe check.');
        }
        $entry = realpath($frameworkRoot . '/distr/core/js/composition/index.mjs');
        if (! is_string($entry)) {
            self::markTestSkipped('The exact Framework candidate entry is unavailable.');
        }

        $project = $this->tmpPath('recipe-snapshot-project');
        mkdir($project . '/composition', 0777, true);
        $this->writeJson($project . '/composition/recipe.json', $this->recipe());
        $this->writeJson($project . '/composition/inputs.json', $this->inputs('compact'));
        $this->writeJson($project . '/composition/page.json', $this->template());
        $this->writeJson($project . '/composition/header-compact.json', $this->header('Краткая шапка'));
        $this->writeJson($project . '/composition/header-expanded.json', $this->header('Расширенная шапка'));
        $this->writeJson($project . '/composition/descriptor.json', $this->descriptor());

        $publisher = new FileRecipeSnapshotPublisher(FileRecipeCompiler::fromFrameworkDistribution($node, $frameworkRoot));
        $compact = $publisher->compileAndActivate($project, 'guide.home', 'composition/descriptor.json', null);
        self::assertSame(1, $compact['activation_revision']);
        self::assertStringContainsString('Краткая шапка', $compact['html']);

        $this->writeJson($project . '/composition/inputs.json', $this->inputs('expanded'));
        $expanded = $publisher->compileAndActivate($project, 'guide.home', 'composition/descriptor.json', 1);
        self::assertSame(2, $expanded['activation_revision']);
        self::assertNotSame($compact['snapshot_digest'], $expanded['snapshot_digest']);
        self::assertStringContainsString('Расширенная шапка', $expanded['html']);

        $rolledBack = $publisher->rollback($project, 'guide.home', $compact['snapshot_digest'], 2);
        self::assertSame(3, $rolledBack['activation_revision']);
        self::assertSame($compact['snapshot_digest'], $rolledBack['snapshot_digest']);
        self::assertStringContainsString('Краткая шапка', $publisher->active($project, 'guide.home')['html']);

        try {
            $publisher->compileAndActivate($project, 'guide.home', 'composition/descriptor.json', 2);
            self::fail('A stale activation revision was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('COMPOSITION_RECIPE_ACTIVATION_CONFLICT', $exception->errorCode);
        }
        self::assertSame(3, $publisher->active($project, 'guide.home')['activation_revision']);

        $broken = new FileRecipeSnapshotPublisher(new FileRecipeCompiler($node, $entry, 'sha256:' . str_repeat('0', 64)));
        try {
            $broken->compileAndActivate($project, 'guide.home', 'composition/descriptor.json', 3);
            self::fail('A failed compilation changed the active snapshot.');
        } catch (\RuntimeException $exception) {
            self::assertSame('docara_composition_recipe_framework_digest_mismatch', $exception->getMessage());
        }
        self::assertSame($compact['snapshot_digest'], $publisher->active($project, 'guide.home')['snapshot_digest']);

        $head = $project . '/.docara/composition-recipe/' . hash('sha256', 'guide.home') . '/active.json';
        file_put_contents($head, '{}');
        try {
            $publisher->active($project, 'guide.home');
            self::fail('A malformed active pointer was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('COMPOSITION_RECIPE_HEAD_INVALID', $exception->errorCode);
        }
    }

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        file_put_contents($path, json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string, mixed> */
    private function recipe(): array
    {
        return [
            'schema' => 'simai.composition.recipe.v1',
            'id' => 'docara.file-demo',
            'profile' => 'ui-layout',
            'inputs' => [
                'headerMode' => [
                    'kind' => 'setting',
                    'schema' => ['type' => 'string', 'enum' => ['compact', 'expanded']],
                    'expectedOrigin' => ['owner' => 'docara/settings', 'ref' => 'header.mode', 'revision' => '1'],
                ],
                'body' => [
                    'kind' => 'content',
                    'schema' => ['type' => 'array'],
                    'expectedOrigin' => ['owner' => 'docara/content', 'ref' => 'page.demo', 'revision' => '1'],
                ],
            ],
            'root' => [
                'id' => 'page',
                'ref' => ['kind' => 'template', 'owner' => 'docara/files', 'ref' => 'page', 'policy' => 'pinned', 'revision' => '1'],
                'slots' => [
                    'header' => [[
                        'id' => 'header-choice',
                        'select' => [
                            'value' => ['input' => 'headerMode'],
                            'cases' => [
                                'compact' => ['id' => 'compact', 'ref' => ['kind' => 'fragment', 'owner' => 'docara/files', 'ref' => 'header.compact', 'policy' => 'pinned', 'revision' => '1']],
                                'expanded' => ['id' => 'expanded', 'ref' => ['kind' => 'fragment', 'owner' => 'docara/files', 'ref' => 'header.expanded', 'policy' => 'pinned', 'revision' => '1']],
                            ],
                        ],
                    ]],
                    'main' => [[
                        'id' => 'body',
                        'node' => [
                            'type' => 'content.paragraph',
                            'data' => ['content' => ['input' => 'body']],
                            'bindings' => [['input' => 'body', 'target' => 'content']],
                        ],
                    ]],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function inputs(string $mode): array
    {
        $digest = static fn (mixed $value): string => 'sha256:' . hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $body = [['type' => 'text', 'value' => 'Файловый контент Docara.']];

        return ['schema' => 'simai.composition.inputs.v1', 'scope' => 'docara-site', 'values' => [
            'headerMode' => ['kind' => 'setting', 'value' => $mode, 'valueDigest' => $digest($mode), 'origin' => ['scope' => 'docara-site', 'owner' => 'docara/settings', 'ref' => 'header.mode', 'revision' => '1']],
            'body' => ['kind' => 'content', 'value' => $body, 'valueDigest' => $digest($body), 'origin' => ['scope' => 'docara-site', 'owner' => 'docara/content', 'ref' => 'page.demo', 'revision' => '1']],
        ]];
    }

    /** @return array<string, mixed> */
    private function template(): array
    {
        return ['schema' => 'simai.composition.recipe-manifest.v1', 'id' => 'page', 'parameters' => [], 'slots' => [
            'header' => ['min' => 1, 'max' => 1, 'types' => ['layout.section']], 'main' => ['min' => 1, 'max' => 1, 'types' => ['content.paragraph']],
        ], 'body' => ['id' => 'shell', 'node' => ['type' => 'layout.page', 'slots' => ['default' => [
            ['id' => 'header-slot', 'insertSlot' => 'header'], ['id' => 'main-slot', 'insertSlot' => 'main'],
        ]]]]];
    }

    /** @return array<string, mixed> */
    private function header(string $title): array
    {
        return ['id' => 'header', 'node' => ['type' => 'layout.section', 'slots' => ['default' => [[
            'id' => 'title', 'node' => ['type' => 'content.heading', 'data' => ['level' => ['literal' => 2], 'content' => ['literal' => [['type' => 'text', 'value' => $title]]]]],
        ]]]]];
    }

    /** @return array<string, mixed> */
    private function descriptor(): array
    {
        return ['recipe' => 'composition/recipe.json', 'inputs' => 'composition/inputs.json', 'scope' => 'docara-site', 'executionContract' => [
            'contractDigest' => 'sha256:894de36b030bebdcc539c3616f29f0ca97d20f5aed07b9cd07d0d5448eda1cc2', 'registryDigest' => 'sha256:docara-registry', 'rendererDigest' => 'sha256:docara-renderer',
        ], 'sources' => [
            ['kind' => 'template', 'owner' => 'docara/files', 'ref' => 'page', 'revision' => '1', 'path' => 'composition/page.json'],
            ['kind' => 'fragment', 'owner' => 'docara/files', 'ref' => 'header.compact', 'revision' => '1', 'path' => 'composition/header-compact.json'],
            ['kind' => 'fragment', 'owner' => 'docara/files', 'ref' => 'header.expanded', 'revision' => '1', 'path' => 'composition/header-expanded.json'],
        ]];
    }
}
