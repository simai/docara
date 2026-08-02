<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\InitCommand;
use Simai\Docara\Console\UpdateCommand;
use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Simai\Docara\PortableSite\PortableProjectUpdater;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class PortableUpdateCommandTest extends TestCase
{
    #[Test]
    public function verify_and_json_output_are_machine_readable_for_a_clean_initialized_project(): void
    {
        $project = $this->initializeProject();

        [$status, $console] = $this->executeUpdate($project, ['--verify' => true, '--json' => true]);

        self::assertSame(Command::SUCCESS, $status, $console->getDisplay());
        $result = json_decode(trim($console->getDisplay()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('docara.update_verification.v1', $result['schema']);
        self::assertSame('current', $result['status']);
        self::assertTrue($result['project_owned_preserved']);
        self::assertGreaterThan(20, $result['engine_files']);
    }

    #[Test]
    public function dry_run_apply_and_rollback_update_only_engine_owned_state(): void
    {
        $project = $this->initializeProject();
        $package = $this->copyPackage();
        $page = $project . '/content/ru/index.md';
        $config = $project . '/docara.json';
        $pageBytes = "# User page\n";
        $configBytes = (string) file_get_contents($config);
        file_put_contents($page, $pageBytes);

        $schema = $package . '/resources/schemas/page.schema.json';
        file_put_contents($schema, (string) file_get_contents($schema) . "\n");
        $updater = new PortableProjectUpdater($this->filesystem, $package);

        $plan = $updater->dryRun($project);
        self::assertSame('docara.update_plan.v1', $plan['schema']);
        self::assertNotEmpty($plan['operations']);
        self::assertFileExists($project . '/.docara/update-plan.json');
        self::assertSame($pageBytes, file_get_contents($page));
        self::assertSame($configBytes, file_get_contents($config));

        $applied = $updater->apply($project);
        self::assertSame('applied', $applied['status']);
        self::assertMatchesRegularExpression('/^[0-9]{14}-[a-f0-9]{12}$/', $applied['rollback_id']);
        self::assertFileExists($project . '/.docara/rollbacks/' . $applied['rollback_id'] . '/manifest.json');
        self::assertFileExists($project . '/.docara/rollbacks/' . $applied['rollback_id'] . '/simai-framework.lock.json');
        self::assertSame($pageBytes, file_get_contents($page));
        self::assertSame($configBytes, file_get_contents($config));

        $rolledBack = $updater->rollback($project, $applied['rollback_id']);
        self::assertSame('rolled_back', $rolledBack['status']);
        self::assertSame($pageBytes, file_get_contents($page));
        self::assertSame($configBytes, file_get_contents($config));
    }

    #[Test]
    public function apply_refuses_a_stale_plan_and_leaves_everything_unchanged(): void
    {
        $project = $this->initializeProject();
        $updater = new PortableProjectUpdater($this->filesystem);
        $updater->dryRun($project);
        $engineBefore = $this->treeHash($project . '/.docara/engine');
        $config = $project . '/docara.json';
        file_put_contents($config, (string) file_get_contents($config) . "\n");

        try {
            $updater->apply($project);
            self::fail('A stale plan must fail closed.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('inputs changed after dry-run', $exception->getMessage());
        }
        self::assertSame($engineBefore, $this->treeHash($project . '/.docara/engine'));
        self::assertDirectoryDoesNotExist($project . '/.docara/rollbacks');
    }

    #[Test]
    public function verify_refuses_dirty_unknown_and_symlinked_engine_state(): void
    {
        $project = $this->initializeProject();
        $updater = new PortableProjectUpdater($this->filesystem);
        $packageRevision = $project . '/.docara/engine/package-revision.json';
        file_put_contents($packageRevision, "{}\n");
        $this->assertUpdateFailure($updater, $project, 'Dirty package-owned file');

        $project = $this->initializeProject('unknown-project');
        file_put_contents($project . '/.docara/engine/unknown.txt', 'unknown');
        $this->assertUpdateFailure($updater, $project, 'Unknown or missing package-owned file');

        if (function_exists('symlink')) {
            $project = $this->initializeProject('symlink-project');
            $target = $project . '/outside.txt';
            file_put_contents($target, 'outside');
            self::assertTrue(symlink($target, $project . '/.docara/engine/escape'));
            $this->assertUpdateFailure($updater, $project, 'Symbolic links are forbidden');
        }
    }

    #[Test]
    public function explicit_adoption_is_previewed_applied_and_reversible(): void
    {
        $project = $this->initializeProject();
        self::assertTrue($this->filesystem->deleteDirectory($project . '/.docara'));
        $updater = new PortableProjectUpdater($this->filesystem);

        try {
            $updater->dryRun($project);
            self::fail('Implicit adoption must fail.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('--adopt', $exception->getMessage());
        }

        $plan = $updater->dryRun($project, true);
        self::assertSame('adopt', $plan['action']);
        $applied = $updater->apply($project);
        self::assertFileExists($project . '/.docara/engine/ownership.json');
        $updater->rollback($project, $applied['rollback_id']);
        self::assertDirectoryDoesNotExist($project . '/.docara/engine');
        self::assertFileExists($project . '/content/ru/index.md');
    }

    #[Test]
    public function corrupt_rollback_is_rejected_before_current_state_changes(): void
    {
        $project = $this->initializeProject();
        $updater = new PortableProjectUpdater($this->filesystem);
        $updater->dryRun($project);
        $applied = $updater->apply($project);
        $engineBefore = $this->treeHash($project . '/.docara/engine');
        file_put_contents(
            $project . '/.docara/rollbacks/' . $applied['rollback_id'] . '/simai-framework.lock.json',
            '{}',
        );

        try {
            $updater->rollback($project, $applied['rollback_id']);
            self::fail('Corrupt rollback must fail closed.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('corrupt Framework lock snapshot', $exception->getMessage());
        }
        self::assertSame($engineBefore, $this->treeHash($project . '/.docara/engine'));
    }

    #[Test]
    public function command_requires_one_explicit_action_and_reports_human_next_steps(): void
    {
        $project = $this->initializeProject();
        [$invalid, $invalidConsole] = $this->executeUpdate($project, ['--verify' => true, '--apply' => true]);
        self::assertSame(Command::INVALID, $invalid);
        self::assertStringContainsString('Choose exactly one update action', $invalidConsole->getDisplay());

        [$dryRun, $console] = $this->executeUpdate($project, ['--diff' => true]);
        self::assertSame(Command::SUCCESS, $dryRun, $console->getDisplay());
        self::assertStringContainsString('Review the plan', $console->getDisplay());
        self::assertStringContainsString('Operations: 0', $console->getDisplay());
    }

    private function initializeProject(string $name = 'project'): string
    {
        $project = $this->tmpPath($name);
        $files = new Filesystem;
        $command = new InitCommand($files, new PortableProjectInitializer($files));
        $command->setApplication(new Application);
        $command->setBase($this->tmp);
        $console = new CommandTester($command);
        self::assertSame(Command::SUCCESS, $console->execute(['path' => $name]), $console->getDisplay());

        return $project;
    }

    /** @param array<string, mixed> $arguments @return array{int, CommandTester} */
    private function executeUpdate(string $project, array $arguments): array
    {
        $files = new Filesystem;
        $command = new UpdateCommand(new PortableProjectUpdater($files));
        $command->setApplication(new Application);
        $command->setBase($project);
        $console = new CommandTester($command);

        return [$console->execute($arguments), $console];
    }

    private function copyPackage(): string
    {
        $source = dirname(__DIR__);
        $target = $this->tmpPath('package');
        foreach (['src', 'resources', 'stubs'] as $directory) {
            self::assertTrue($this->filesystem->copyDirectory($source . '/' . $directory, $target . '/' . $directory));
        }
        $this->filesystem->copy($source . '/composer.json', $target . '/composer.json');
        $this->filesystem->copy($source . '/docara', $target . '/docara');

        return $target;
    }

    private function treeHash(string $directory): string
    {
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && ! $file->isLink()) {
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($directory))), '/');
                $records[$relative] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($records, SORT_STRING);

        return hash('sha256', json_encode($records, JSON_THROW_ON_ERROR));
    }

    private function assertUpdateFailure(PortableProjectUpdater $updater, string $project, string $message): void
    {
        try {
            $updater->verify($project);
            self::fail('Unsafe ownership state must fail closed.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }
}
