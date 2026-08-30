<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Documentation\DocumentationStatusService;

final readonly class SdkService
{
    public function __construct(
        public DiscoveryService $discovery,
        public ScaffoldService $scaffold,
        public ValidationService $validation,
        public ArtifactTestService $test,
        public QaService $qa,
        public DesignAtlasService $atlas,
        public DocumentationStatusService $documentation,
    ) {}

    /** @param array<string, mixed> $arguments */
    public function invoke(string $root, string $operation, array $arguments): OperationResult
    {
        return match ($operation) {
            'doctor' => $this->discovery->doctor($root),
            'list' => $this->discovery->list($root, $this->string($arguments, 'kind')),
            'inspect' => $this->discovery->inspect($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id')),
            'schema' => $this->discovery->schema($root, $this->string($arguments, 'kind')),
            'atlas' => $this->atlas->atlas($root),
            'scaffold.plan' => $this->scaffold->plan($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id'), [
                'locale' => $this->optionalString($arguments, 'locale'),
                'title' => $this->optionalString($arguments, 'title'),
                'profile' => $this->optionalString($arguments, 'profile'),
                'source' => $this->optionalString($arguments, 'source'),
                'entity' => $this->optionalString($arguments, 'entity'),
            ]),
            'scaffold.apply' => $this->scaffold->apply($root, $this->string($arguments, 'plan_id')),
            'validate' => $this->validation->validate($root, $this->string($arguments, 'kind'), $this->optionalString($arguments, 'id')),
            'test' => $this->test->test($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id'), $this->string($arguments, 'page')),
            'qa.plan' => $this->qa->plan($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id'), $this->string($arguments, 'page')),
            'qa.finalize_reference' => $this->qa->finalizeReference($root, $this->string($arguments, 'plan_id')),
            'qa.verify' => $this->qa->verify($root, $this->string($arguments, 'plan_id')),
            'documentation.status' => OperationResult::success('documentation.status', $this->optionalString($arguments, 'source'), $this->documentation->report(
                $root,
                $this->optionalString($arguments, 'source'),
                $this->optionalString($arguments, 'kind'),
                $this->optionalString($arguments, 'status'),
            )),
            'documentation.plan' => OperationResult::success('documentation.plan', $this->string($arguments, 'source') . ':' . $this->string($arguments, 'key'), $this->documentation->planAccept(
                $root,
                $this->string($arguments, 'source'),
                $this->string($arguments, 'key'),
                $this->string($arguments, 'route'),
                $this->string($arguments, 'review'),
                is_array($arguments['examples'] ?? null) ? $arguments['examples'] : [],
                $this->optionalString($arguments, 'exclude_reason'),
            )),
            'documentation.apply' => OperationResult::success('documentation.apply', $this->string($arguments, 'plan_id'), $this->documentation->apply($root, $this->string($arguments, 'plan_id'))),
            default => throw new \InvalidArgumentException('SDK_OPERATION_UNKNOWN:' . $operation),
        };
    }

    /** @param array<string, mixed> $arguments */
    private function string(array $arguments, string $key): string
    {
        $value = $arguments[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException('SDK_ARGUMENT_REQUIRED:' . $key);
        }

        return $value;
    }

    /** @param array<string, mixed> $arguments */
    private function optionalString(array $arguments, string $key): ?string
    {
        $value = $arguments[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
