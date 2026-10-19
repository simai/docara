<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeCompiler;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

/**
 * Docara accepts the Recipe specification editions it is proven compatible
 * with, each by its exact contract digest, and nothing else.
 *
 * 1.0.1 is the edition of the bundled Framework release. 1.1.0 (simai/ui
 * 7493766f, release ui-7493766fe39e) lets the five recipe formats and the
 * canonical-json profile be named without a version; recipes in the older form
 * resolve to the same bytes. tests/fixtures/framework/composition-recipe-1.1.0
 * holds that release's Recipe runtime and specification, laid out as a
 * Framework root, so both editions run here without a Framework checkout.
 */
final class CompositionRecipeEditionsTest extends TestCase
{
    private const CONTRACT = 'distr/core/contracts/composition-recipe-v1/contract.lock.json';

    private const EDITION_1_0_1 = 'sha256:63daf55cb7d7c39d55f59a5ba3f5d7e511ace3f16d344879cbc877782ff3dcfd';

    private const EDITION_1_1_0 = 'sha256:b9eba08bffbdffcb17e2af9d1349764fc8ef279828c44af6a02fd02ce7a5d649';

    #[Test]
    public function the_lock_accepts_exactly_the_proven_editions_with_their_digests(): void
    {
        $lock = $this->json(dirname(__DIR__, 2) . '/resources/contracts/composition/runtime-lock.json');

        self::assertSame('docara.composition_runtime_lock.v3', $lock['schema']);
        self::assertSame([
            ['edition' => '1.0.1', 'contract_digest' => self::EDITION_1_0_1],
            ['edition' => '1.1.0', 'contract_digest' => self::EDITION_1_1_0],
        ], $lock['contract']['editions']);
        self::assertArrayNotHasKey('edition', $lock['contract']);
        self::assertArrayNotHasKey('contract_digest', $lock['contract']);
    }

    #[Test]
    public function both_editions_are_accepted_and_each_records_its_own_digest(): void
    {
        foreach ([[$this->edition101(), '1.0.1', self::EDITION_1_0_1], [$this->edition110(), '1.1.0', self::EDITION_1_1_0]] as [$root, $edition, $digest]) {
            $declared = $this->json($root . '/' . self::CONTRACT);
            self::assertSame($edition, $declared['version']);
            self::assertSame($digest, $declared['contractDigest']);

            $receipt = $this->buildSite(FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root))['receipt'];
            foreach ($receipt['pages'] as $page) {
                self::assertSame(
                    $digest,
                    $page['declarative_pipeline']['composition_recipe_primary']['dependency_receipt']['executionContract']['contractDigest'],
                    "the execution contract of {$page['url']} must name the edition that ran",
                );
            }
        }
    }

    #[Test]
    public function an_edition_outside_the_list_is_refused(): void
    {
        $root = $this->writable($this->edition110());
        $this->rewriteLock($root, ['version' => '1.2.0']);

        $this->expectExceptionMessage('docara_composition_recipe_specification_edition_mismatch');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function an_accepted_edition_with_another_editions_digest_is_refused(): void
    {
        foreach ([
            [$this->edition110(), ['contractDigest' => self::EDITION_1_0_1]],
            [$this->edition101(), ['contractDigest' => self::EDITION_1_1_0]],
            [$this->edition110(), ['version' => '1.0.1']],
            [$this->edition110(), ['contractDigest' => 'sha256:' . str_repeat('0', 64)]],
        ] as $index => [$source, $change]) {
            $root = $this->writable($source, "wrong-digest-{$index}");
            $this->rewriteLock($root, $change);
            try {
                FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
                self::fail('A lock pairing an edition with a digest that is not its own was accepted.');
            } catch (\RuntimeException $exception) {
                self::assertSame('docara_composition_recipe_specification_edition_mismatch', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function an_edition_claimed_over_the_other_editions_files_is_refused(): void
    {
        // A 1.1.0 distribution relabelled as 1.0.1, digest included: the claim
        // matches the list, the normative files do not.
        $root = $this->writable($this->edition110());
        $this->rewriteLock($root, ['version' => '1.0.1', 'contractDigest' => self::EDITION_1_0_1]);

        $this->expectExceptionMessage('docara_composition_recipe_specification_digest_mismatch');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function an_empty_or_malformed_edition_list_accepts_nothing(): void
    {
        $root = $this->edition110();
        $entry = $root . '/distr/core/js/composition/index.mjs';
        foreach ([
            [],
            [['edition' => '1.1.0']],
            [['edition' => '1.1', 'contract_digest' => self::EDITION_1_1_0]],
            [['edition' => '1.1.0', 'contract_digest' => self::EDITION_1_1_0], ['edition' => '1.1.0', 'contract_digest' => self::EDITION_1_0_1]],
        ] as $editions) {
            $compiler = new FileRecipeCompiler($this->node(), $entry, frameworkRoot: $root, recipeContract: [
                'lock_path' => self::CONTRACT,
                'lock_schema' => 'simai.composition.recipe-contract-lock.v1',
                'editions' => $editions,
            ]);
            try {
                $compiler->compile($this->tmp, 'composition/descriptor.json');
                self::fail('A malformed edition list accepted a distribution.');
            } catch (\RuntimeException $exception) {
                self::assertSame('docara_composition_recipe_specification_edition_mismatch', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function a_distribution_swapped_for_another_accepted_edition_mid_build_is_refused(): void
    {
        $root = $this->writable($this->edition101());
        $compiler = FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
        $project = $this->headerSwitchProject('header-switch', 'compact', false);
        $compiler->compile($project, 'composition/descriptor.json');

        // The recorded execution contract names 1.0.1; a 1.1.0 runtime must not
        // continue under it.
        $compiler->close();
        (new Filesystem)->deleteDirectory($root . '/distr');
        (new Filesystem)->copyDirectory($this->edition110() . '/distr', $root . '/distr');

        $this->expectExceptionMessage('docara_composition_recipe_specification_edition_mismatch');
        $compiler->compile($project, 'composition/descriptor.json');
    }

    #[Test]
    public function an_older_form_recipe_resolves_to_the_specified_bytes_under_both_editions(): void
    {
        $fixture = $this->headerSwitch();
        foreach (['compact', 'expanded'] as $mode) {
            $project = $this->headerSwitchProject("older-{$mode}", $mode, false);
            $results = [];
            foreach (['1.0.1' => $this->edition101(), '1.1.0' => $this->edition110()] as $edition => $root) {
                $results[$edition] = FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root)->compile($project, 'composition/descriptor.json');
                // The Framework's own normative result, unchanged since 1.0.1.
                self::assertSame(self::sorted($fixture['expected'][$mode]['document']), self::sorted($results[$edition]['document']), "{$mode} document under {$edition}");
                self::assertSame(self::sorted($fixture['expected'][$mode]['dependencyReceipt']), self::sorted($results[$edition]['dependencyReceipt']), "{$mode} receipt under {$edition}");
            }
            self::assertSame(json_encode($results['1.0.1']), json_encode($results['1.1.0']), "{$mode}: document, receipt and HTML are byte-identical");
        }
    }

    #[Test]
    public function a_recipe_named_without_versions_resolves_under_1_1_0_to_the_specified_digests(): void
    {
        $bare = $this->json($this->edition110() . '/distr/core/contracts/composition-recipe-v1/fixtures/format-names.json')['bareResolution'];
        foreach (['compact', 'expanded'] as $mode) {
            $project = $this->headerSwitchProject("bare-{$mode}", $mode, true);
            $result = FileRecipeCompiler::fromFrameworkDistribution($this->node(), $this->edition110())->compile($project, 'composition/descriptor.json');
            $expected = $bare[$mode];
            $receipt = $result['dependencyReceipt'];
            self::assertSame($expected['documentSchema'], array_intersect_key($result['document'], ['schema' => true, 'schema_version' => true]));
            self::assertSame($expected['receiptSchema'], array_intersect_key($receipt, ['schema' => true, 'schema_version' => true]));
            self::assertSame($expected['canonicalization'], $receipt['executionContract']['canonicalization']);
            self::assertSame($expected['recipeDigest'], $receipt['recipeDigest']);
            self::assertSame($expected['documentDigest'], $receipt['documentDigest']);
            self::assertSame(
                $expected['references'],
                array_map(static fn (array $reference): array => ['ref' => $reference['ref'], 'digest' => $reference['digest']], $receipt['references']),
            );
            self::assertStringContainsString($mode === 'compact' ? 'Краткая шапка' : 'Расширенная шапка', $result['html']);

        }
    }

    #[Test]
    public function a_site_builds_to_the_same_bytes_under_both_editions_except_the_recorded_digest(): void
    {
        $older = $this->buildSite(FileRecipeCompiler::fromFrameworkDistribution($this->node(), $this->edition101()), 'site-1.0.1');
        $newer = $this->buildSite(FileRecipeCompiler::fromFrameworkDistribution($this->node(), $this->edition110()), 'site-1.1.0');

        self::assertSame($older['html'], $newer['html']);
        $normalize = static function (array $receipt): string {
            // The recorded digest names the edition that ran; the plan hash
            // covers the plan with that receipt. Nothing else may differ.
            foreach ($receipt['pages'] as &$page) {
                unset(
                    $page['declarative_pipeline']['composition_recipe_primary']['dependency_receipt']['executionContract']['contractDigest'],
                    $page['declarative_pipeline']['plan_hash'],
                );
            }

            return (string) json_encode($receipt['pages']);
        };
        self::assertSame($normalize($older['receipt']), $normalize($newer['receipt']));
    }

    /** @return array{html: array<string, string>, receipt: array<string, mixed>} */
    private function buildSite(FileRecipeCompiler $compiler, string $name = 'site'): array
    {
        $project = $this->tmpPath($name);
        $files = new Filesystem;
        $files->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $project);
        $example = dirname(__DIR__, 2) . '/examples/composition-recipe-site';
        $files->copyDirectory($example . '/content', $project . '/content');
        $files->copyDirectory($example . '/composition', $project . '/composition');
        $destination = $project . '/build_local';
        (new PortableSiteBuilder($files, new PortableMarkdownRenderer, recipeCompiler: $compiler))->build($project, $destination);

        $html = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($destination, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with($file->getPathname(), '.html')) {
                $html[substr($file->getPathname(), strlen($destination))] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($html);

        return ['html' => $html, 'receipt' => $this->json($destination . '.docara/resolved-page-plans.json')];
    }

    /**
     * The Framework's header-switch fixture as a Docara file recipe: the
     * recipe, its template and fragments as project files, the descriptor
     * carrying the fixture's execution contract. With $bare the recipe, the
     * template manifest, the inputs envelope and the profile are named without
     * a version, as format-names.json resolves them.
     */
    private function headerSwitchProject(string $name, string $mode, bool $bare): string
    {
        // Decoded as objects, so empty objects stay objects and every source
        // keeps the bytes its digest is specified over.
        $fixture = json_decode((string) file_get_contents($this->headerSwitchPath()), false, 512, JSON_THROW_ON_ERROR);
        $recipe = $fixture->recipe;
        $article = $fixture->sources->article;
        $inputs = $fixture->inputs->{$mode};
        $executionContract = clone $fixture->expected->{$mode}->dependencyReceipt->executionContract;
        unset($executionContract->canonicalization);
        $named = static function (object $value, string $name): object {
            $rest = clone $value;
            unset($rest->schema);

            return (object) (['schema' => $name, 'schema_version' => 1] + get_object_vars($rest));
        };
        if ($bare) {
            $recipe = $named($recipe, 'simai.composition.recipe');
            $article = $named($article, 'simai.composition.recipe-manifest');
            $inputs = $named($inputs, 'simai.composition.inputs');
            $executionContract->canonicalization = 'simai.recipe.canonical-json';
        }

        $project = $this->tmpPath($name);
        mkdir($project . '/composition', 0777, true);
        $write = static fn (string $file, mixed $value) => file_put_contents(
            $project . '/composition/' . $file,
            json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
        $write('recipe.json', $recipe);
        $write('inputs.json', $inputs);
        $write('article.json', $article);
        $write('header-compact.json', $fixture->sources->{'header.compact'});
        $write('header-expanded.json', $fixture->sources->{'header.expanded'});
        $write('descriptor.json', [
            'recipe' => 'composition/recipe.json',
            'inputs' => 'composition/inputs.json',
            'scope' => $fixture->trustedContext->scope,
            'executionContract' => $executionContract,
            'sources' => [
                ['kind' => 'template', 'owner' => 'example/library', 'ref' => 'article', 'revision' => 'r1', 'path' => 'composition/article.json'],
                ['kind' => 'fragment', 'owner' => 'example/library', 'ref' => 'header.compact', 'revision' => 'r1', 'path' => 'composition/header-compact.json'],
                ['kind' => 'fragment', 'owner' => 'example/library', 'ref' => 'header.expanded', 'revision' => 'r1', 'path' => 'composition/header-expanded.json'],
            ],
        ]);

        return $project;
    }

    /** @return array<string, mixed> */
    private function headerSwitch(): array
    {
        return $this->json($this->headerSwitchPath());
    }

    private function headerSwitchPath(): string
    {
        return $this->edition110() . '/distr/core/contracts/composition-recipe-v1/fixtures/header-switch.json';
    }

    /** Key order is not part of a JSON value; the specified results are compared as values. */
    private static function sorted(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(self::sorted(...), $value);
    }

    private function edition101(): string
    {
        $root = FileRecipeCompiler::bundledFrameworkRoot();
        if (! is_string($root) || ($this->json($root . '/' . self::CONTRACT)['version'] ?? null) !== '1.0.1') {
            self::markTestSkipped('The bundled Framework release implements Recipe 1.0.1; this package carries another.');
        }

        return $root;
    }

    private function edition110(): string
    {
        return dirname(__DIR__) . '/fixtures/framework/composition-recipe-1.1.0';
    }

    private function writable(string $source, string $name = 'distribution'): string
    {
        $root = $this->tmpPath($name);
        (new Filesystem)->copyDirectory($source . '/distr', $root . '/distr');

        return $root;
    }

    /** @param array<string, mixed> $change */
    private function rewriteLock(string $root, array $change): void
    {
        $path = $root . '/' . self::CONTRACT;
        file_put_contents($path, json_encode(array_replace($this->json($path), $change), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function node(): string
    {
        $node = (new ExecutableFinder)->find('node');
        if (! is_string($node)) {
            self::markTestSkipped('A Node.js binary is required.');
        }

        return $node;
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($value);

        return $value;
    }
}
