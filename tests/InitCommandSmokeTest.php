<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InitCommandSmokeTest extends TestCase
{
    #[Test]
    public function init_update_respects_custom_docs_dir_and_copies_core(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-smoke-' . bin2hex(random_bytes(4));
        $docsDir = $tmp . '/source/customdocs';
        $coreFile = $tmp . '/source/_core/bootstrap.php';
        $this->assertTrue(mkdir($docsDir, 0777, true));

        $original = "ORIGINAL\n";
        file_put_contents($docsDir . '/index.md', $original);

        file_put_contents($tmp . '/.env', <<<ENV
DOCS_DIR=customdocs
AZURE_KEY=
AZURE_REGION=
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
ENV);

        try {
            $binary = realpath(__DIR__ . '/../vendor/bin/docara') ?: 'vendor/bin/docara';
            $env = ['APP_ENV' => 'test', 'DOCARA_SKIP_FRONTEND_INSTALL' => 'true'];
            $process = new Process(['php', $binary, 'init', '--update'], $tmp, $env);
            $process->run();

            $this->assertTrue($process->isSuccessful(), "docara init --update failed: {$process->getErrorOutput()} {$process->getOutput()}");
            $this->assertSame($original, file_get_contents($docsDir . '/index.md'), 'Existing docs were overwritten');
            $this->assertFileExists($coreFile, 'Bundled _core was not copied');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }
}
