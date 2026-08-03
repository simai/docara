<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

final readonly class OperationResult
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<Diagnostic>  $diagnostics
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public string $operation,
        public string $status,
        public int $exitCode,
        public ?string $subject,
        public array $data = [],
        public array $diagnostics = [],
        public array $provenance = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $diagnostics = array_map(static fn (Diagnostic $diagnostic): array => $diagnostic->toArray(), $this->diagnostics);
        usort($diagnostics, static fn (array $left, array $right): int => [
            $left['severity'], $left['code'], $left['source']['path'] ?? '', $left['source']['pointer'] ?? '',
        ] <=> [
            $right['severity'], $right['code'], $right['source']['path'] ?? '', $right['source']['pointer'] ?? '',
        ]);

        return [
            'schema' => 'docara.operation_result.v1',
            'operation' => $this->operation,
            'status' => $this->status,
            'exit_code' => $this->exitCode,
            'subject' => $this->subject,
            'data' => $this->data,
            'diagnostics' => $diagnostics,
            'provenance' => $this->provenance,
        ];
    }

    public static function success(string $operation, ?string $subject, array $data = [], array $provenance = []): self
    {
        $provenance += self::fallbackProvenance();

        return new self($operation, 'success', 0, $subject, $data, [], $provenance);
    }

    public static function failure(string $operation, ?string $subject, Diagnostic $diagnostic, int $exitCode = 2): self
    {
        return new self($operation, 'error', $exitCode, $subject, [], [$diagnostic], self::fallbackProvenance());
    }

    /** @return array{engine_revision:string,project_root:string,input_sha256:string} */
    private static function fallbackProvenance(): array
    {
        return [
            'engine_revision' => 'unresolved',
            'project_root' => '.',
            'input_sha256' => str_repeat('0', 64),
        ];
    }
}
