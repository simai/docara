<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;

final class FrameworkTypographyProjectionTest extends TestCase
{
    #[Test]
    public function exact_projection_replaces_only_the_two_framework_stylesheets(): void
    {
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json');
        $repository = FrameworkManifestRepository::bundled($lock);
        $projection = $repository->typographyProjection();

        self::assertIsArray($projection);
        self::assertSame('5.4.0-rc.1', $projection['candidate']);
        self::assertSame('41cc7e01a3616bf245bf054917033397684d2093', $projection['source']['revision']);
        self::assertSame('367b3423f9707b850c6bef9476ab8d1ed44039e1', $projection['builder']['revision']);
        self::assertSame('2b2e6ea88ac5f30dc0c90c61104506e6c9541108', $projection['distribution']['revision']);
        self::assertFalse($projection['distribution']['published']);

        self::assertCount(10, $projection['files']);
        foreach (array_keys($projection['files']) as $key) {
            self::assertSame(
                $projection['files'][$key]['sha256'],
                hash('sha256', $repository->bundledTypographyAsset($key)),
            );
        }

        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework'))->plan([]);
        $assets = array_column($plan->assets, null, 'key');
        foreach (['simai.framework.core.css' => 'core', 'simai.framework.utility.full.css' => 'utility'] as $assetKey => $fileKey) {
            self::assertStringStartsWith('/' . $projection['files'][$fileKey]['public'] . '?sf_v=', $assets[$assetKey]['url']);
            self::assertSame($projection['files'][$fileKey]['sha256'], $assets[$assetKey]['sha256']);
            self::assertSame($projection['distribution']['revision'], $assets[$assetKey]['source_revision']);
        }
        self::assertStringContainsString(
            '@d1daa951dd08b94a9f209fd9f31a78d2b3779563/distr',
            $assets['simai.framework.boot']['content'],
        );

        $nested = (new FrameworkAssetPlanner($repository, '/project~/docs/_docara/framework'))->plan([]);
        $nestedAssets = array_column($nested->assets, null, 'key');
        self::assertStringStartsWith(
            '/project~/docs/_docara/vendor/simai-framework/typography/5.4.0-rc.1/core.css?sf_v=',
            $nestedAssets['simai.framework.core.css']['url'],
        );
    }

    #[Test]
    public function changed_projected_bytes_fail_before_render(): void
    {
        [$root, $lock] = $this->fixture();
        file_put_contents($root . '/resources/portable/vendor/simai-framework/typography/5.4.0-rc.1/core.css', 'changed');

        try {
            new FrameworkManifestRepository($lock, $root . '/resources/framework');
            self::fail('Changed typography bytes were admitted.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_TYPOGRAPHY_ASSET_HASH_MISMATCH', $exception->errorCode);
        } finally {
            $this->removeFixture($root);
        }
    }

    #[Test]
    public function symlink_and_hardlink_projected_assets_fail_closed(): void
    {
        foreach (['symlink', 'hardlink'] as $attack) {
            [$root, $lock] = $this->fixture();
            $core = $root . '/resources/portable/vendor/simai-framework/typography/5.4.0-rc.1/core.css';
            $outside = $root . '/outside.css';
            file_put_contents($outside, file_get_contents($core));
            unlink($core);
            $attack === 'symlink' ? symlink($outside, $core) : link($outside, $core);

            try {
                new FrameworkManifestRepository($lock, $root . '/resources/framework');
                self::fail(ucfirst($attack) . ' typography bytes were admitted.');
            } catch (FrameworkComponentException $exception) {
                self::assertSame('FRAMEWORK_TYPOGRAPHY_ASSET_UNSAFE', $exception->errorCode);
            } finally {
                $this->removeFixture($root);
            }
        }
    }

    /** @return array{string, FrameworkLock} */
    private function fixture(): array
    {
        $root = sys_get_temp_dir() . '/docara-typography-' . bin2hex(random_bytes(8));
        $resources = $root . '/resources';
        mkdir($resources . '/framework', 0777, true);
        mkdir($resources . '/portable/vendor/simai-framework/typography/5.4.0-rc.1', 0777, true);
        copy(dirname(__DIR__, 2) . '/resources/framework/runtime-lock.json', $resources . '/framework/runtime-lock.json');
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json');
        foreach ($lock->typographyProjection()['files'] as $record) {
            $source = dirname(__DIR__, 2) . '/resources/' . $record['path'];
            $target = $resources . '/' . $record['path'];
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
        }

        return [$root, $lock];
    }

    private function removeFixture(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $item->isDir() && ! $item->isLink() ? rmdir($path) : unlink($path);
        }
        rmdir($root);
    }
}
