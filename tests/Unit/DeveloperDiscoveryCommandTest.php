<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\SchemaRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class DeveloperDiscoveryCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copy(dirname(__DIR__, 2) . '/stubs/portable/docara.json', $this->tmpPath('docara.json'));
    }

    #[Test]
    public function human_and_json_are_views_of_the_same_ordered_registry_result(): void
    {
        $application = ApplicationFactory::create($this->tmp);
        $json = new CommandTester($application->find('list'));
        self::assertSame(0, $json->execute(['kind' => 'smart', '--json' => true]));
        $result = json_decode($json->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        (new JsonSchemaValidator(new SchemaRepository))->assertValid($result, 'operation-result.schema.json');

        self::assertSame('list', $result['operation']);
        self::assertSame(['docara.brand', 'docara.navigation', 'docara.preferences', 'docara.toc', 'ui.alert', 'ui.button'], array_column($result['data']['items'], 'id'));

        $human = new CommandTester($application->find('list'));
        self::assertSame(0, $human->execute(['kind' => 'smart']));
        foreach ($result['data']['items'] as $item) {
            self::assertStringContainsString($item['id'], $human->getDisplay());
        }
    }

    #[Test]
    public function doctor_inspect_and_schema_expose_effective_provenance(): void
    {
        $application = ApplicationFactory::create($this->tmp);
        foreach ([
            ['doctor', []],
            ['inspect', ['kind' => 'smart', 'id' => 'ui.alert']],
            ['schema', ['kind' => 'layout']],
        ] as [$name, $arguments]) {
            $tester = new CommandTester($application->find($name));
            $exit = $tester->execute($arguments + ['--json' => true]);
            self::assertSame(0, $exit, $name . ': ' . $tester->getDisplay());
            $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
            (new JsonSchemaValidator(new SchemaRepository))->assertValid($result, 'operation-result.schema.json');
            self::assertSame('.', $result['provenance']['project_root']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['provenance']['input_sha256']);
        }
    }

    #[Test]
    public function unknown_subjects_fail_with_stable_machine_diagnostics(): void
    {
        $application = ApplicationFactory::create($this->tmp);
        $tester = new CommandTester($application->find('inspect'));
        self::assertSame(2, $tester->execute(['kind' => 'smart', 'id' => 'project.unknown', '--json' => true]));
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('SMART_REGISTRY_COMPONENT_NOT_FOUND', $result['diagnostics'][0]['code']);
        self::assertSame('error', $result['diagnostics'][0]['severity']);
        self::assertSame(2, $result['exit_code']);
    }
}
