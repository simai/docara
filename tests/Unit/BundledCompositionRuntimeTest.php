<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\Declarative\Composition\Recipe\FileRecipeCompiler;

/**
 * Docara bundles the Composition Recipe runtime of the Framework release it
 * pins, so a build needs no Framework checkout.
 */
final class BundledCompositionRuntimeTest extends TestCase
{
    #[Test]
    public function the_bundled_runtime_belongs_to_the_pinned_release(): void
    {
        $root = FileRecipeCompiler::bundledFrameworkRoot();
        $runtimeLock = $this->json(dirname(__DIR__, 2) . '/resources/framework/runtime-lock.json');

        self::assertIsString($root);
        self::assertSame($runtimeLock['ui']['commit'], basename($root));
        $manifest = $this->json($root . '/runtime-manifest.json');
        self::assertSame('docara.framework_composition_runtime.v1', $manifest['schema']);
        self::assertSame('simai/ui', $manifest['source']['provider']);
        self::assertSame($runtimeLock['ui']['commit'], $manifest['source']['revision']);
        self::assertSame($runtimeLock['ui']['sha256'], $manifest['source']['tree_sha256']);
    }

    #[Test]
    public function every_bundled_file_matches_the_manifest_and_nothing_else_is_there(): void
    {
        $root = (string) FileRecipeCompiler::bundledFrameworkRoot();
        $manifest = $this->json($root . '/runtime-manifest.json');
        $ledger = '';
        foreach ($manifest['files'] as $path => $record) {
            $file = $root . '/' . $path;
            self::assertFileExists($file);
            self::assertFalse(is_link($file), $path);
            self::assertSame($record['sha256'], hash_file('sha256', $file), $path);
            $ledger .= $record['sha256'] . '  ' . $path . "\n";
        }
        self::assertSame($manifest['packet_sha256'], hash('sha256', $ledger));

        $onDisk = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            $onDisk[] = substr($file->getPathname(), strlen($root) + 1);
        }
        sort($onDisk, SORT_STRING);
        $expected = [...array_keys($manifest['files']), 'runtime-manifest.json'];
        sort($expected, SORT_STRING);
        self::assertSame($expected, $onDisk);
        self::assertSame([basename($root)], array_values(array_diff(scandir(dirname($root)) ?: [], ['.', '..'])));
    }

    #[Test]
    public function the_bundled_runtime_implements_the_expected_recipe_specification(): void
    {
        $recipeLock = $this->json(dirname(__DIR__, 2) . '/resources/contracts/composition/runtime-lock.json');
        $root = (string) FileRecipeCompiler::bundledFrameworkRoot();

        // The specification and every locked module are checked when the
        // compiler is created; Node.js is only started by a request.
        self::assertInstanceOf(FileRecipeCompiler::class, FileRecipeCompiler::fromFrameworkDistribution('node', $root));
        foreach ([$recipeLock['runtime']['entry'], ...$recipeLock['runtime']['modules']] as $module) {
            self::assertFileExists($root . '/' . $module);
        }
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($value);

        return $value;
    }
}
