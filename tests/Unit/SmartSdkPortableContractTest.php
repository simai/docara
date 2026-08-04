<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Smart\Artifact\DocaraPortableSmartAdmissionPolicy;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class SmartSdkPortableContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function public_schema_validates_unchanged_scaffold_and_every_effective_manifest(): void
    {
        $application = ApplicationFactory::create($this->tmp);
        $schema = $this->jsonCommand($application, 'schema', ['kind' => 'smart']);
        self::assertSame('smart.manifest.schema.json', $schema['data']['schema_id']);
        self::assertSame($this->golden()['contract'], $schema['data']['contract']);
        self::assertEquals(
            (new SchemaRepository)->get('smart.manifest.schema.json'),
            $schema['data']['schema'],
        );

        $scaffold = $this->jsonCommand($application, 'scaffold', [
            'kind' => 'smart',
            'id' => 'project.audit-card',
            '--dry-run' => true,
        ]);
        $plan = json_decode(
            (string) file_get_contents($this->tmpPath($scaffold['data']['plan_path'])),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $manifest = null;
        foreach ($plan['files'] as $file) {
            if ($file['path'] === 'smart/project.audit-card/manifest.json') {
                $manifest = json_decode(base64_decode($file['content_base64'], true), true, 512, JSON_THROW_ON_ERROR);
            }
        }
        self::assertIsArray($manifest);
        (new JsonSchemaValidator(new SchemaRepository))->assertValid($manifest, $schema['data']['schema_id']);
        (new DocaraPortableSmartAdmissionPolicy)->assertAdmitted($manifest, 'project.audit-card');

        $runtime = ProjectRuntime::load($this->tmp);
        foreach ($runtime->smarts->keys() as $id) {
            $definition = $runtime->smarts->definition($id);
            (new JsonSchemaValidator(new SchemaRepository))->assertValid(
                $definition->portableManifest,
                $schema['data']['schema_id'],
            );
        }
    }

    #[Test]
    public function cli_human_json_and_mcp_share_schema_and_neutral_provenance(): void
    {
        $application = ApplicationFactory::create($this->tmp);
        $adapter = new McpAdapter(SdkServiceFactory::create(), $this->tmp);
        $golden = $this->golden();

        $schema = $this->jsonCommand($application, 'schema', ['kind' => 'smart']);
        $mcpSchema = $this->mcp($adapter, 'docara_schema', ['kind' => 'smart']);
        self::assertEquals($schema, $mcpSchema);
        self::assertSame($golden['subject'], $schema['subject']);
        self::assertSame($golden['schema_id'], $schema['data']['schema_id']);
        self::assertSame($golden['contract'], $schema['data']['contract']);

        $humanSchema = new CommandTester($application->find('schema'));
        self::assertSame(0, $humanSchema->execute(['kind' => 'smart']));
        foreach (array_values($golden['contract']) as $value) {
            self::assertStringContainsString((string) $value, $humanSchema->getDisplay());
        }

        foreach ($golden['definitions'] as $id => $expected) {
            $inspect = $this->jsonCommand($application, 'inspect', ['kind' => 'smart', 'id' => $id]);
            $mcpInspect = $this->mcp($adapter, 'docara_inspect', ['kind' => 'smart', 'id' => $id]);
            self::assertEquals($inspect, $mcpInspect, $id);
            self::assertSame($expected['provider'], $inspect['data']['provider'], $id);
            self::assertSame('sf.smart_artifact_abi', $inspect['data']['provenance']['contract_id'], $id);
            self::assertSame('1.0.0', $inspect['data']['provenance']['contract_schema_version'], $id);
            self::assertSame('sf-smart-artifact-abi-v1', $inspect['data']['provenance']['contract_compatibility_id'], $id);
            self::assertSame('sf5.smart.artifact.v1', $inspect['data']['provenance']['storage_compatibility_alias'], $id);
            self::assertSame($expected['provider_adapter'], $inspect['data']['provenance']['provider_adapter'], $id);
            self::assertSame($expected['template_abi'], $inspect['data']['provenance']['template_abi'], $id);
            self::assertArrayNotHasKey('contract', $inspect['data']['provenance'], $id);
            self::assertArrayNotHasKey('legacy_adapter', $inspect['data']['provenance'], $id);
        }
    }

    #[Test]
    public function exact_owner_schema_accepts_owner_surface_and_rejects_invalid_nested_fields(): void
    {
        $schemas = new SchemaRepository;
        try {
            $schemas->get('declarative-smart-manifest.schema.json');
            self::fail('The retired legacy package manifest was still exposed as a public schema.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCHEMA_NOT_FOUND', $exception->errorCode);
        }

        $manifest = ProjectRuntime::load($this->tmp)->smarts->definition('project.notice')->portableManifest;
        $manifest['family'] = 'project.cards';
        $manifest['children'] = [['id' => 'child', 'smart' => 'ui.alert']];
        $manifest['constraints'] = [['scope' => 'documentation']];
        (new JsonSchemaValidator($schemas))->assertValid($manifest, 'smart.manifest.schema.json');

        $manifest['slots'] = ['main' => ['unexpected' => true]];
        try {
            (new JsonSchemaValidator($schemas))->assertValid($manifest, 'smart.manifest.schema.json');
            self::fail('An owner-invalid nested slot passed the exact owner schema.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
        }
    }

    #[Test]
    public function vendored_schema_is_the_exact_source_pinned_owner_blob(): void
    {
        $root = dirname(__DIR__, 2);
        $source = json_decode((string) file_get_contents($root . '/resources/contracts/sf5/smart/v1/source.json'), true, 512, JSON_THROW_ON_ERROR);
        $path = $root . '/resources/schemas/smart.manifest.schema.json';

        self::assertSame($source['tracked_files']['manifest_schema']['sha256'], hash_file('sha256', $path));
        self::assertSame('9d65a9b3d63567ef8a12dd43f5c3e24913e2659105b088778dc50476a9578037', hash_file('sha256', $path));
    }

    #[Test]
    public function docara_cross_field_admission_is_separate_from_the_owner_schema(): void
    {
        $manifest = ProjectRuntime::load($this->tmp)->smarts->definition('project.notice')->portableManifest;
        $manifest['render']['strategy'] = 'server-first-hydratable';
        $manifest['render']['hydration'] = 'none';

        (new JsonSchemaValidator(new SchemaRepository))->assertValid($manifest, 'smart.manifest.schema.json');

        $this->expectException(\Simai\Docara\Smart\Artifact\PortableSmartContractException::class);
        $this->expectExceptionMessage('PORTABLE_SMART_CONTRACT_INVALID:project.notice:render.hydration');
        (new DocaraPortableSmartAdmissionPolicy)->assertAdmitted($manifest, 'project.notice');
    }

    /** @return array<string, mixed> */
    private function jsonCommand($application, string $name, array $arguments): array
    {
        $tester = new CommandTester($application->find($name));
        self::assertSame(0, $tester->execute($arguments + ['--json' => true]), $tester->getDisplay());

        return json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    private function mcp(McpAdapter $adapter, string $tool, array $arguments): array
    {
        $response = $adapter->handle([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => $arguments],
        ]);
        self::assertFalse($response['result']['isError']);

        return $response['result']['structuredContent'];
    }

    /** @return array<string, mixed> */
    private function golden(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/application/golden/smart-sdk-portable-contract.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
