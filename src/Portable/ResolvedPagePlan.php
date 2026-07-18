<?php

namespace Simai\Docara\Portable;

final readonly class ResolvedPagePlan
{
    public const CONTRACT_VERSION = 1;

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $frameworkLock
     * @param  list<array<string, mixed>>  $trace
     * @param  array<string, string>  $provenance
     */
    public function __construct(
        public string $page,
        public string $markdown,
        public array $configuration,
        public array $frameworkLock,
        public array $trace,
        public array $provenance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'page' => $this->page,
            'markdown' => $this->markdown,
            'configuration' => $this->configuration,
            'framework_lock' => $this->frameworkLock,
            'trace' => $this->trace,
            'provenance' => $this->provenance,
        ];
    }

    public function canonicalHash(): string
    {
        return hash('sha256', CanonicalJson::encode([
            'contract_version' => self::CONTRACT_VERSION,
            'page' => $this->page,
            'markdown' => $this->markdown,
            'configuration' => $this->configuration,
            'framework_lock' => $this->frameworkLock,
            'provenance' => $this->provenance,
        ]));
    }
}
