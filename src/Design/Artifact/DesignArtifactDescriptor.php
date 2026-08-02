<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Artifact;

final readonly class DesignArtifactDescriptor
{
    /** @param array<string, mixed> $definition */
    public function __construct(
        public DesignArtifactKind $kind,
        public string $id,
        public string $ownerNamespace,
        public string $provider,
        public string $providerRevision,
        public string $relativePath,
        public string $sha256,
        public array $definition,
    ) {}

    /** @return array<string, string> */
    public function provenance(): array
    {
        return [
            'provider' => $this->provider,
            'provider_revision' => $this->providerRevision,
            'owner_namespace' => $this->ownerNamespace,
            'source' => $this->relativePath,
            'sha256' => $this->sha256,
        ];
    }
}
