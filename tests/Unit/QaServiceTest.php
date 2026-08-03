<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\QaService;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Preview\PreviewKernel;
use Simai\Docara\Preview\PreviewShell;
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

        self::assertSame($first['data']['plan_id'], $second['data']['plan_id']);
        self::assertSame($first['data']['artifact_sha256'], $second['data']['artifact_sha256']);
        self::assertCount(8, $first['data']['scenarios']);
        self::assertSame('.docara-preview/output/smart/index.html', $first['data']['preview']);
        self::assertFileExists($this->tmpPath($first['data']['preview']));
        $plan = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $first['data']['plan_id'] . '.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('element', $plan['target']['scope']);
        self::assertStringContainsString('data-docara-block="alert"', $plan['target']['locator']);
        self::assertSame('docara.production_target_reference.v1', $plan['reference']['contract']);
    }

    #[Test]
    public function exact_zero_defect_report_verifies_and_nonzero_result_fails_closed(): void
    {
        $service = $this->service();
        $planned = $service->plan($this->tmp, 'smart', 'ui.alert', '/ru/components/alert/')->toArray();
        $planId = $planned['data']['plan_id'];
        $plan = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $planId . '.json')), true, 512, JSON_THROW_ON_ERROR);
        $resultRoot = $this->tmpPath('.docara-qa/results/' . $planId);
        $referenceRoot = $this->tmpPath('.docara-qa/references/' . $plan['reference']['reference_id']);
        $this->filesystem->ensureDirectoryExists($resultRoot . '/screenshots');
        $this->filesystem->ensureDirectoryExists($referenceRoot . '/screenshots');
        $png = "\x89PNG\r\n\x1a\nfixture";
        $scenarios = [];
        $referenceScenarios = [];
        foreach ($planned['data']['scenarios'] as $scenario) {
            $path = $resultRoot . '/' . $scenario['screenshot'];
            $this->filesystem->put($path, $png . $scenario['id']);
            $referencePath = $referenceRoot . '/' . $scenario['screenshot'];
            $this->filesystem->put($referencePath, $png . $scenario['id']);
            $referenceSha = hash_file('sha256', $referencePath);
            $referenceScenarios[] = ['id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => $referenceSha];
            $scenarios[] = [
                'id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash_file('sha256', $path),
                'reference_sha256' => $referenceSha,
                'a11y_violations' => 0, 'console_errors' => 0, 'console_warnings' => 0, 'overflow' => 0,
                'keyboard' => 'pass', 'reduced_motion' => 'pass', 'visual_diff_pixels' => 0,
            ];
        }
        $reference = ['schema' => 'docara.qa_reference.v1', 'reference_id' => $plan['reference']['reference_id'], 'subject' => $plan['subject'], 'target' => $plan['target'], 'artifact_sha256' => $plan['artifact_sha256'], 'page_html_sha256' => $plan['reference']['page_html_sha256'], 'scenarios' => $referenceScenarios];
        $this->filesystem->put($referenceRoot . '/reference.json', CanonicalJson::encodePretty($reference));
        $report = ['schema' => 'docara.qa_report.v1', 'plan_id' => $planId, 'artifact_sha256' => $planned['data']['artifact_sha256'], 'target' => $plan['target'], 'reference' => $plan['reference'], 'scenarios' => $scenarios];
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
        $planned = $service->plan($this->tmp, 'region', 'main', '/ru/components/alert/')->toArray();
        $planId = $planned['data']['plan_id'];
        $plan = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $planId . '.json')), true, 512, JSON_THROW_ON_ERROR);
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
        $planned = $service->plan($this->tmp, 'layout', 'docara.docs', '/ru/components/alert/')->toArray();
        $plan = json_decode((string) file_get_contents($this->tmpPath('.docara-qa/plans/' . $planned['data']['plan_id'] . '.json')), true, 512, JSON_THROW_ON_ERROR);
        [, $referencePath] = $this->writeBoundArtifacts($plan);
        $this->filesystem->put($referencePath, "\x89PNG\r\n\x1a\nmutated-pixels");

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('does not match its declared hash');
        $service->verify($this->tmp, $planned['data']['plan_id']);
    }

    #[Test]
    public function layout_plan_is_bound_to_the_layout_selected_by_the_page(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('instead of [docara.landing]');
        $this->service()->plan($this->tmp, 'layout', 'docara.landing', '/ru/');
    }

    private function service(): QaService
    {
        $files = new Filesystem;
        $builder = new PortableSiteBuilder($files, new PortableMarkdownRenderer);

        return new QaService(new PreviewKernel($builder, $files), new PreviewShell($files));
    }

    /** @param array<string,mixed> $plan @return array{0:array<string,mixed>,1:string} */
    private function writeBoundArtifacts(array $plan): array
    {
        $planId = $plan['plan_id'];
        $resultRoot = $this->tmpPath('.docara-qa/results/' . $planId);
        $referenceRoot = $this->tmpPath('.docara-qa/references/' . $plan['reference']['reference_id']);
        $this->filesystem->ensureDirectoryExists($resultRoot . '/screenshots');
        $this->filesystem->ensureDirectoryExists($referenceRoot . '/screenshots');
        $scenarios = [];
        $referenceScenarios = [];
        $firstReference = '';
        foreach ($plan['scenarios'] as $scenario) {
            $bytes = "\x89PNG\r\n\x1a\n" . $scenario['id'];
            $screenshot = $resultRoot . '/' . $scenario['screenshot'];
            $referenceScreenshot = $referenceRoot . '/' . $scenario['screenshot'];
            $this->filesystem->put($screenshot, $bytes);
            $this->filesystem->put($referenceScreenshot, $bytes);
            if ($firstReference === '') {
                $firstReference = $referenceScreenshot;
            }
            $referenceSha = hash_file('sha256', $referenceScreenshot);
            $referenceScenarios[] = ['id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => $referenceSha];
            $scenarios[] = ['id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash_file('sha256', $screenshot), 'reference_sha256' => $referenceSha, 'a11y_violations' => 0, 'console_errors' => 0, 'console_warnings' => 0, 'overflow' => 0, 'keyboard' => 'pass', 'reduced_motion' => 'pass', 'visual_diff_pixels' => 0];
        }
        $reference = ['schema' => 'docara.qa_reference.v1', 'reference_id' => $plan['reference']['reference_id'], 'subject' => $plan['subject'], 'target' => $plan['target'], 'artifact_sha256' => $plan['artifact_sha256'], 'page_html_sha256' => $plan['reference']['page_html_sha256'], 'scenarios' => $referenceScenarios];
        $this->filesystem->put($referenceRoot . '/reference.json', CanonicalJson::encodePretty($reference));
        $report = ['schema' => 'docara.qa_report.v1', 'plan_id' => $planId, 'artifact_sha256' => $plan['artifact_sha256'], 'target' => $plan['target'], 'reference' => $plan['reference'], 'scenarios' => $scenarios];
        $this->filesystem->put($resultRoot . '/report.json', CanonicalJson::encodePretty($report));

        return [$report, $firstReference];
    }
}
