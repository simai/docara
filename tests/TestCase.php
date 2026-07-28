<?php

declare(strict_types=1);

namespace Tests;

use Closure;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Simai\Docara\File\Filesystem;

abstract class TestCase extends PHPUnitTestCase
{
    use Haiku;

    protected Filesystem $filesystem;

    protected string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem;
        $this->tmp = __DIR__ . '/fixtures/tmp/' . static::haiku();
        $this->filesystem->ensureDirectoryExists($this->tmp);
    }

    protected function tearDown(): void
    {
        if (isset($this->tmp) && $this->filesystem->isDirectory($this->tmp)) {
            $this->filesystem->deleteDirectory($this->tmp);
        }

        parent::tearDown();
    }

    protected function tmpPath(string $path): string
    {
        return "{$this->tmp}/{$path}";
    }

    /** @param array<string, mixed> $files */
    protected function createSource(array $files): void
    {
        $create = function (string $prefix, array $files, Closure $create): void {
            foreach ($files as $path => $contents) {
                if (is_array($contents)) {
                    $this->filesystem->ensureDirectoryExists("{$prefix}/{$path}");
                    $create("{$prefix}/{$path}", $contents, $create);
                } else {
                    $this->filesystem->ensureDirectoryExists(dirname("{$prefix}/{$path}"));
                    $this->filesystem->put("{$prefix}/{$path}", $contents);
                }
            }
        };

        $create($this->tmp, $files, $create);
    }
}
