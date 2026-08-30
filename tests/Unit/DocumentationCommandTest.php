<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\DocumentationCommand;
use Simai\Docara\Documentation\DocumentationStatusService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class DocumentationCommandTest extends TestCase
{
    #[Test]
    public function status_is_available_through_the_cli(): void
    {
        $this->createSource([
            'docara.json' => json_encode([
                'schema' => 'docara.site.v1', 'preset' => 'docs', 'framework_lock' => 'framework.lock.json',
                'default_locale' => 'ru', 'locales' => ['missing_page_policy' => 'skip', 'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => '']],
                'documentation_tracking' => ['enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'documentation.lock.json', 'sources' => [['id' => 'api', 'provider' => 'contract_json', 'file' => 'api.json']]],
            ], JSON_THROW_ON_ERROR),
            'api.json' => json_encode(['schema' => 'docara.documentation_source.v1', 'id' => 'api', 'provider' => 'contract_json', 'revision' => '1', 'entities' => [[
                'key' => 'api.endpoint', 'kind' => 'reference', 'title' => 'Endpoint', 'public_contract' => ['method' => 'GET'], 'example_cases' => [], 'provenance' => ['openapi.json'],
            ]]], JSON_THROW_ON_ERROR),
            'content/ru/index.md' => "# API\n", 'content/ru/lang.json' => '{}',
        ]);
        $application = new Application;
        $application->addCommand((new DocumentationCommand(new DocumentationStatusService))->setBase($this->tmp));
        $tester = new CommandTester($application->find('documentation'));
        $exit = $tester->execute(['action' => 'status', '--source' => 'api', '--status' => 'new', '--json' => true]);
        self::assertSame(0, $exit, $tester->getDisplay());
        self::assertStringStartsWith('{', $tester->getDisplay());
        $report = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('docara.documentation_status.v1', $report['schema']);
        self::assertSame(1, $report['summary']['new']);
    }
}
