<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Portable\CanonicalJson;
use Throwable;

final readonly class McpAdapter
{
    public function __construct(private SdkService $sdk, private string $root, private bool $allowWrites = false) {}

    /** @param array<string, mixed> $request @return array<string, mixed> */
    public function handle(array $request): array
    {
        $id = $request['id'] ?? null;
        try {
            $method = $request['method'] ?? null;
            if ($method === 'initialize') {
                return $this->success($id, [
                    'protocolVersion' => '2025-03-26',
                    'capabilities' => ['tools' => ['listChanged' => false]],
                    'serverInfo' => ['name' => 'docara-local-sdk', 'version' => '1.0.0'],
                    'instructions' => 'Project-local Docara SDK adapter. Apply is disabled unless the process starts with --allow-writes.',
                ]);
            }
            if ($method === 'notifications/initialized') {
                return [];
            }
            if ($method === 'tools/list') {
                return $this->success($id, ['tools' => $this->tools()]);
            }
            if ($method !== 'tools/call') {
                return $this->error($id, -32601, 'MCP_METHOD_NOT_FOUND');
            }
            $params = is_array($request['params'] ?? null) ? $request['params'] : [];
            $name = $params['name'] ?? null;
            $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
            if (! is_string($name) || ! isset($this->operations()[$name])) {
                return $this->error($id, -32602, 'MCP_TOOL_UNKNOWN');
            }
            $operation = $this->operations()[$name];
            if ($operation === 'scaffold.apply' && ! $this->allowWrites) {
                $result = (new OperationFailureMapper)->map(
                    $this->root,
                    $operation,
                    $arguments,
                    new \InvalidArgumentException('MCP_WRITE_CAPABILITY_REQUIRED:Scaffold apply is disabled for this MCP process.'),
                    3,
                );
            } else {
                $result = $this->sdk->invoke($this->root, $operation, $arguments);
            }

            return $this->success($id, [
                'content' => [['type' => 'text', 'text' => CanonicalJson::encodePretty($result->toArray())]],
                'structuredContent' => $result->toArray(),
                'isError' => $result->exitCode !== 0,
            ]);
        } catch (Throwable $exception) {
            $params = is_array($request['params'] ?? null) ? $request['params'] : [];
            $name = is_string($params['name'] ?? null) ? $params['name'] : 'unknown';
            $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
            $operation = $this->operations()[$name] ?? 'mcp.tool';
            $result = (new OperationFailureMapper)->map($this->root, $operation, $arguments, $exception);

            return $this->success($id, [
                'content' => [['type' => 'text', 'text' => CanonicalJson::encodePretty($result->toArray())]],
                'structuredContent' => $result->toArray(),
                'isError' => true,
            ]);
        }
    }

    /** @return list<array<string, mixed>> */
    private function tools(): array
    {
        $tools = [];
        foreach ($this->operations() as $name => $operation) {
            $tools[] = [
                'name' => $name,
                'description' => 'Delegate Docara application operation ' . $operation . '.',
                'inputSchema' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $this->properties($operation)],
                'annotations' => [
                    'readOnlyHint' => ! in_array($operation, ['scaffold.plan', 'scaffold.apply', 'qa.plan', 'qa.finalize_reference'], true),
                    'destructiveHint' => false,
                    'idempotentHint' => true,
                    'openWorldHint' => false,
                ],
            ];
        }

        return $tools;
    }

    /** @return array<string, string> */
    private function operations(): array
    {
        return [
            'docara_doctor' => 'doctor', 'docara_list' => 'list', 'docara_inspect' => 'inspect', 'docara_schema' => 'schema',
            'docara_atlas' => 'atlas',
            'docara_scaffold_plan' => 'scaffold.plan', 'docara_scaffold_apply' => 'scaffold.apply',
            'docara_validate' => 'validate', 'docara_test' => 'test', 'docara_qa_plan' => 'qa.plan',
            'docara_qa_finalize_reference' => 'qa.finalize_reference', 'docara_qa_verify' => 'qa.verify',
        ];
    }

    /** @return array<string, mixed> */
    private function properties(string $operation): array
    {
        $string = ['type' => 'string', 'minLength' => 1];

        return match ($operation) {
            'doctor', 'atlas' => [],
            'list', 'schema' => ['kind' => $string],
            'inspect', 'validate' => ['kind' => $string, 'id' => $string],
            'scaffold.plan' => ['kind' => $string, 'id' => $string, 'locale' => $string, 'title' => $string, 'profile' => $string],
            'scaffold.apply', 'qa.finalize_reference', 'qa.verify' => ['plan_id' => $string],
            'test', 'qa.plan' => ['kind' => $string, 'id' => $string, 'page' => $string],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function success(mixed $id, array $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /** @return array<string, mixed> */
    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
