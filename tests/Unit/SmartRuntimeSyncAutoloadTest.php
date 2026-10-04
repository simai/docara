<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * The Smart sync is run by materializers in consumer projects, where Docara
 * sits in vendor/simai/docara and has no vendor directory of its own. 2.14.0
 * required the package's own autoloader there and failed with a PHP fatal.
 */
final class SmartRuntimeSyncAutoloadTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/docara-sync-autoload-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/vendor/simai/docara/scripts', 0755, true);
        copy(
            dirname(__DIR__, 2) . '/scripts/sync-framework-smart-runtime.php',
            $this->root . '/vendor/simai/docara/scripts/sync-framework-smart-runtime.php',
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            '/vendor/simai/docara/scripts/sync-framework-smart-runtime.php',
            '/vendor/autoload.php',
        ] as $file) {
            if (is_file($this->root . $file)) {
                unlink($this->root . $file);
            }
        }
        foreach (['/vendor/simai/docara/scripts', '/vendor/simai/docara', '/vendor/simai', '/vendor', ''] as $directory) {
            if (is_dir($this->root . $directory)) {
                rmdir($this->root . $directory);
            }
        }
    }

    #[Test]
    public function the_sync_uses_the_consumer_autoloader_when_installed_as_a_dependency(): void
    {
        file_put_contents(
            $this->root . '/vendor/autoload.php',
            '<?php return require ' . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ';',
        );

        $process = $this->runSync();

        self::assertSame(2, $process->getExitCode(), $process->getErrorOutput());
        self::assertStringContainsString('Usage:', $process->getErrorOutput());
        self::assertStringNotContainsString('Failed opening required', $process->getErrorOutput());
    }

    #[Test]
    public function the_sync_fails_with_a_named_code_without_any_autoloader(): void
    {
        $process = $this->runSync();

        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('FRAMEWORK_SYNC_AUTOLOAD_UNAVAILABLE', $process->getErrorOutput());
    }

    private function runSync(): Process
    {
        $process = new Process(
            [PHP_BINARY, $this->root . '/vendor/simai/docara/scripts/sync-framework-smart-runtime.php'],
            $this->root,
        );
        $process->run();

        return $process;
    }
}
