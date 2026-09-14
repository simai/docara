<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeCompiler;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

final class FileRecipeCompilerTest extends TestCase
{
    #[Test]
    public function it_compiles_only_the_selected_file_variant_with_the_framework_recipe_runtime(): void
    {
        $node = (new ExecutableFinder)->find('node');
        $frameworkRoot = getenv('SIMAI_UI_SOURCE_ROOT');
        if (! is_string($node) || ! is_string($frameworkRoot) || $frameworkRoot === '') {
            self::markTestSkipped('The exact Framework candidate is required for the cross-product Recipe check.');
        }
        $entry = realpath($frameworkRoot . '/src/core/js/composition/index.mjs');
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

        $compiler = new FileRecipeCompiler($node, $entry, 'sha256:' . hash_file('sha256', $entry));
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
        $frameworkRoot = getenv('SIMAI_UI_SOURCE_ROOT');
        if (! is_string($node) || ! is_string($frameworkRoot) || $frameworkRoot === '') {
            self::markTestSkipped('The exact Framework candidate is required for the cross-product Recipe check.');
        }
        $entry = realpath($frameworkRoot . '/src/core/js/composition/index.mjs');
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
        (new FileRecipeCompiler($node, $entry, 'sha256:' . hash_file('sha256', $entry)))->compile($project, 'composition/descriptor.json');
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
