<?php

declare(strict_types=1);

namespace Tests\Unit;

use RuntimeException;
use Simai\Docara\Release\DeterministicZipWriter;
use Tests\TestCase;
use ZipArchive;

final class ReleasePackageTest extends TestCase
{
    public function test_zip_is_byte_identical_regardless_of_input_order(): void
    {
        $root = sys_get_temp_dir() . '/docara-release-test-' . bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $first = $root . '/first.zip';
        $second = $root . '/second.zip';
        $writer = new DeterministicZipWriter;
        $writer->write($first, [
            'z.txt' => ['contents' => 'last', 'executable' => false],
            'docara' => ['contents' => '#!/usr/bin/env php', 'executable' => true],
        ]);
        $writer->write($second, [
            'docara' => ['contents' => '#!/usr/bin/env php', 'executable' => true],
            'z.txt' => ['contents' => 'last', 'executable' => false],
        ]);

        self::assertSame(hash_file('sha256', $first), hash_file('sha256', $second));
        self::assertSame(file_get_contents($first), file_get_contents($second));

        $archive = new ZipArchive;
        self::assertTrue($archive->open($first, ZipArchive::RDONLY));
        self::assertSame(2, $archive->numFiles);
        self::assertSame('#!/usr/bin/env php', $archive->getFromName('docara'));
        $archive->close();
    }

    public function test_zip_rejects_traversal_paths(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsafe release archive path');

        (new DeterministicZipWriter)->write(
            sys_get_temp_dir() . '/docara-unsafe-release.zip',
            ['../escape' => ['contents' => 'x', 'executable' => false]],
        );
    }
}
