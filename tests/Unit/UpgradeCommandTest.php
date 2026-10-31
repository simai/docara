<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\ProjectUpgradeService;
use Simai\Docara\Console\UpgradeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class UpgradeCommandTest extends TestCase
{
    #[Test]
    public function an_exact_target_conflicts_only_with_apply_and_rollback(): void
    {
        foreach ([['--check' => true], ['--dry-run' => true]] as $action) {
            self::assertStringNotContainsString('UPGRADE_ACTION_CONFLICT', $this->upgrade($action + ['--to' => '3.9.1']));
        }
        foreach ([['--apply' => 'plan'], ['--rollback' => 'latest']] as $action) {
            self::assertStringContainsString('UPGRADE_ACTION_CONFLICT', $this->upgrade($action + ['--to' => '3.9.1']));
        }
    }

    /** @param array<string,mixed> $input */
    private function upgrade(array $input): string
    {
        $application = new Application;
        $application->addCommand((new UpgradeCommand(new ProjectUpgradeService($this->filesystem, dirname(__DIR__, 2))))->setBase($this->tmp));
        $tester = new CommandTester($application->find('upgrade'));
        $tester->execute($input + ['--json' => true]);

        return $tester->getDisplay();
    }
}
