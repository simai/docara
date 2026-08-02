<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\File\Filesystem;

final readonly class PortableProjectInitializer
{
    public function __construct(private Filesystem $files) {}

    /**
     * @return array{copied:int,preserved:int,engine_files:int,engine_sha256:string}
     */
    public function initialize(string $root): array
    {
        $stubs = dirname(__DIR__, 2) . '/stubs/portable';
        if (! $this->files->isDirectory($stubs)) {
            throw new RuntimeException("Portable scaffold was not found at {$stubs}.");
        }

        $root = rtrim($root, '/\\');
        $copied = 0;
        $preserved = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($stubs, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($stubs))), '/');
            $destination = $root . '/' . $relative;
            if ($this->files->exists($destination)) {
                $preserved++;

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($destination));
            $this->files->copy($file->getPathname(), $destination);
            $copied++;
        }

        $engine = (new PortableProjectUpdater($this->files))->installEngineState($root);

        return [
            'copied' => $copied,
            'preserved' => $preserved,
            'engine_files' => (int) $engine['engine_files'],
            'engine_sha256' => (string) $engine['current_sha256'],
        ];
    }
}
