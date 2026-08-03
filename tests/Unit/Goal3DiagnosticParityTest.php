<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class Goal3DiagnosticParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function actual_cli_json_human_and_mcp_preserve_one_complete_failure_contract(): void
    {
        $arguments = ['kind' => 'smart', 'id' => 'project.missing'];
        $jsonCommand = new CommandTester(ApplicationFactory::create($this->tmp)->find('inspect'));
        self::assertSame(2, $jsonCommand->execute($arguments + ['--json' => true]));
        $json = json_decode($jsonCommand->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        (new JsonSchemaValidator(new SchemaRepository))->assertValid($json, 'operation-result.schema.json');

        self::assertSame('inspect', $json['operation']);
        self::assertSame('smart:project.missing', $json['subject']);
        self::assertSame('SMART_REGISTRY_COMPONENT_NOT_FOUND', $json['diagnostics'][0]['code']);
        self::assertSame(['path' => 'command', 'pointer' => '/arguments/id'], $json['diagnostics'][0]['source']);
        self::assertSame('docara.application', $json['diagnostics'][0]['owner']);
        self::assertNotEmpty($json['diagnostics'][0]['provenance']);
        self::assertNotSame('unresolved', $json['provenance']['engine_revision']);
        self::assertNotSame(str_repeat('0', 64), $json['provenance']['input_sha256']);
        self::assertNotEmpty($json['diagnostics'][0]['suggestion']);

        $humanCommand = new CommandTester(ApplicationFactory::create($this->tmp)->find('inspect'));
        self::assertSame(2, $humanCommand->execute($arguments));
        $human = $humanCommand->getDisplay();
        foreach (['ERROR: inspect [smart:project.missing]', 'SMART_REGISTRY_COMPONENT_NOT_FOUND',
            'Source: command#/arguments/id', 'Owner: docara.application', 'Provenance:', 'Suggestion:'] as $needle) {
            self::assertStringContainsString($needle, $human);
        }

        $mcp = (new McpAdapter(SdkServiceFactory::create(), $this->tmp))->handle([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => 'docara_inspect', 'arguments' => $arguments],
        ]);
        self::assertTrue($mcp['result']['isError']);
        self::assertSame(CanonicalJson::encode($json), CanonicalJson::encode($mcp['result']['structuredContent']));
        self::assertSame(2, $mcp['result']['structuredContent']['exit_code']);
        self::assertStringNotContainsString($this->tmp, json_encode($mcp, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function malformed_project_config_preserves_file_location_across_actual_cli_and_mcp(): void
    {
        $this->filesystem->put($this->tmpPath('docara.json'), "{\n    \"schema\": \"docara.site.v1\",\n}\n");

        $this->assertFileFailureParity(
            'doctor',
            [],
            'docara_doctor',
            'SDK_PROJECT_CONFIG_INVALID',
            'docara.json',
            2,
            31,
        );
    }

    #[Test]
    public function malformed_project_smart_manifest_preserves_file_location_across_actual_cli_and_mcp(): void
    {
        $path = 'smart/project.notice/manifest.json';
        $this->filesystem->put($this->tmpPath($path), "{\n    project: true\n}\n");

        $this->assertFileFailureParity(
            'inspect',
            ['kind' => 'smart', 'id' => 'project.notice'],
            'docara_inspect',
            'SMART_PROVIDER_JSON_INVALID',
            $path,
            2,
            5,
        );
    }

    #[Test]
    public function schema_rejects_nullable_source_empty_provenance_and_unbound_hashes(): void
    {
        $validator = new JsonSchemaValidator(new SchemaRepository);
        $valid = $this->actualFailure();
        foreach (['null_source', 'empty_provenance', 'unresolved', 'zero_hash', 'file_line', 'file_column'] as $mutation) {
            $invalid = $valid;
            match ($mutation) {
                'null_source' => $invalid['diagnostics'][0]['source'] = null,
                'empty_provenance' => $invalid['diagnostics'][0]['provenance'] = [],
                'unresolved' => $invalid['provenance']['engine_revision'] = 'unresolved',
                'zero_hash' => $invalid['provenance']['input_sha256'] = str_repeat('0', 64),
                'file_line' => $invalid['diagnostics'][0]['source'] = ['path' => 'docara.json', 'pointer' => '/', 'column' => 1],
                'file_column' => $invalid['diagnostics'][0]['source'] = ['path' => 'docara.json', 'pointer' => '/', 'line' => 1],
            };
            try {
                $validator->assertValid($invalid, 'operation-result.schema.json');
                self::fail("Invalid operation result mutation [$mutation] was accepted.");
            } catch (PortableConfigurationException) {
                self::assertTrue(true);
            }
        }
    }

    /** @param array<string, mixed> $arguments */
    private function assertFileFailureParity(
        string $command,
        array $arguments,
        string $mcpTool,
        string $code,
        string $path,
        int $line,
        int $column,
    ): void {
        $jsonCommand = new CommandTester(ApplicationFactory::create($this->tmp)->find($command));
        self::assertSame(2, $jsonCommand->execute($arguments + ['--json' => true]));
        $json = json_decode($jsonCommand->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        (new JsonSchemaValidator(new SchemaRepository))->assertValid($json, 'operation-result.schema.json');
        self::assertSame($command, $json['operation']);
        self::assertSame($code, $json['diagnostics'][0]['code']);
        self::assertSame(['column' => $column, 'line' => $line, 'path' => $path, 'pointer' => '/'], $json['diagnostics'][0]['source']);
        self::assertNotEmpty($json['diagnostics'][0]['owner']);
        self::assertNotEmpty($json['diagnostics'][0]['provenance']);
        self::assertNotEmpty($json['diagnostics'][0]['suggestion']);

        $humanCommand = new CommandTester(ApplicationFactory::create($this->tmp)->find($command));
        self::assertSame(2, $humanCommand->execute($arguments));
        self::assertStringContainsString("Source: {$path}:{$line}:{$column}#/", $humanCommand->getDisplay());

        $mcp = (new McpAdapter(SdkServiceFactory::create(), $this->tmp))->handle([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $mcpTool, 'arguments' => $arguments],
        ]);
        self::assertTrue($mcp['result']['isError']);
        self::assertSame(CanonicalJson::encode($json), CanonicalJson::encode($mcp['result']['structuredContent']));
        self::assertStringNotContainsString($this->tmp, json_encode($mcp, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function actualFailure(): array
    {
        $tester = new CommandTester(ApplicationFactory::create($this->tmp)->find('inspect'));
        self::assertSame(2, $tester->execute(['kind' => 'smart', 'id' => 'project.missing', '--json' => true]));

        return json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
    }
}
