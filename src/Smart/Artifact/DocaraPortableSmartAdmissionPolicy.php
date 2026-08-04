<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Artifact;

use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\SchemaRepository;

/**
 * Docara-owned consumer admission layered after the byte-exact Framework schema.
 * It is not a public ABI or an alternative manifest dialect.
 */
final readonly class DocaraPortableSmartAdmissionPolicy
{
    public const OWNER_SCHEMA = 'smart.manifest.schema.json';

    public const POLICY_ID = 'docara.smart_admission.v1';

    public function __construct(
        private JsonSchemaValidator $schemas = new JsonSchemaValidator(new SchemaRepository),
        private Sf5SmartArtifactV1Contract $contract = new Sf5SmartArtifactV1Contract,
    ) {}

    /** @param array<string, mixed> $manifest */
    public function assertAdmitted(array $manifest, string $expectedCode): void
    {
        $this->schemas->assertValid($manifest, self::OWNER_SCHEMA);
        $this->contract->assertManifest($manifest, $expectedCode);
    }
}

