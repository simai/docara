<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeCompiler;
use Simai\Docara\File\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

/**
 * Docara accepts a Framework distribution by the specification it implements,
 * not by the bytes of its runtime. The runtime grows with every Framework pair;
 * the specification stays at its edition. These tests hold that line from both
 * sides: a distribution whose runtime moved is still accepted, and a
 * distribution that merely claims the edition is not.
 */
final class CompositionRecipeSpecificationTest extends TestCase
{
    private const CONTRACT = 'distr/core/contracts/composition-recipe-v1/contract.lock.json';

    /** Copy the real distribution's Recipe surface into a writable fixture. */
    private function distribution(): string
    {
        $frameworkRoot = getenv('SIMAI_UI_ROOT');
        if (! is_string($frameworkRoot) || $frameworkRoot === '' || ! is_file($frameworkRoot . '/' . self::CONTRACT)) {
            self::markTestSkipped('The exact Framework distribution is required for the specification checks.');
        }

        $root = $this->tmpPath('recipe-specification-distribution');
        $files = new Filesystem;
        $files->copyDirectory($frameworkRoot . '/distr/core/js/composition', $root . '/distr/core/js/composition');
        $files->copyDirectory($frameworkRoot . '/distr/core/contracts/composition-recipe-v1', $root . '/distr/core/contracts/composition-recipe-v1');

        return $root;
    }

    private function node(): string
    {
        $node = (new ExecutableFinder)->find('node');
        if (! is_string($node)) {
            self::markTestSkipped('A Node.js binary is required.');
        }

        return $node;
    }

    #[Test]
    public function a_distribution_implementing_the_expected_specification_is_accepted(): void
    {
        $root = $this->distribution();
        $compiler = FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
        self::assertInstanceOf(FileRecipeCompiler::class, $compiler);
    }

    #[Test]
    public function a_runtime_that_moved_on_is_still_accepted_while_the_specification_holds(): void
    {
        $root = $this->distribution();
        // What a new Framework pair does: the runtime grows, the contract does not.
        foreach (['builtins.mjs', 'recipe.mjs'] as $module) {
            $file = $root . '/distr/core/js/composition/' . $module;
            file_put_contents($file, file_get_contents($file) . "\nexport const addedByALaterPair = true;\n");
        }

        $compiler = FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
        self::assertInstanceOf(FileRecipeCompiler::class, $compiler);
    }

    #[Test]
    public function a_distribution_declaring_another_edition_is_refused(): void
    {
        $root = $this->distribution();
        $path = $root . '/' . self::CONTRACT;
        $lock = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $lock['version'] = '0.9.9';
        file_put_contents($path, json_encode($lock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->expectExceptionMessage('docara_composition_recipe_specification_edition_mismatch');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function a_normative_file_edited_behind_the_declared_digest_is_refused(): void
    {
        $root = $this->distribution();
        // The claim stays, the substance changes: exactly what recomputing catches.
        $schema = $root . '/distr/core/contracts/composition-recipe-v1/recipe.schema.json';
        $contents = json_decode((string) file_get_contents($schema), true, 512, JSON_THROW_ON_ERROR);
        $contents['title'] = 'edited behind the digest';
        file_put_contents($schema, json_encode($contents, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->expectExceptionMessage('docara_composition_recipe_specification_digest_mismatch');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function a_distribution_without_the_specification_is_refused(): void
    {
        $root = $this->distribution();
        unlink($root . '/' . self::CONTRACT);

        $this->expectExceptionMessage('docara_composition_recipe_specification_missing');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function a_missing_normative_file_is_refused(): void
    {
        $root = $this->distribution();
        unlink($root . '/distr/core/contracts/composition-recipe-v1/limits.json');

        $this->expectExceptionMessage('docara_composition_recipe_specification_incomplete');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function a_runtime_module_replaced_by_a_symbolic_link_is_refused(): void
    {
        $root = $this->distribution();
        $module = $root . '/distr/core/js/composition/recipe.mjs';
        $target = $root . '/distr/core/js/composition/canonical.mjs';
        unlink($module);
        symlink($target, $module);

        $this->expectExceptionMessage('docara_composition_recipe_distribution_invalid');
        FileRecipeCompiler::fromFrameworkDistribution($this->node(), $root);
    }

    #[Test]
    public function the_lock_names_the_specification_rather_than_runtime_digests(): void
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/resources/contracts/composition/runtime-lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame('docara.composition_runtime_lock.v2', $lock['schema']);
        self::assertArrayNotHasKey('files', $lock, 'the lock must not pin runtime bytes again');
        self::assertSame(self::CONTRACT, $lock['contract']['lock_path']);
        self::assertMatchesRegularExpression('/\A\d+\.\d+\.\d+\z/', $lock['contract']['edition']);
        self::assertMatchesRegularExpression('/\Asha256:[a-f0-9]{64}\z/', $lock['contract']['contract_digest']);
        foreach ($lock['runtime']['modules'] as $module) {
            self::assertStringStartsWith('distr/core/js/composition/', $module);
        }
    }
}
