<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;

final class AtomicStaticCutoverScriptTest extends TestCase
{
    #[Test]
    public function preflight_cutover_and_rollback_preserve_exact_trees(): void
    {
        $parent = $this->tmpPath('mirror');
        mkdir($parent . '/active', 0777, true);
        mkdir($parent . '/candidate', 0777, true);
        file_put_contents($parent . '/active/index.html', 'current');
        file_put_contents($parent . '/candidate/index.html', 'candidate');
        $current = $this->treeDigest($parent . '/active');
        $candidate = $this->treeDigest($parent . '/candidate');

        foreach (['preflight', 'cutover', 'rollback'] as $command) {
            $process = $this->executeScript($command, $parent, $current, $candidate);
            self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            self::assertStringContainsString('"status": "pass"', $process->getOutput());
        }

        self::assertSame($current, $this->treeDigest($parent . '/active'));
        self::assertSame($candidate, $this->treeDigest($parent . '/candidate'));
        self::assertDirectoryDoesNotExist($parent . '/backup');
    }

    #[Test]
    public function mismatched_digest_and_symlink_fail_closed_without_mutation(): void
    {
        $parent = $this->tmpPath('negative');
        mkdir($parent . '/active', 0777, true);
        mkdir($parent . '/candidate', 0777, true);
        file_put_contents($parent . '/active/index.html', 'current');
        file_put_contents($parent . '/candidate/index.html', 'candidate');
        $current = $this->treeDigest($parent . '/active');
        $candidate = $this->treeDigest($parent . '/candidate');

        $mismatch = $this->executeScript('cutover', $parent, str_repeat('0', 64), $candidate);
        self::assertSame(1, $mismatch->getExitCode());
        self::assertStringContainsString('[CUTOVER_BLOCKED]', $mismatch->getErrorOutput());
        self::assertSame($current, $this->treeDigest($parent . '/active'));
        self::assertSame($candidate, $this->treeDigest($parent . '/candidate'));

        symlink($parent . '/candidate/index.html', $parent . '/candidate/link.html');
        $symlink = $this->executeScript('preflight', $parent, $current, $candidate);
        self::assertSame(1, $symlink->getExitCode());
        self::assertStringContainsString('symlink', $symlink->getErrorOutput());
    }

    private function executeScript(string $command, string $parent, string $current, string $candidate): Process
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__) . '/scripts/atomic-static-cutover.php',
            $command,
            '--parent=' . $parent,
            '--active=active',
            '--candidate=candidate',
            '--backup=backup',
            '--expected-active-sha256=' . $current,
            '--expected-candidate-sha256=' . $candidate,
        ]);
        $process->run();

        return $process;
    }

    private function treeDigest(string $root): string
    {
        $lines = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $lines[$relative] = hash_file('sha256', $file->getPathname()) . '  ' . $relative;
        }
        ksort($lines, SORT_STRING);

        return hash('sha256', implode("\n", $lines) . "\n");
    }
}
