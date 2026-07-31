<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use JsonException;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class ComponentSourceMetadataRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $entries = [];

    public function __construct(
        private readonly string $path,
        private readonly SchemaRepository $schemas = new SchemaRepository,
    ) {
        if (! is_file($this->path) || is_link($this->path)) {
            throw new PortableConfigurationException(
                'COMPONENT_SOURCE_METADATA_MISSING',
                'The deterministic component source metadata snapshot is missing.',
            );
        }
        try {
            $snapshot = json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'COMPONENT_SOURCE_METADATA_INVALID',
                $exception->getMessage(),
                $exception,
            );
        }
        $this->schemas->assertValid($snapshot, 'component-source-metadata.schema.json');
        $expectedHash = hash('sha256', CanonicalJson::encode([
            'repository' => $snapshot['repository'],
            'revision' => $snapshot['revision'],
            'entries' => $snapshot['entries'],
        ]));
        if (! hash_equals($expectedHash, (string) $snapshot['content_sha256'])) {
            throw new PortableConfigurationException(
                'COMPONENT_SOURCE_METADATA_HASH_MISMATCH',
                'The deterministic component source metadata snapshot hash is invalid.',
            );
        }
        $previous = null;
        foreach ($snapshot['entries'] as $entry) {
            $sourceRef = (string) $entry['source_ref'];
            if ($previous !== null && strcmp($previous, $sourceRef) >= 0) {
                throw new PortableConfigurationException(
                    'COMPONENT_SOURCE_METADATA_ORDER_INVALID',
                    'Component source metadata entries must be strictly sorted and unique.',
                );
            }
            $this->entries[$sourceRef] = $entry;
            $previous = $sourceRef;
        }
    }

    /** @return array<string, mixed> */
    public function forSource(string $sourceRef): array
    {
        $entry = $this->entries[$sourceRef] ?? null;
        if (! is_array($entry)) {
            throw new PortableConfigurationException(
                'COMPONENT_SOURCE_METADATA_REFERENCE_MISSING',
                "Source metadata for [$sourceRef] is missing.",
            );
        }

        return $entry;
    }
}
