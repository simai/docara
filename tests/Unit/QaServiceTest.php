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
    }

    #[Test]
    public function exact_zero_defect_report_verifies_and_nonzero_result_fails_closed(): void
    {
        $service = $this->service();
        $planned = $service->plan($this->tmp, 'smart', 'ui.alert', '/ru/components/alert/')->toArray();
        $planId = $planned['data']['plan_id'];
        $resultRoot = $this->tmpPath('.docara-qa/results/' . $planId);
        $this->filesystem->ensureDirectoryExists($resultRoot . '/screenshots');
        $png = "\x89PNG\r\n\x1a\nfixture";
        $scenarios = [];
        foreach ($planned['data']['scenarios'] as $scenario) {
            $path = $resultRoot . '/' . $scenario['screenshot'];
            $this->filesystem->put($path, $png . $scenario['id']);
            $scenarios[] = [
                'id' => $scenario['id'], 'screenshot' => $scenario['screenshot'], 'screenshot_sha256' => hash_file('sha256', $path),
                'a11y_violations' => 0, 'console_errors' => 0, 'console_warnings' => 0, 'overflow' => 0,
                'keyboard' => 'pass', 'reduced_motion' => 'pass', 'visual_diff_pixels' => 0,
            ];
        }
        $report = ['schema' => 'docara.qa_report.v1', 'plan_id' => $planId, 'artifact_sha256' => $planned['data']['artifact_sha256'], 'scenarios' => $scenarios];
        $this->filesystem->put($resultRoot . '/report.json', CanonicalJson::encodePretty($report));
        self::assertSame(8, $service->verify($this->tmp, $planId)->toArray()['data']['scenarios_verified']);

        $report['scenarios'][0]['console_errors'] = 1;
        $this->filesystem->put($resultRoot . '/report.json', CanonicalJson::encodePretty($report));
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('failed one or more acceptance assertions');
        $service->verify($this->tmp, $planId);
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
}
