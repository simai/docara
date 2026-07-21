<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class EffectiveComponentCatalogValidator
{
    public function __construct(
        private SchemaRepository $schemas = new SchemaRepository,
        private FrameworkConsumerPolicy $consumerPolicy = new FrameworkConsumerPolicy,
    ) {}

    /** @param array<string, mixed> $catalog */
    public function assertValid(array $catalog): void
    {
        $nonclaims = $catalog['nonclaims'] ?? null;
        foreach ([
            'catalog_is_canonical_framework_registry',
            'all_framework_components_supported',
            'production_ready',
            'public_release_ready',
        ] as $claim) {
            if (! is_array($nonclaims) || ($nonclaims[$claim] ?? null) !== false) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_OVERCLAIM_FORBIDDEN',
                    'The effective catalogue must preserve all bounded nonclaims.',
                );
            }
        }

        $entries = $catalog['entries'] ?? null;
        if (! is_array($entries) || ! array_is_list($entries) || $entries === []) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_ENTRY_INVALID',
                'The effective catalogue must contain a non-empty entry list.',
            );
        }
        $ids = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['id'] ?? null)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ENTRY_INVALID',
                    'Every effective catalogue entry must have an ID.',
                );
            }
            $id = $entry['id'];
            if (isset($ids[$id])) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_DUPLICATE_ID',
                    'Effective catalogue IDs must be unique.',
                );
            }
            $ids[$id] = true;
        }

        $ordered = array_keys($ids);
        $sorted = $ordered;
        sort($sorted, SORT_STRING);
        if ($ordered !== $sorted) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_ORDER_INVALID',
                'Effective catalogue entries must be sorted by ID.',
            );
        }

        foreach ($entries as $entry) {
            $this->assertFamilyContract($entry, $catalog);
            $lifecycle = $entry['lifecycle'] ?? null;
            if ($lifecycle === 'supported') {
                $verification = $entry['verification'] ?? null;
                if (! is_array($verification)
                    || ($verification['renderer'] ?? null) !== true
                    || ($verification['tests'] ?? null) !== true
                    || ($verification['docs'] ?? null) !== true
                    || ($verification['demo'] ?? null) !== true
                ) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SUPPORTED_EVIDENCE_REQUIRED',
                        'Every supported catalogue entry needs renderer, tests, docs and demo evidence.',
                    );
                }
            } else {
                if (($entry['verification']['demo'] ?? null) !== false) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_UNSUPPORTED_DEMO_FORBIDDEN',
                        'An unavailable catalogue entry cannot claim a live demo.',
                    );
                }
                $gap = $entry['gap'] ?? null;
                if (! is_array($gap) || ! is_string($gap['owner'] ?? null) || trim($gap['owner']) === '') {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_GAP_OWNER_REQUIRED',
                        'Every unavailable catalogue entry needs a technical owner.',
                    );
                }
            }

            foreach ($this->referencedPaths($entry) as $path) {
                if (! $this->isSafeRelativePath($path)) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_PATH_INVALID',
                        'Catalogue references must be safe package-relative paths.',
                    );
                }
            }

            $encodedEntry = CanonicalJson::encode($entry);
            $encodedProvenance = CanonicalJson::encode($entry['provenance'] ?? []);
            if (preg_match(
                '~(?:^|[/@])(?:main|master|latest)(?:$|[/])~i',
                $encodedEntry,
            ) === 1
                || $this->containsAbsoluteFilesystemReference($entry)
                || str_contains($encodedProvenance, '\\\\')
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_PROVENANCE_UNSAFE',
                    'Catalogue provenance cannot contain moving references or absolute paths.',
                );
            }
        }

        $expectedContentHash = hash('sha256', CanonicalJson::encode($entries));
        if (! is_string($catalog['content_sha256'] ?? null)
            || ! hash_equals($expectedContentHash, $catalog['content_sha256'])
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_HASH_MISMATCH',
                'The effective catalogue content hash does not match its entries.',
            );
        }

        $this->schemas->assertValid($catalog, 'effective-component-catalog.schema.json');
    }

    /** @param array<string, mixed> $entry @param array<string, mixed> $catalog */
    private function assertFamilyContract(array $entry, array $catalog): void
    {
        $id = (string) ($entry['id'] ?? '');
        $family = $entry['family'] ?? null;
        $lifecycle = $entry['lifecycle'] ?? null;
        $syntax = $entry['authoring']['syntax'] ?? null;
        $sourceKind = $entry['provenance']['source_kind'] ?? null;

        if ($family === 'native_markdown') {
            if ($lifecycle !== 'supported'
                || $syntax !== 'markdown'
                || $sourceKind !== 'portable_markdown_profile'
                || ($entry['provenance']['profile_id'] ?? null) !== 'docara.portable_markdown_profile.v1'
                || isset($entry['consumer_policy'])
                || isset($entry['gap'])
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_FAMILY_CONTRACT_INVALID',
                    "Native Markdown catalogue entry [$id] does not match the enabled profile.",
                );
            }

            return;
        }

        if ($family === 'docara_typed') {
            $name = str_starts_with($id, 'docara.') ? substr($id, strlen('docara.')) : '';
            if ($lifecycle !== 'supported'
                || $syntax !== 'directive'
                || $sourceKind !== 'typed_definition'
                || ($entry['authoring']['call'] ?? null) !== ':::' . $name
                || isset($entry['consumer_policy'])
                || isset($entry['gap'])
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_FAMILY_CONTRACT_INVALID',
                    "Typed Docara catalogue entry [$id] does not match its directive contract.",
                );
            }

            return;
        }

        if ($family === 'framework_smart') {
            $provenance = $entry['provenance'] ?? null;
            if ($lifecycle !== 'supported'
                || $syntax !== 'directive_json'
                || ($entry['authoring']['call'] ?? null) !== ':::' . $id
                || $sourceKind !== 'smart_consumer_metadata'
                || ! is_array($provenance)
                || ($provenance['provider'] ?? null) !== 'larena/ui'
                || ($provenance['provider_revision'] ?? null) !== ($catalog['provider_revision'] ?? null)
                || ($provenance['runtime_pair'] ?? null) !== ($catalog['framework_pair'] ?? null)
                || ! is_string($provenance['manifest_sha256'] ?? null)
                || preg_match('/^[a-f0-9]{64}$/D', $provenance['manifest_sha256']) !== 1
                || ! is_string($provenance['upstream_revision'] ?? null)
                || preg_match('/^[a-f0-9]{40}$/D', $provenance['upstream_revision']) !== 1
                || isset($entry['gap'])
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_PROVENANCE_MISMATCH',
                    "Smart catalogue entry [$id] does not match the exact root admission contract.",
                );
            }
            try {
                $expectedPolicy = $this->consumerPolicy->catalogMetadata($id);
            } catch (FrameworkComponentException $exception) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_POLICY_MISMATCH',
                    "Smart catalogue entry [$id] has no accepted consumer policy.",
                    $exception,
                );
            }
            if (($entry['consumer_policy'] ?? null) !== $expectedPolicy) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_POLICY_MISMATCH',
                    "Smart catalogue entry [$id] does not match its accepted consumer policy.",
                );
            }

            return;
        }

        if ($family === 'requirement') {
            if ($lifecycle === 'supported'
                || $sourceKind !== 'product_requirement'
                || ! in_array($syntax, ['proposed_directive', 'unavailable'], true)
                || isset($entry['consumer_policy'])
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_FAMILY_CONTRACT_INVALID',
                    "Requirement catalogue entry [$id] is incorrectly executable.",
                );
            }

            return;
        }

        throw new PortableConfigurationException(
            'COMPONENT_CATALOG_FAMILY_CONTRACT_INVALID',
            "Catalogue entry [$id] has an unknown family.",
        );
    }

    /** @param array<string, mixed> $entry
     * @return list<string>
     */
    private function referencedPaths(array $entry): array
    {
        $paths = [];
        foreach (['docs_ref'] as $key) {
            if (is_string($entry[$key] ?? null)) {
                $paths[] = $entry[$key];
            }
        }
        $provenance = $entry['provenance'] ?? null;
        if (is_array($provenance)) {
            foreach (['definition_ref', 'manifest_ref', 'admission_authority'] as $key) {
                if (is_string($provenance[$key] ?? null)) {
                    $paths[] = $provenance[$key];
                }
            }
        }

        return $paths;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === ''
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path) === 1
            || str_contains($path, '\\')
            || str_contains($path, "\0")
        ) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function containsAbsoluteFilesystemReference(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsAbsoluteFilesystemReference($item)) {
                    return true;
                }
            }

            return false;
        }
        if (! is_string($value)) {
            return false;
        }

        return preg_match(
            '~(?:^|[\s("\'])'
            . '(?:/(?:'
            . 'Applications|Library|Network|System|Users|Volumes'
            . '|bin|boot|dev|etc|home|lib|lib64|media|mnt|opt|private|proc'
            . '|root|run|sbin|srv|sys|tmp|usr|var'
            . ')/'
            . '|[A-Za-z]:[\\\\/]'
            . '|\\\\\\\\[^\\\\\s]+\\\\)'
            . '~u',
            $value,
        ) === 1;
    }
}
