<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Binding;

final readonly class BindingDescriptor
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $presentations
     * @param  list<string>  $ownedProps
     * @param  list<string>  $storageCompatibilityAliases
     */
    public function __construct(
        public string $id,
        public string $ownerNamespace,
        public string $provider,
        public string $providerRevision,
        public array $capabilities,
        public string $smart,
        public array $presentations,
        public array $ownedProps,
        public string $outputSchema,
        public string $source,
        public string $sha256,
        public BindingResolver $resolver,
        public array $storageCompatibilityAliases = [],
    ) {}

    /** @return array<string, mixed> */
    public function provenance(): array
    {
        return [
            'binding' => $this->id,
            'owner_namespace' => $this->ownerNamespace,
            'provider' => $this->provider,
            'provider_revision' => $this->providerRevision,
            'capabilities' => $this->capabilities,
            'smart' => $this->smart,
            'presentations' => $this->presentations,
            'output_schema' => $this->outputSchema,
            'source' => $this->source,
            'sha256' => $this->sha256,
            'storage_compatibility_aliases' => $this->storageCompatibilityAliases,
        ];
    }
}
