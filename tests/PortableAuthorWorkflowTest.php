<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;

final class PortableAuthorWorkflowTest extends TestCase
{
    #[Test]
    public function one_markdown_edit_rebuilds_one_route_while_route_structure_requires_a_full_build(): void
    {
        $this->filesystem->copyDirectory(dirname(__DIR__) . '/stubs/portable', $this->tmp);
        $events = [];
        $builder = new PortableSiteBuilder(
            new Filesystem,
            new PortableMarkdownRenderer,
            observer: static function (string $event, string $subject) use (&$events): void {
                $events[] = [$event, $subject];
            },
        );
        $destination = $this->tmpPath('build_author');
        $builder->build($this->tmp, $destination);
        $unchangedRoute = hash_file('sha256', $destination . '/ru/guides/index.html');

        file_put_contents(
            $this->tmpPath('content/ru/index.md'),
            (string) file_get_contents($this->tmpPath('content/ru/index.md')) . "\n\nAuthor workflow marker.\n",
        );
        $events = [];
        $single = $builder->build($this->tmp, $destination, '/ru/');
        self::assertCount(1, $single);
        self::assertSame([['page.build', 'content/ru/index.md']], $events);
        self::assertStringContainsString('Author workflow marker.', (string) file_get_contents($destination . '/ru/index.html'));
        self::assertSame($unchangedRoute, hash_file('sha256', $destination . '/ru/guides/index.html'));

        $accepted = $this->tree($destination);
        file_put_contents($this->tmpPath('content/ru/new-route.md'), "# New route\n\nA physical owner.\n");
        try {
            $builder->build($this->tmp, $destination, '/ru/new-route/');
            self::fail('A structural addition unexpectedly bypassed the full-build gate.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTABLE_PAGE_NOT_FOUND', $exception->errorCode);
            self::assertStringContainsString('Run a full build after structural changes', $exception->getMessage());
        }
        self::assertSame($accepted, $this->tree($destination));

        $builder->build($this->tmp, $destination);
        self::assertFileExists($destination . '/ru/new-route/index.html');
        rename($this->tmpPath('content/ru/new-route.md'), $this->tmpPath('content/ru/renamed-route.md'));
        try {
            $builder->build($this->tmp, $destination, '/ru/renamed-route/');
            self::fail('A structural rename unexpectedly bypassed the full-build gate.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTABLE_PAGE_NOT_FOUND', $exception->errorCode);
        }

        $builder->build($this->tmp, $destination);
        self::assertFileDoesNotExist($destination . '/ru/new-route/index.html');
        self::assertFileExists($destination . '/ru/renamed-route/index.html');
        unlink($this->tmpPath('content/ru/renamed-route.md'));
        $builder->build($this->tmp, $destination);
        self::assertFileDoesNotExist($destination . '/ru/renamed-route/index.html');
    }

    /** @return array<string, string> */
    private function tree(string $root): array
    {
        $hashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $hashes[$relative] = hash_file('sha256', $file->getPathname());
        }
        ksort($hashes, SORT_STRING);

        return $hashes;
    }
}
