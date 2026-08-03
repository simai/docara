<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\QaIdentity;
use Simai\Docara\Application\QaService;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class QaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function qa_plan_is_bound_to_isolated_preview_and_exact_matrix(): void
    {
        $service = $this->service();
        $first = $service->plan($this->tmp, 'smart', 'ui.alert', '/ru/components/alert/')->toArray();
        $second = $service->plan($this->tmp, 'smart', 'ui.alert', '/ru/components/alert/')->toArray();

        self::assertSame($first['data']['draft_plan_id'], $second['data']['draft_plan_id']);
        self::assertSame($first['data']['artifact_sha256'], $second['data']['artifact_sha256']);
        self::assertCount(8, $first['data']['scenarios']);
        self::assertSame('.docara-preview/output/smart/index.html', $first['data']['preview']);
        self::assertFileExists($this->tmpPath($first['data']['preview']));
        $plan = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $first['data']['draft_plan_id'] . '.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('element', $plan['target']['scope']);
        self::assertStringContainsString('data-docara-block="alert"', $plan['target']['locator']);
        self::assertSame('docara.production_target_reference_draft.v1', $plan['reference']['contract']);
        self::assertSame('draft', $plan['phase']);
        $this->writeReferenceDraft($plan);
        $finalized = $service->finalizeReference($this->tmp, $plan['plan_id'])->toArray();
        $final = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $finalized['data']['plan_id'] . '.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('finalized', $final['phase']);
        self::assertSame('docara.production_target_reference.v2', $final['reference']['contract']);
        self::assertSame($plan['plan_id'], $final['source_plan_id']);
    }

    #[Test]
    public function exact_zero_defect_report_verifies_and_nonzero_result_fails_closed(): void
    {
        $service = $this->service();
        [$planned, $plan] = $this->finalized($service, 'smart', 'ui.alert');
        $planId = $plan['plan_id'];
        $resultRoot = $this->tmpPath('.docara-qa/results/' . $planId);
        $referenceRoot = $this->tmpPath('.docara-qa/references/' . $plan['reference']['reference_id']);
        $this->filesystem->ensureDirectoryExists($resultRoot . '/screenshots');
        $this->filesystem->ensureDirectoryExists($referenceRoot . '/screenshots');
        $reference = json_decode((string) file_get_contents($referenceRoot . '/reference.json'), true, 512, JSON_THROW_ON_ERROR);
        $scenarios = [];
        foreach ($plan['scenarios'] as $index => $scenario) {
            $referenceScenario = $reference['scenarios'][$index];
            $referencePath = $referenceRoot . '/' . $referenceScenario['screenshot'];
            $bytes = (string) file_get_contents($referencePath);
            $path = $resultRoot . '/' . $scenario['screenshot'];
            $this->filesystem->put($path, $bytes);
            $referenceSha = hash_file('sha256', $referencePath);
            $scenarios[] = [
                'id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash_file('sha256', $path),
                'reference_sha256' => $referenceSha,
                'a11y_violations' => 0, 'console_errors' => 0, 'console_warnings' => 0, 'overflow' => 0,
                'keyboard' => 'pass', 'reduced_motion' => 'pass', 'visual_diff_pixels' => 0,
            ];
        }
        $report = ['schema' => 'docara.qa_report.v2', 'plan_id' => $planId, 'artifact_sha256' => $plan['artifact_sha256'], 'target' => $plan['target'], 'reference' => $plan['reference'], 'scenarios' => $scenarios];
        $this->filesystem->put($resultRoot . '/report.json', CanonicalJson::encodePretty($report));
        self::assertSame(8, $service->verify($this->tmp, $planId)->toArray()['data']['scenarios_verified']);

        $report['scenarios'][0]['visual_diff_pixels'] = 1;
        $this->filesystem->put($resultRoot . '/report.json', CanonicalJson::encodePretty($report));
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('failed one or more acceptance assertions');
        $service->verify($this->tmp, $planId);
    }

    #[Test]
    public function report_with_foreign_target_or_reference_fails_closed(): void
    {
        $service = $this->service();
        [, $plan] = $this->finalized($service, 'region', 'main');
        $planId = $plan['plan_id'];
        [$report] = $this->writeBoundArtifacts($plan);
        $report['target']['id'] = 'foreign';
        $this->filesystem->put($this->tmpPath('.docara-qa/results/' . $planId . '/report.json'), CanonicalJson::encodePretty($report));

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('not bound to the exact plan');
        $service->verify($this->tmp, $planId);
    }

    #[Test]
    public function mutated_production_reference_pixels_fail_before_acceptance(): void
    {
        $service = $this->service();
        [, $plan] = $this->finalized($service, 'layout', 'docara.docs');
        [, $referencePath] = $this->writeBoundArtifacts($plan);
        $this->filesystem->put($referencePath, "\x89PNG\r\n\x1a\nmutated-pixels");

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('does not match its declared hash');
        $service->verify($this->tmp, $plan['plan_id']);
    }

    #[Test]
    public function plan_and_reference_ids_are_recomputed_from_canonical_content(): void
    {
        $service = $this->service();
        [, $plan] = $this->finalized($service, 'smart', 'ui.alert');
        $planId = $plan['plan_id'];
        $path = $this->tmpPath('.docara-qa/plans/' . $planId . '.json');
        $plan['subject'] = 'layout:foreign';
        $plan['target']['kind'] = 'layout';
        $plan['target']['id'] = 'foreign';
        $plan['target']['scope'] = 'document';
        $plan['target']['locator'] = 'body';
        $this->filesystem->put($path, CanonicalJson::encodePretty($plan));

        try {
            $service->verify($this->tmp, $planId);
            self::fail('Mutated plan core was accepted under the original id.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('QA_PLAN_ID_MISMATCH', $exception->errorCode);
        }

        $plan = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $plan['subject'] = 'smart:ui.alert';
        $plan['target'] = ['kind' => 'smart', 'id' => 'ui.alert', 'scope' => 'element', 'locator' => '[data-docara-smart="ui.alert"],[data-docara-block="alert"]', 'html_sha256' => $plan['artifact_sha256']];
        $plan['reference']['manifest_sha256'] = str_repeat('f', 64);
        $newPlanId = QaIdentity::planId($plan);
        $plan['plan_id'] = $newPlanId;
        $this->filesystem->put($this->tmpPath('.docara-qa/plans/' . $newPlanId . '.json'), CanonicalJson::encodePretty($plan));
        try {
            $service->verify($this->tmp, $newPlanId);
            self::fail('Mutated reference seal was accepted under a forged plan id.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('QA_REFERENCE_MANIFEST_MISMATCH', $exception->errorCode);
        }
    }

    #[Test]
    public function verify_rejects_forged_zero_diff_when_candidate_png_differs(): void
    {
        $service = $this->service();
        [, $plan] = $this->finalized($service, 'region', 'main');
        $planId = $plan['plan_id'];
        [$report] = $this->writeBoundArtifacts($plan);
        $candidate = $this->tmpPath('.docara-qa/results/' . $planId . '/' . $report['scenarios'][0]['screenshot']);
        $this->filesystem->put($candidate, "\x89PNG\r\n\x1a\nchanged-candidate-pixels");
        $report['scenarios'][0]['screenshot_sha256'] = hash_file('sha256', $candidate);
        $report['scenarios'][0]['visual_diff_pixels'] = 0;
        $this->filesystem->put($this->tmpPath('.docara-qa/results/' . $planId . '/report.json'), CanonicalJson::encodePretty($report));

        $command = new CommandTester(ApplicationFactory::create($this->tmp)->find('qa'));
        self::assertSame(2, $command->execute(['--verify' => $planId, '--json' => true]));
        $result = json_decode($command->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('QA_VISUAL_DIFF_MISMATCH', $result['diagnostics'][0]['code']);
        self::assertSame($planId, $result['subject']);
    }

    #[Test]
    public function verify_rejects_plan_when_preview_bytes_no_longer_match(): void
    {
        $service = $this->service();
        $planned = $service->plan($this->tmp, 'layout', 'docara.docs', '/ru/components/alert/')->toArray();
        $draft = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $planned['data']['draft_plan_id'] . '.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->writeReferenceDraft($draft);
        $this->filesystem->put($this->tmpPath('.docara-preview/output/layout/artifact.html'), '<main>mutated</main>');

        try {
            $service->finalizeReference($this->tmp, $planned['data']['draft_plan_id']);
            self::fail('Plan with mutated preview bytes was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('QA_PREVIEW_BINDING_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function layout_plan_is_bound_to_the_layout_selected_by_the_page(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('instead of [docara.landing]');
        $this->service()->plan($this->tmp, 'layout', 'docara.landing', '/ru/');
    }

    #[Test]
    public function coherent_reference_candidate_and_report_mutation_is_rejected_under_original_plan(): void
    {
        $service = $this->service();
        [, $plan] = $this->finalized($service, 'smart', 'ui.alert');
        [$report] = $this->writeBoundArtifacts($plan);
        $referencePath = $this->tmpPath('.docara-qa/references/' . $plan['reference']['reference_id'] . '/reference.json');
        $reference = json_decode((string) file_get_contents($referencePath), true, 512, JSON_THROW_ON_ERROR);
        $replacement = "\x89PNG\r\n\x1a\ncoherent-replacement";
        $referenceScreenshot = $this->tmpPath('.docara-qa/references/' . $plan['reference']['reference_id'] . '/' . $reference['scenarios'][0]['screenshot']);
        $candidateScreenshot = $this->tmpPath('.docara-qa/results/' . $plan['plan_id'] . '/' . $report['scenarios'][0]['screenshot']);
        $this->filesystem->put($referenceScreenshot, $replacement);
        $this->filesystem->put($candidateScreenshot, $replacement);
        $replacementSha = hash('sha256', $replacement);
        $reference['scenarios'][0]['screenshot_sha256'] = $replacementSha;
        $report['scenarios'][0]['screenshot_sha256'] = $replacementSha;
        $report['scenarios'][0]['reference_sha256'] = $replacementSha;
        $report['scenarios'][0]['visual_diff_pixels'] = 0;
        $this->filesystem->put($referencePath, CanonicalJson::encodePretty($reference));
        $this->filesystem->put($this->tmpPath('.docara-qa/results/' . $plan['plan_id'] . '/report.json'), CanonicalJson::encodePretty($report));

        $command = new CommandTester(ApplicationFactory::create($this->tmp)->find('qa'));
        self::assertSame(2, $command->execute(['--verify' => $plan['plan_id'], '--json' => true]));
        $result = json_decode($command->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('QA_REFERENCE_ID_MISMATCH', $result['diagnostics'][0]['code']);
        self::assertSame($plan['plan_id'], $result['subject']);
    }

    private function service(): QaService
    {
        $files = new Filesystem;
        $builder = new PortableSiteBuilder($files, new PortableMarkdownRenderer);

        return new QaService(new PreviewKernel($builder, $files), new PreviewShell($files));
    }

    /** @return array{0:array<string,mixed>,1:array<string,mixed>} */
    private function finalized(QaService $service, string $kind, string $id): array
    {
        $draftResult = $service->plan($this->tmp, $kind, $id, '/ru/components/alert/')->toArray();
        $draftId = $draftResult['data']['draft_plan_id'];
        $draft = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $draftId . '.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->writeReferenceDraft($draft);
        $finalized = $service->finalizeReference($this->tmp, $draftId)->toArray()['data'];
        $plan = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $finalized['plan_id'] . '.json')), true, 512, JSON_THROW_ON_ERROR);

        return [$finalized, $plan];
    }

    /** @param array<string,mixed> $draft */
    private function writeReferenceDraft(array $draft): void
    {
        $root = $this->tmpPath('.docara-qa/reference-drafts/' . $draft['plan_id']);
        $this->filesystem->ensureDirectoryExists($root . '/screenshots');
        $scenarios = [];
        foreach ($draft['scenarios'] as $scenario) {
            $bytes = "\x89PNG\r\n\x1a\n" . $scenario['id'];
            $path = $root . '/' . $scenario['screenshot'];
            $this->filesystem->put($path, $bytes);
            $scenarios[] = ['id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash('sha256', $bytes)];
        }
        $reference = [
            'schema' => 'docara.qa_reference_draft.v1',
            'draft_plan_id' => $draft['plan_id'],
            'subject' => $draft['subject'],
            'target' => $draft['target'],
            'artifact_sha256' => $draft['artifact_sha256'],
            'page_html_sha256' => $draft['reference']['page_html_sha256'],
            'scenarios' => $scenarios,
        ];
        $this->filesystem->put($root . '/reference.json', CanonicalJson::encodePretty($reference));
    }

    /** @param array<string,mixed> $plan @return array{0:array<string,mixed>,1:string} */
    private function writeBoundArtifacts(array $plan): array
    {
        $planId = $plan['plan_id'];
        $resultRoot = $this->tmpPath('.docara-qa/results/' . $planId);
        $referenceRoot = $this->tmpPath('.docara-qa/references/' . $plan['reference']['reference_id']);
        $this->filesystem->ensureDirectoryExists($resultRoot . '/screenshots');
        $reference = json_decode((string) file_get_contents($referenceRoot . '/reference.json'), true, 512, JSON_THROW_ON_ERROR);
        $scenarios = [];
        $firstReference = '';
        foreach ($plan['scenarios'] as $index => $scenario) {
            $referenceScenario = $reference['scenarios'][$index];
            $referenceScreenshot = $referenceRoot . '/' . $referenceScenario['screenshot'];
            $bytes = (string) file_get_contents($referenceScreenshot);
            $screenshot = $resultRoot . '/' . $scenario['screenshot'];
            $this->filesystem->put($screenshot, $bytes);
            if ($firstReference === '') {
                $firstReference = $referenceScreenshot;
            }
            $referenceSha = hash_file('sha256', $referenceScreenshot);
            $scenarios[] = ['id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash_file('sha256', $screenshot), 'reference_sha256' => $referenceSha, 'a11y_violations' => 0, 'console_errors' => 0, 'console_warnings' => 0, 'overflow' => 0, 'keyboard' => 'pass', 'reduced_motion' => 'pass', 'visual_diff_pixels' => 0];
        }
        $report = ['schema' => 'docara.qa_report.v2', 'plan_id' => $planId, 'artifact_sha256' => $plan['artifact_sha256'], 'target' => $plan['target'], 'reference' => $plan['reference'], 'scenarios' => $scenarios];
        $this->filesystem->put($resultRoot . '/report.json', CanonicalJson::encodePretty($report));

        return [$report, $firstReference];
    }
}
