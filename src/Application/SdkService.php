<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

final readonly class SdkService
{
    public function __construct(
        public DiscoveryService $discovery,
        public ScaffoldService $scaffold,
        public ValidationService $validation,
        public ArtifactTestService $test,
        public QaService $qa,
        public DesignAtlasService $atlas,
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
            'scaffold.plan' => $this->scaffold->plan($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id')),
            'scaffold.apply' => $this->scaffold->apply($root, $this->string($arguments, 'plan_id')),
            'validate' => $this->validation->validate($root, $this->string($arguments, 'kind'), $this->optionalString($arguments, 'id')),
            'test' => $this->test->test($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id'), $this->string($arguments, 'page')),
            'qa.plan' => $this->qa->plan($root, $this->string($arguments, 'kind'), $this->string($arguments, 'id'), $this->string($arguments, 'page')),
            'qa.finalize_reference' => $this->qa->finalizeReference($root, $this->string($arguments, 'plan_id')),
            'qa.verify' => $this->qa->verify($root, $this->string($arguments, 'plan_id')),
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
