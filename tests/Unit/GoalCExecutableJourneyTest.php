<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Console\ApplicationFactory;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class GoalCExecutableJourneyTest extends TestCase
{
    private const PAGE = '/ru/project-demos/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function documented_discover_plan_preview_apply_validate_journey_uses_real_commands(): void
    {
        $application = ApplicationFactory::create($this->tmp);

        foreach ([
            ['doctor', []],
            ['list', ['kind' => 'smart']],
            ['atlas', []],
            ['inspect', ['kind' => 'smart', 'id' => 'project.install-builder']],
            ['schema', ['kind' => 'smart']],
        ] as [$name, $arguments]) {
            $result = $this->execute($application, $name, $arguments);
            self::assertSame('success', $result['status'], $name);
        }

        $plan = $this->execute($application, 'scaffold', [
            'kind' => 'smart',
            'id' => 'project.notice-card',
            '--dry-run' => true,
        ]);
        $planId = $plan['data']['plan_id'];
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $planId);

        $preview = $this->execute($application, 'preview', [
            'target' => 'smart',
            '--page' => self::PAGE,
            '--selector' => 'ui.dropdown',
        ]);
        self::assertFalse($preview['accepted_build_receipt']);

        $tested = $this->execute($application, 'test', [
            'kind' => 'smart',
            'id' => 'ui.dropdown',
            '--page' => self::PAGE,
        ]);
        self::assertSame('success', $tested['status']);
        self::assertFalse($tested['data']['accepted_build_receipt']);

        $applied = $this->execute($application, 'scaffold', ['--apply' => $planId]);
        self::assertSame($planId, $applied['data']['plan_id']);
        self::assertFileExists($this->tmpPath('smart/project.notice-card/manifest.json'));

        $validated = $this->execute($application, 'validate', [
            'kind' => 'smart',
            'id' => 'project.notice-card',
        ]);
        self::assertSame('success', $validated['status']);
    }

    #[Test]
    public function mcp_projects_the_same_read_and_hash_bound_write_boundaries(): void
    {
        $sdk = SdkServiceFactory::create();
        $readOnly = new McpAdapter($sdk, $this->tmp);
        $inspect = $readOnly->handle($this->request(1, 'docara_inspect', [
            'kind' => 'smart',
            'id' => 'project.install-builder',
        ]));
        self::assertFalse($inspect['result']['isError']);
        self::assertSame('inspect', $inspect['result']['structuredContent']['operation']);

        $plan = $readOnly->handle($this->request(2, 'docara_scaffold_plan', [
            'kind' => 'smart',
            'id' => 'project.mcp-notice',
        ]));
        $planId = $plan['result']['structuredContent']['data']['plan_id'];
        $denied = $readOnly->handle($this->request(3, 'docara_scaffold_apply', ['plan_id' => $planId]));
        self::assertTrue($denied['result']['isError']);
        self::assertSame('MCP_WRITE_CAPABILITY_REQUIRED', $denied['result']['structuredContent']['diagnostics'][0]['code']);
        self::assertDirectoryDoesNotExist($this->tmpPath('smart/project.mcp-notice'));

        $writeEnabled = new McpAdapter($sdk, $this->tmp, true);
        $applied = $writeEnabled->handle($this->request(4, 'docara_scaffold_apply', ['plan_id' => $planId]));
        self::assertFalse($applied['result']['isError']);
        self::assertSame($planId, $applied['result']['structuredContent']['data']['plan_id']);
    }

    #[Test]
    public function public_documentation_uses_only_the_executable_option_contract(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru';
        $documents = [
            (string) file_get_contents($root . '/development/agent-journey.md'),
            (string) file_get_contents($root . '/development/developer-sdk.md'),
            (string) file_get_contents($root . '/components/project.md'),
        ];
        $combined = implode("\n", $documents);

        self::assertStringContainsString('preview smart --page=/ru/project-demos/ --selector=ui.dropdown', $combined);
        self::assertStringContainsString('scaffold --apply="$PLAN_SHA256" --json', $combined);
        self::assertStringNotContainsString('--apply-plan', $combined);
        self::assertStringNotContainsString('/ru/example/', $combined);
        self::assertDoesNotMatchRegularExpression('/preview smart\s+project\.[^\s]+/', $combined);
        self::assertDoesNotMatchRegularExpression('/scaffold\s+smart\s+project\.[^\s]+\s+--apply=/', $combined);
    }

    /** @param array<string, mixed> $arguments @return array<string, mixed> */
    private function execute(object $application, string $command, array $arguments): array
    {
        $tester = new CommandTester($application->find($command));
        $exit = $tester->execute($arguments + ['--json' => true]);
        self::assertSame(0, $exit, $command . ': ' . $tester->getDisplay());

        return json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $arguments @return array<string, mixed> */
    private function request(int $id, string $name, array $arguments): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ];
    }
}
