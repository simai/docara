<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\CanonicalJson;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class Goal3WriteContainmentTest extends TestCase
{
    private string $project;

    private string $outside;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = $this->tmpPath('project');
        $this->outside = $this->tmpPath('outside');
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->project);
        $this->filesystem->ensureDirectoryExists($this->outside);
    }

    #[Test]
    public function preview_root_symlink_is_rejected_before_the_first_external_mutation(): void
    {
        self::assertTrue(symlink($this->outside, $this->project . '/.docara-preview'));
        try {
            $this->assertQaRejectedWithoutOutsideMutation('SDK_WRITE_PATH_UNSAFE');
        } finally {
            unlink($this->project . '/.docara-preview');
        }
    }

    #[Test]
    public function qa_root_symlink_is_rejected_before_creating_external_plans_directory(): void
    {
        self::assertTrue(symlink($this->outside, $this->project . '/.docara-qa'));
        try {
            $this->assertQaRejectedWithoutOutsideMutation('SDK_WRITE_PATH_UNSAFE');
        } finally {
            unlink($this->project . '/.docara-qa');
        }
    }

    #[Test]
    public function nested_preview_parent_target_and_build_cache_symlinks_are_rejected_without_external_mutation(): void
    {
        foreach (['.docara-preview/output', '.docara-preview/.candidate-smart', 'build_preview-cache'] as $relative) {
            $this->filesystem->deleteDirectory($this->project . '/.docara-preview');
            $this->filesystem->deleteDirectory($this->project . '/build_preview-cache');
            $this->filesystem->ensureDirectoryExists(dirname($this->project . '/' . $relative));
            self::assertTrue(symlink($this->outside, $this->project . '/' . $relative));
            try {
                $this->assertQaRejectedWithoutOutsideMutation('SDK_WRITE_PATH_UNSAFE');
            } finally {
                unlink($this->project . '/' . $relative);
            }
        }
    }

    #[Test]
    public function qa_verify_rejects_symlinked_results_parent_without_reading_or_mutating_external_artifacts(): void
    {
        $plan = $this->qaFinalPlan();
        $results = $this->project . '/.docara-qa/results';
        self::assertTrue(symlink($this->outside, $results));
        $this->filesystem->put($this->outside . '/report.json', '{}');
        $before = $this->inventory($this->outside);
        try {
            $tester = new CommandTester(ApplicationFactory::create($this->project)->find('qa'));
            self::assertSame(2, $tester->execute([
                '--verify' => $plan,
                '--json' => true,
            ]));
            $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('SDK_WRITE_PATH_UNSAFE', $result['diagnostics'][0]['code']);
            self::assertSame($before, $this->inventory($this->outside));
        } finally {
            unlink($results);
        }
    }

    #[Test]
    public function hardlinked_plan_and_content_collision_are_rejected_without_overwrite(): void
    {
        $planId = $this->qaPlan();
        $plan = $this->project . '/.docara-qa/plans/' . $planId . '.json';
        $contents = (string) file_get_contents($plan);
        unlink($plan);
        $outsidePlan = $this->outside . '/plan.json';
        $this->filesystem->put($outsidePlan, $contents);
        self::assertTrue(link($outsidePlan, $plan));
        $before = $this->inventory($this->outside);
        try {
            $this->assertQaRejectedWithoutOutsideMutation('SDK_WRITE_PATH_UNSAFE', $before);
        } finally {
            unlink($plan);
        }

        $this->filesystem->put($plan, "{}\n");
        $this->filesystem->ensureDirectoryExists($this->project . '/.DOCARA-QA');
        $tester = new CommandTester(ApplicationFactory::create($this->project)->find('qa'));
        self::assertSame(2, $tester->execute([
            'kind' => 'smart',
            'id' => 'ui.alert',
            '--page' => '/ru/components/alert/',
            '--dry-run' => true,
            '--json' => true,
        ]));
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('QA_PLAN_COLLISION', $result['diagnostics'][0]['code']);
        self::assertSame("{}\n", file_get_contents($plan));
    }

    #[Test]
    public function scaffold_plan_root_symlink_is_rejected_without_external_mutation(): void
    {
        $this->filesystem->deleteDirectory($this->project . '/.docara');
        self::assertTrue(symlink($this->outside, $this->project . '/.docara'));
        $before = $this->inventory($this->outside);
        try {
            $tester = new CommandTester(ApplicationFactory::create($this->project)->find('scaffold'));
            self::assertSame(2, $tester->execute([
                'kind' => 'smart',
                'id' => 'project.card',
                '--dry-run' => true,
                '--json' => true,
            ]));
            $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('SDK_WRITE_PATH_UNSAFE', $result['diagnostics'][0]['code']);
            self::assertSame($before, $this->inventory($this->outside));
        } finally {
            unlink($this->project . '/.docara');
        }
    }

    private function assertQaRejectedWithoutOutsideMutation(string $code, ?array $before = null): void
    {
        $before ??= $this->inventory($this->outside);
        $tester = new CommandTester(ApplicationFactory::create($this->project)->find('qa'));
        self::assertSame(2, $tester->execute([
            'kind' => 'smart',
            'id' => 'ui.alert',
            '--page' => '/ru/components/alert/',
            '--dry-run' => true,
            '--json' => true,
        ]));
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($code, $result['diagnostics'][0]['code']);
        self::assertSame($before, $this->inventory($this->outside));
    }

    private function qaPlan(): string
    {
        $tester = new CommandTester(ApplicationFactory::create($this->project)->find('qa'));
        self::assertSame(0, $tester->execute([
            'kind' => 'smart',
            'id' => 'ui.alert',
            '--page' => '/ru/components/alert/',
            '--dry-run' => true,
            '--json' => true,
        ]));
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        return (string) $result['data']['draft_plan_id'];
    }

    private function qaFinalPlan(): string
    {
        $draftId = $this->qaPlan();
        $draft = json_decode((string) file_get_contents($this->project . '/.docara-qa/plans/' . $draftId . '.json'), true, 512, JSON_THROW_ON_ERROR);
        $root = $this->project . '/.docara-qa/reference-drafts/' . $draftId;
        $this->filesystem->ensureDirectoryExists($root . '/screenshots');
        $scenarios = [];
        foreach ($draft['scenarios'] as $scenario) {
            $bytes = "\x89PNG\r\n\x1a\n" . $scenario['id'];
            $this->filesystem->put($root . '/' . $scenario['screenshot'], $bytes);
            $scenarios[] = ['id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash('sha256', $bytes)];
        }
        $reference = ['schema' => 'docara.qa_reference_draft.v1', 'draft_plan_id' => $draftId, 'subject' => $draft['subject'], 'target' => $draft['target'], 'artifact_sha256' => $draft['artifact_sha256'], 'page_html_sha256' => $draft['reference']['page_html_sha256'], 'scenarios' => $scenarios];
        $this->filesystem->put($root . '/reference.json', CanonicalJson::encodePretty($reference));
        $tester = new CommandTester(ApplicationFactory::create($this->project)->find('qa'));
        self::assertSame(0, $tester->execute(['--finalize-reference' => $draftId, '--json' => true]));
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        return (string) $result['data']['plan_id'];
    }

    /** @return list<string> */
    private function inventory(string $root): array
    {
        $records = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
            $records[] = ($entry->isDir() ? 'd:' : 'f:') . $relative
                . ($entry->isFile() ? ':' . hash_file('sha256', $entry->getPathname()) : '');
        }
        sort($records, SORT_STRING);

        return $records;
    }
}
