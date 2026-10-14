<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Console\ApplicationFactory;
use Symfony\Component\Console\Tester\ApplicationTester;

final class ConsoleApplicationTest extends TestCase
{
    #[Test]
    public function running_without_arguments_lists_the_commands(): void
    {
        $tester = $this->tester();

        self::assertSame(0, $tester->run([]));
        $display = $tester->getDisplay();
        foreach (['init', 'build', 'verify-static', 'serve', 'list'] as $command) {
            self::assertMatchesRegularExpression('/^\s+' . preg_quote($command, '/') . '\s/m', $display);
        }
    }

    #[Test]
    public function list_without_a_kind_lists_the_commands_and_with_a_kind_lists_subjects(): void
    {
        $tester = $this->tester();
        self::assertSame(0, $tester->run(['command' => 'list', '--json' => true]));
        $described = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertContains('build', array_column($described['commands'], 'name'));

        $tester->run(['command' => 'list', 'kind' => 'smart', '--json' => true]);
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('list', $result['operation'] ?? null);
    }

    #[Test]
    public function the_version_of_a_checkout_is_the_version_file(): void
    {
        $version = trim((string) file_get_contents(dirname(__DIR__, 2) . '/VERSION'));
        $tester = $this->tester();

        self::assertSame(0, $tester->run(['--version' => true]));
        self::assertStringContainsString('Docara ' . $version, $tester->getDisplay());
        self::assertSame($version, ApplicationFactory::version());
    }

    private function tester(): ApplicationTester
    {
        $application = ApplicationFactory::create(sys_get_temp_dir());
        $application->setAutoExit(false);

        return new ApplicationTester($application);
    }
}
