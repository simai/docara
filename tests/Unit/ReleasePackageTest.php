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

    public function test_artifact_verifier_accepts_packaged_documentation_links(): void
    {
        [$manifest, $root] = $this->releaseFixture('[Guide](docs/guide.md)', true);

        try {
            [$status, $stdout, $stderr] = $this->verifyArtifact($manifest);
            self::assertSame(0, $status, $stderr);
            self::assertStringContainsString('Release package verified:', $stdout);
        } finally {
            $this->deleteDirectory($root);
        }
    }

    public function test_artifact_verifier_rejects_a_broken_readme_link(): void
    {
        [$manifest, $root] = $this->releaseFixture('[Missing](docs/missing.md)', false);

        try {
            [$status, $stdout, $stderr] = $this->verifyArtifact($manifest);
            self::assertSame('', $stdout);
            self::assertSame(1, $status);
            self::assertStringContainsString('Broken local documentation link [README.md -> docs/missing.md]', $stderr);
        } finally {
            $this->deleteDirectory($root);
        }
    }

    /** @return array{string, string} */
    private function releaseFixture(string $readme, bool $includeGuide): array
    {
        $root = sys_get_temp_dir() . '/docara-release-verifier-' . bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $archive = $root . '/fixture.zip';
        $files = [
            'RELEASE-MANIFEST.json' => ['contents' => "{}\n", 'executable' => false],
            'RELEASE-SBOM.cdx.json' => ['contents' => "{}\n", 'executable' => false],
            'LICENSE' => ['contents' => 'MIT', 'executable' => false],
            'README.md' => ['contents' => $readme, 'executable' => false],
            'composer.json' => ['contents' => "{}\n", 'executable' => false],
            'docara' => ['contents' => '#!/usr/bin/env php', 'executable' => true],
        ];
        if ($includeGuide) {
            $files['docs/guide.md'] = ['contents' => '# Guide', 'executable' => false];
        }
        (new DeterministicZipWriter)->write($archive, $files);
        $hashes = [];
        foreach ($files as $path => $file) {
            $hashes[$path] = hash('sha256', $file['contents']);
        }
        $manifest = $root . '/fixture.release-manifest.json';
        file_put_contents($manifest, json_encode([
            'schema' => 'docara.release_artifact_manifest.v1',
            'archive' => basename($archive),
            'archive_sha256' => hash_file('sha256', $archive),
            'file_count' => count($files),
            'files' => $hashes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return [$manifest, $root];
    }

    /** @return array{int, string, string} */
    private function verifyArtifact(string $manifest): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 2) . '/scripts/verify-release-package.php', $manifest],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), (string) $stdout, (string) $stderr];
    }

    private function deleteDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($directory);
    }
}
