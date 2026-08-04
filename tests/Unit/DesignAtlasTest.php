<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\DesignAtlasService;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\SchemaRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class DesignAtlasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function atlas_is_a_deterministic_projection_of_admitted_registries(): void
    {
        $service = new DesignAtlasService;
        $first = $service->atlas($this->tmp)->toArray();
        $second = $service->atlas($this->tmp)->toArray();

        self::assertSame($first, $second);
        (new JsonSchemaValidator(new SchemaRepository))->assertValid($first['data'], 'design-atlas.schema.json');
        self::assertSame(count($first['data']['entries']), $first['data']['count']);
        $core = $first['data'];
        unset($core['count'], $core['fingerprint']);
        self::assertSame(hash('sha256', CanonicalJson::encode($core)), $first['data']['fingerprint']);
        self::assertSame('admitted_registry_descriptor', $first['data']['vocabulary']['typing_source']);
        self::assertSame('none', $first['data']['vocabulary']['fence_length_semantics']);
    }

    #[Test]
    public function ownership_authoring_and_container_contracts_are_independent_and_bounded(): void
    {
        $entries = (new DesignAtlasService)->atlas($this->tmp)->toArray()['data']['entries'];
        $layout = $this->entry($entries, 'layout', 'docara.docs');
        $section = $this->entry($entries, 'section', 'docara.article');
        $smart = $this->entry($entries, 'smart', 'project.notice');
        $preset = $this->entry($entries, 'preset', 'docara.navigation:header');

        self::assertSame('docara', $layout['owner']);
        self::assertSame('container', $layout['authoring_kind']);
        self::assertSame(['section'], $layout['container_contract']['allowed_children']);
        self::assertSame(3, $layout['container_contract']['max_depth']);
        self::assertSame(64, $section['container_contract']['max_children']);
        self::assertSame('project/project', $smart['owner']);
        self::assertSame('block', $smart['authoring_kind']);
        self::assertNull($smart['container_contract']);
        self::assertSame('docara', $preset['owner']);
        self::assertSame('configuration', $preset['authoring_kind']);
    }

    #[Test]
    public function cli_json_human_and_mcp_are_projections_of_one_atlas_service(): void
    {
        $application = ApplicationFactory::create($this->tmp);
        $json = new CommandTester($application->find('atlas'));
        self::assertSame(0, $json->execute(['--json' => true]));
        $jsonResult = json_decode($json->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        $human = new CommandTester($application->find('atlas'));
        self::assertSame(0, $human->execute([]));
        self::assertStringContainsString($jsonResult['data']['fingerprint'], $human->getDisplay());

        $mcp = (new McpAdapter(SdkServiceFactory::create(), $this->tmp))->handle([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'docara_atlas', 'arguments' => []],
        ]);
        self::assertFalse($mcp['result']['isError']);
        self::assertEquals($jsonResult, $mcp['result']['structuredContent']);
    }

    #[Test]
    public function atlas_cannot_publish_unregistered_files_as_definitions(): void
    {
        $before = (new DesignAtlasService)->atlas($this->tmp)->toArray()['data'];
        $this->filesystem->ensureDirectoryExists($this->tmpPath('design'));
        $this->filesystem->put($this->tmpPath('design/README.txt'), "not an artifact\n");
        $after = (new DesignAtlasService)->atlas($this->tmp)->toArray()['data'];

        self::assertSame($before, $after);
    }

    /** @param list<array<string, mixed>> $entries @return array<string, mixed> */
    private function entry(array $entries, string $kind, string $id): array
    {
        foreach ($entries as $entry) {
            if ($entry['kind'] === $kind && $entry['id'] === $id) {
                return $entry;
            }
        }

        self::fail("Missing Atlas entry [$kind:$id].");
    }
}
