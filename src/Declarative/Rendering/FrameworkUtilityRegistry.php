<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering;

use Simai\Docara\Portable\PortableConfigurationException;

final class FrameworkUtilityRegistry
{
    /** @var array<string, true>|null */
    private ?array $utilities = null;

    public function __construct(
        private readonly string $path = __DIR__ . '/../../../resources/framework/view-utilities.json',
    ) {}

    /** @param list<string> $utilities */
    public function assertAllowed(array $utilities): void
    {
        $allowed = $this->utilities();
        foreach ($utilities as $utility) {
            if (! is_string($utility) || ! isset($allowed[$utility])) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_VIEW_UTILITY_NOT_ALLOWED',
                    "Framework utility [$utility] is not present in the pinned View Tree projection.",
                );
            }
        }
    }

    /** @return array<string, mixed> */
    public function provenance(): array
    {
        $document = $this->document();

        return [
            'source' => 'framework/view-utilities.json',
            'sha256' => hash_file('sha256', $this->path),
            'compatibility_id' => $document['compatibility_id'],
            'registry_sha256' => $document['registry_sha256'],
        ];
    }

    /** @return array<string, true> */
    private function utilities(): array
    {
        if ($this->utilities !== null) {
            return $this->utilities;
        }
        $utilities = $this->document()['utilities'] ?? null;
        if (! is_array($utilities) || ! array_is_list($utilities)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_UTILITY_REGISTRY_INVALID',
                'The pinned Framework View Tree utility projection is invalid.',
            );
        }

        return $this->utilities = array_fill_keys($utilities, true);
    }

    /** @return array<string, mixed> */
    private function document(): array
    {
        if (! is_file($this->path) || is_link($this->path)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_UTILITY_REGISTRY_MISSING',
                'The pinned Framework View Tree utility projection is missing.',
            );
        }
        try {
            $document = json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_UTILITY_REGISTRY_INVALID',
                'The pinned Framework View Tree utility projection is invalid.',
                $exception,
            );
        }
        if (! is_array($document)
            || ($document['schema'] ?? null) !== 'docara.framework_view_utilities.v1'
            || ($document['compatibility_id'] ?? null) !== 'sf-v5.3.2-7e836d8a-dd786bba'
            || ($document['registry_sha256'] ?? null) !== '2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7'
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_VIEW_UTILITY_REGISTRY_INVALID',
                'The pinned Framework View Tree utility projection does not match the runtime lock.',
            );
        }

        return $document;
    }
}
