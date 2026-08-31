<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\ProjectUpgradeService;
use Simai\Docara\PortableSite\PortableProjectInitializer;

final class ProjectUpgradeServiceTest extends TestCase
{
    #[Test]
    public function plan_apply_and_offline_rollback_preserve_project_owned_inputs(): void
    {
        $project = $this->consumer();
        $content = (string) file_get_contents($project . '/content/ru/index.md');
        $service = $this->service();

        $plan = $service->plan($project, '2.4.0')->data;
        self::assertSame('docara.upgrade_plan.v1', $plan['schema']);
        self::assertSame('upgrade_available', $plan['status']);
        self::assertSame('2.3.0', $plan['current_version']);
        self::assertSame('2.4.0', $plan['target_version']);
        self::assertSame($content, file_get_contents($project . '/content/ru/index.md'));

        $applied = $service->apply($project, $plan['plan_sha256'])->data;
        self::assertSame('applied', $applied['status']);
        self::assertSame('2.4.0', $this->lockVersion($project . '/composer.lock'));
        self::assertFileExists($project . '/build_production/new.html');
        self::assertFileDoesNotExist($project . '/build_production/old.html');
        self::assertSame($content, file_get_contents($project . '/content/ru/index.md'));

        $rolledBack = $service->rollback($project, 'latest')->data;
        self::assertSame('rolled_back', $rolledBack['status']);
        self::assertSame('2.3.0', $this->lockVersion($project . '/composer.lock'));
        self::assertFileExists($project . '/build_production/old.html');
        self::assertSame($content, file_get_contents($project . '/content/ru/index.md'));
    }

    #[Test]
    public function changed_project_input_invalidates_the_plan_before_runtime_promotion(): void
    {
        $project = $this->consumer();
        $service = $this->service();
        $plan = $service->plan($project, '2.4.0')->data;
        file_put_contents($project . '/content/ru/index.md', "# Changed\n");

        $this->expectExceptionMessage('UPGRADE_PLAN_STALE');
        try {
            $service->apply($project, $plan['plan_sha256']);
        } finally {
            self::assertSame('2.3.0', $this->lockVersion($project . '/composer.lock'));
            self::assertFileExists($project . '/build_production/old.html');
        }
    }

    #[Test]
    public function major_and_non_exact_targets_are_rejected_before_candidate_resolution(): void
    {
        $project = $this->consumer();
        $calls = 0;
        $service = new ProjectUpgradeService(
            $this->filesystem,
            dirname(__DIR__),
            function () use (&$calls): array {
                $calls++;

                return [];
            },
        );
        try {
            $service->plan($project, '3.0.0');
            self::fail('Major upgrade must fail closed.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('MAJOR_UPGRADE_REQUIRED', $exception->getMessage());
        }
        try {
            $service->plan($project, '^2.4');
            self::fail('Moving target must fail closed.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('UPGRADE_TARGET_VERSION_INVALID', $exception->getMessage());
        }
        self::assertSame(0, $calls);
    }

    #[Test]
    public function legacy_engine_without_project_local_composer_gets_one_safe_bootstrap_path(): void
    {
        $project = $this->tmpPath('legacy');
        (new PortableProjectInitializer($this->filesystem))->initialize($project);

        $this->expectExceptionMessage('PROJECT_LOCAL_RUNTIME_REQUIRED');
        $this->service()->plan($project);
    }

    #[Test]
    public function project_local_runtime_without_engine_gets_the_explicit_adoption_path(): void
    {
        $project = $this->consumer();
        self::assertTrue($this->filesystem->deleteDirectory($project . '/.docara/engine'));

        try {
            $this->service()->check($project);
            self::fail('A pre-manifest project must receive the explicit adoption path.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('UPGRADE_ENGINE_ADOPTION_REQUIRED', $exception->getMessage());
            self::assertStringContainsString('update --dry-run --adopt --json', $exception->getMessage());
            self::assertStringContainsString('update --apply --json', $exception->getMessage());
        }

        self::assertDirectoryDoesNotExist($project . '/.docara/engine');
        self::assertFileExists($project . '/content/ru/index.md');
    }

    #[Test]
    public function failed_promotion_compensates_dependencies_lock_engine_and_verified_build(): void
    {
        $project = $this->consumer();
        $service = new ProjectUpgradeService(
            $this->filesystem,
            dirname(__DIR__),
            function (string $root, string $candidate): array {
                $this->writeLock($candidate . '/composer.lock', '2.4.0');
                $this->filesystem->ensureDirectoryExists($candidate . '/vendor/bin');
                $this->filesystem->put($candidate . '/vendor/bin/docara', "<?php exit(9);\n");

                return ['resolved' => true];
            },
            function (string $root, string $candidate, string $upgradeRoot): array {
                $this->filesystem->ensureDirectoryExists($upgradeRoot . '/verified-build');
                $this->filesystem->put($upgradeRoot . '/verified-build/new.html', 'new');

                return ['checks' => ['fixture']];
            },
        );
        $plan = $service->plan($project, '2.4.0')->data;

        try {
            $service->apply($project, $plan['plan_sha256']);
            self::fail('Failed candidate runtime must trigger compensation.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('UPGRADE_CANDIDATE_CHECK_FAILED', $exception->getMessage());
        }
        self::assertSame('2.3.0', $this->lockVersion($project . '/composer.lock'));
        self::assertFileExists($project . '/vendor/bin/docara');
        self::assertFileExists($project . '/.docara/engine/ownership.json');
        self::assertFileExists($project . '/build_production/old.html');
        $journal = json_decode((string) file_get_contents($project . '/.docara/upgrades/' . $plan['upgrade_id'] . '/journal.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('compensated', $journal['phase']);
    }

    #[Test]
    public function current_version_is_reported_without_verification_or_runtime_changes(): void
    {
        $project = $this->consumer();
        $verified = false;
        $service = new ProjectUpgradeService(
            $this->filesystem,
            dirname(__DIR__),
            function (string $root, string $candidate): array {
                $this->writeLock($candidate . '/composer.lock', '2.3.0');

                return ['resolved' => true];
            },
            function () use (&$verified): array {
                $verified = true;

                return [];
            },
        );

        $result = $service->upgrade($project)->data;

        self::assertSame('current', $result['status']);
        self::assertSame('2.3.0', $result['current_version']);
        self::assertFalse($verified);
        self::assertFileExists($project . '/build_production/old.html');
    }

    #[Test]
    public function modified_plan_or_verified_build_is_rejected_before_promotion(): void
    {
        $project = $this->consumer();
        $service = $this->service();
        $plan = $service->plan($project, '2.4.0')->data;
        $planPath = $project . '/.docara/upgrades/' . $plan['upgrade_id'] . '/plan.json';
        $originalPlan = (string) file_get_contents($planPath);
        $decoded = json_decode($originalPlan, true, 512, JSON_THROW_ON_ERROR);
        $decoded['target_version'] = '2.4.1';
        file_put_contents($planPath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        try {
            $service->apply($project, $plan['plan_sha256']);
            self::fail('Tampered plan must fail closed.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('UPGRADE_PLAN_HASH_MISMATCH', $exception->getMessage());
        }

        file_put_contents($planPath, $originalPlan);
        file_put_contents($project . '/.docara/upgrades/' . $plan['upgrade_id'] . '/verified-build/new.html', 'tampered');
        $this->expectExceptionMessage('UPGRADE_PLAN_STALE');
        $service->apply($project, $plan['plan_sha256']);
    }

    #[Test]
    public function rollback_refuses_to_overwrite_runtime_changed_after_apply(): void
    {
        $project = $this->consumer();
        $service = $this->service();
        $plan = $service->plan($project, '2.4.0')->data;
        $service->apply($project, $plan['plan_sha256']);
        file_put_contents($project . '/build_production/new.html', 'changed-after-apply');

        $this->expectExceptionMessage('UPGRADE_ROLLBACK_STALE');
        $service->rollback($project, 'latest');
    }

    #[Test]
    public function symbolic_link_in_project_owned_inputs_is_rejected(): void
    {
        $project = $this->consumer();
        $target = $project . '/content/ru/index.md';
        $link = $project . '/content/ru/linked.md';
        if (! @symlink($target, $link)) {
            self::markTestSkipped('Symbolic links are unavailable in this environment.');
        }

        $this->expectExceptionMessage('UPGRADE_PROJECT_PATH_UNSAFE');
        $this->service()->plan($project, '2.4.0');
    }

    private function consumer(): string
    {
        $project = $this->tmpPath('consumer-' . bin2hex(random_bytes(3)));
        (new PortableProjectInitializer($this->filesystem))->initialize($project);
        $this->filesystem->put($project . '/composer.json', json_encode([
            'name' => 'fixture/docs',
            'require' => ['simai/docara' => '^2.3'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
        $this->writeLock($project . '/composer.lock', '2.3.0');
        $this->filesystem->ensureDirectoryExists($project . '/vendor/bin');
        $this->filesystem->put($project . '/vendor/bin/docara', $this->proxy());
        $this->filesystem->ensureDirectoryExists($project . '/build_production');
        $this->filesystem->put($project . '/build_production/old.html', 'old');

        return $project;
    }

    private function service(): ProjectUpgradeService
    {
        return new ProjectUpgradeService(
            $this->filesystem,
            dirname(__DIR__),
            function (string $root, string $candidate, ?string $target): array {
                $this->writeLock($candidate . '/composer.lock', $target ?? '2.4.0');
                $this->filesystem->ensureDirectoryExists($candidate . '/vendor/bin');
                $this->filesystem->put($candidate . '/vendor/bin/docara', $this->proxy());

                return ['resolved' => true];
            },
            function (string $root, string $candidate, string $upgradeRoot): array {
                $this->filesystem->ensureDirectoryExists($upgradeRoot . '/verified-build');
                $this->filesystem->put($upgradeRoot . '/verified-build/new.html', 'new');

                return ['checks' => ['doctor', 'validate', 'update', 'build', 'verify-static']];
            },
        );
    }

    private function proxy(): string
    {
        $autoload = var_export(dirname(__DIR__) . '/vendor/autoload.php', true);

        return "#!/usr/bin/env php\n<?php\nrequire {$autoload};\nexit(\\Simai\\Docara\\Console\\ApplicationFactory::create()->run());\n";
    }

    private function writeLock(string $path, string $version): void
    {
        $this->filesystem->put($path, json_encode([
            '_readme' => ['fixture'],
            'content-hash' => hash('sha256', $version),
            'packages' => [[
                'name' => 'simai/docara',
                'version' => $version,
                'dist' => ['reference' => hash('sha256', 'dist-' . $version)],
            ]],
            'packages-dev' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    }

    private function lockVersion(string $path): string
    {
        $lock = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $lock['packages'][0]['version'];
    }
}
