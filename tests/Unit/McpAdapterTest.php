<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Tests\TestCase;

final class McpAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
    }

    #[Test]
    public function capabilities_and_read_tools_delegate_the_same_application_results(): void
    {
        $adapter = new McpAdapter(SdkServiceFactory::create(), $this->tmp);
        $initialize = $adapter->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize']);
        self::assertSame('docara-local-sdk', $initialize['result']['serverInfo']['name']);

        $tools = $adapter->handle(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);
        self::assertCount(10, $tools['result']['tools']);
        self::assertContains('docara_inspect', array_column($tools['result']['tools'], 'name'));

        $call = $adapter->handle(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => [
            'name' => 'docara_inspect', 'arguments' => ['kind' => 'smart', 'id' => 'ui.alert'],
        ]]);
        self::assertFalse($call['result']['isError']);
        self::assertSame('inspect', $call['result']['structuredContent']['operation']);
        self::assertSame('framework.lock', $call['result']['structuredContent']['data']['provider']);
    }

    #[Test]
    public function plan_delegates_but_apply_requires_explicit_process_write_capability(): void
    {
        $readOnly = new McpAdapter(SdkServiceFactory::create(), $this->tmp);
        $plan = $readOnly->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => 'docara_scaffold_plan', 'arguments' => ['kind' => 'smart', 'id' => 'project.card'],
        ]]);
        $planId = $plan['result']['structuredContent']['data']['plan_id'];
        $denied = $readOnly->handle(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => [
            'name' => 'docara_scaffold_apply', 'arguments' => ['plan_id' => $planId],
        ]]);
        self::assertTrue($denied['result']['isError']);
        self::assertSame('MCP_WRITE_CAPABILITY_REQUIRED', $denied['result']['structuredContent']['diagnostics'][0]['code']);
        self::assertDirectoryDoesNotExist($this->tmpPath('smart/project.card'));

        $write = new McpAdapter(SdkServiceFactory::create(), $this->tmp, true);
        $applied = $write->handle(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => [
            'name' => 'docara_scaffold_apply', 'arguments' => ['plan_id' => $planId],
        ]]);
        self::assertFalse($applied['result']['isError']);
        self::assertFileExists($this->tmpPath('smart/project.card/manifest.json'));
    }

    #[Test]
    public function unknown_tools_and_external_root_arguments_fail_closed(): void
    {
        $adapter = new McpAdapter(SdkServiceFactory::create(), $this->tmp, true);
        $unknown = $adapter->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'shell_exec']]);
        self::assertSame(-32602, $unknown['error']['code']);

        $tools = $adapter->handle(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);
        foreach ($tools['result']['tools'] as $tool) {
            self::assertArrayNotHasKey('root', $tool['inputSchema']['properties']);
            self::assertArrayNotHasKey('path', $tool['inputSchema']['properties']);
        }
    }
}
