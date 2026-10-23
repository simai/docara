<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Authoring\DocumentationStandard;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Simai\Docara\Quality\QualityReportService;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class QualityReportCommandTest extends TestCase
{
    #[Test]
    public function report_review_packet_build_line_and_mcp_work_on_a_starter(): void
    {
        (new PortableProjectInitializer($this->filesystem))->initialize($this->tmp, 'minimal', 'en');
        $this->filesystem->put($this->tmpPath('content/en/how-to/broken.md'), "---\ndescription: \"A page with a link to a page that does not exist.\"\n---\n\n# Broken\n\nSee [the missing page](/missing-page/) for details.\n");
        $application = ApplicationFactory::create($this->tmp);

        // Before a build there is nothing to score.
        $qa = new CommandTester($application->find('qa'));
        self::assertSame(2, $qa->execute(['kind' => 'report', '--json' => true]));
        self::assertSame('QUALITY_BUILD_MISSING', json_decode($qa->getDisplay(), true)['diagnostics'][0]['code']);

        // The build prints one line because docara.authoring.json exists.
        $build = new CommandTester($application->find('build'));
        self::assertSame(0, $build->execute([]));
        self::assertMatchesRegularExpression('/Documentation quality: \d{1,3}\/100, \d+ page\(s\) need attention \(docara qa report\)\./', $build->getDisplay());

        // The human summary, the JSON file and the optional HTML page.
        self::assertSame(0, $qa->execute(['kind' => 'report', '--html' => true, '--top' => '3']));
        $display = $qa->getDisplay();
        self::assertMatchesRegularExpression('/Documentation quality: \d{1,3}\/100 — \d+ page\(s\) scored/', $display);
        self::assertStringContainsString('Fix first:', $display);
        self::assertStringContainsString('Report (json): build_local.docara/quality-report.json', $display);
        $report = json_decode((string) file_get_contents($this->tmpPath('build_local.docara/quality-report.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(QualityReportService::SCHEMA, $report['schema']);
        self::assertCount(3, $report['fix_first']);
        self::assertEqualsCanonicalizing(['completeness', 'freshness', 'findability', 'readability', 'accessibility', 'links'], array_keys($report['groups']));
        self::assertNull($report['groups']['freshness']['score']);
        $broken = array_values(array_filter($report['pages'], static fn (array $page): bool => $page['url'] === '/how-to/broken/'))[0];
        self::assertContains('QUALITY_LINK_BROKEN', array_column($broken['findings'], 'code'));
        self::assertFileExists($this->tmpPath('build_local.docara/quality-report.html'));
        self::assertStringNotContainsString('<script', (string) file_get_contents($this->tmpPath('build_local.docara/quality-report.html')));
        self::assertFileDoesNotExist($this->tmpPath('build_local/quality-report.html'));

        // The report is deterministic.
        $first = file_get_contents($this->tmpPath('build_local.docara/quality-report.json'));
        $qa->execute(['kind' => 'report', '--html' => true, '--top' => '3']);
        self::assertSame($first, file_get_contents($this->tmpPath('build_local.docara/quality-report.json')));

        // Strict mode: --min-score, then quality.min_score.
        self::assertSame(1, $qa->execute(['kind' => 'report', '--min-score' => '100', '--json' => true]));
        $result = json_decode($qa->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('QUALITY_SCORE_BELOW_MINIMUM', $result['diagnostics'][0]['code']);
        self::assertFalse($result['data']['gate']['passed']);
        self::assertSame(0, $qa->execute(['kind' => 'report', '--min-score' => '0']));
        self::assertSame(2, $qa->execute(['kind' => 'report', '--min-score' => 'high', '--json' => true]));
        $authoring = json_decode((string) file_get_contents($this->tmpPath('docara.authoring.json')), true, 512, JSON_THROW_ON_ERROR);
        $authoring['quality'] = ['min_score' => 100, 'exclude' => ['how-to/broken.md']];
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), json_encode($authoring, JSON_THROW_ON_ERROR));
        self::assertSame(1, $qa->execute(['kind' => 'report']));
        self::assertStringContainsString('Minimum score 100: FAILED.', $qa->getDisplay());
        $report = json_decode((string) file_get_contents($this->tmpPath('build_local.docara/quality-report.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['quality.exclude'], array_column(array_filter($report['skipped'], static fn (array $item): bool => $item['url'] === '/how-to/broken/'), 'reason'));

        // The review packet of one page.
        $review = new CommandTester($application->find('qa'));
        self::assertSame(0, $review->execute(['kind' => 'review-packet', 'id' => '/how-to/publish-the-site/', '--json' => true]));
        $packet = json_decode($review->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['data'];
        self::assertSame('page', $packet['scope']);
        self::assertSame(['accuracy', 'clarity', 'task', 'examples'], array_column($packet['packet']['questions'], 'id'));
        $page = $packet['packet']['pages'][0];
        self::assertSame('how_to', $page['profile']['id']);
        self::assertSame(['task_boundary', 'step_safety', 'result_confirmation'], array_column($page['checklist'], 'id'));
        self::assertStringContainsString('# Publish the site', $page['text']);
        self::assertFileExists($this->tmpPath('build_local.docara/review-packets/how-to-publish-the-site.md'));
        self::assertSame(2, $review->execute(['kind' => 'review-packet', 'id' => '/nowhere/', '--json' => true]));

        // MCP exposes both operations.
        $adapter = new McpAdapter(SdkServiceFactory::create(), $this->tmp);
        $names = array_column($adapter->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])['result']['tools'], 'name');
        self::assertContains('docara_qa_report', $names);
        self::assertContains('docara_qa_review_packet', $names);
        $call = $adapter->handle(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'docara_qa_review_packet', 'arguments' => ['page' => '/how-to/publish-the-site/']]]);
        self::assertFalse($call['result']['isError']);
        $call = $adapter->handle(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'docara_qa_report', 'arguments' => ['min_score' => 0]]]);
        self::assertFalse($call['result']['isError']);
        self::assertArrayHasKey('score', $call['result']['structuredContent']['data']['site']);

        // AGENTS.md tells assistants to run the packet.
        self::assertStringContainsString('qa review-packet <route>', (new DocumentationStandard)->agentsMarkdownForProject($this->tmp));
    }

    #[Test]
    public function a_site_without_docara_authoring_json_gets_no_quality_line(): void
    {
        (new PortableProjectInitializer($this->filesystem))->initialize($this->tmp, 'minimal', 'en');
        $this->filesystem->delete($this->tmpPath('docara.authoring.json'));

        $build = new CommandTester(ApplicationFactory::create($this->tmp)->find('build'));
        self::assertSame(0, $build->execute([]));
        self::assertStringNotContainsString('Documentation quality', $build->getDisplay());
        self::assertFileDoesNotExist($this->tmpPath('build_local.docara/quality-report.json'));
    }
}
