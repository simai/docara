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
            $this->assertPresentationContract($entry);
            if ($lifecycle === 'supported') {
                $verification = $entry['verification'] ?? null;
                if (! is_array($verification)
                    || ($verification['renderer'] ?? null) !== true
                    || ($verification['tests'] ?? null) !== true
                    || ($verification['docs'] ?? null) !== true
                    || ($verification['demo'] ?? null) !== true
                    || ! is_string($entry['example_ref'] ?? null)
                ) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SUPPORTED_EVIDENCE_REQUIRED',
                        'Every supported catalogue entry needs renderer, tests, docs, demo and an example fixture.',
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
                foreach (['owner', 'reason', 'fallback', 'admission_condition'] as $key) {
                    if (! is_array($gap) || ! is_string($gap[$key] ?? null) || trim($gap[$key]) === '') {
                        throw new PortableConfigurationException(
                            'COMPONENT_CATALOG_GAP_EXPLANATION_REQUIRED',
                            'Every unavailable catalogue entry needs an owner, reason, fallback and admission condition.',
                        );
                    }
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

    /** @param array<string, mixed> $entry */
    private function assertPresentationContract(array $entry): void
    {
        $presentation = $entry['presentation']['ru'] ?? null;
        $limitations = $entry['limitations'] ?? null;
        if (! is_array($presentation)
            || ! is_array($limitations)
            || ! is_array($presentation['limitations'] ?? null)
            || count($presentation['limitations']) !== count($limitations)
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'The Russian presentation for [' . (string) ($entry['id'] ?? '')
                . '] must translate every catalogue limitation exactly once.',
            );
        }
        $stateLabels = $presentation['states'] ?? null;
        $states = $entry['states'] ?? null;
        if (! is_array($stateLabels)
            || ($stateLabels !== [] && array_is_list($stateLabels))
            || ! is_array($states)
            || $this->sortedStrings(array_keys($stateLabels)) !== $this->sortedStrings($states)
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'The Russian presentation for [' . (string) ($entry['id'] ?? '')
                . '] must label every admitted state exactly once.',
            );
        }
        $parameterPresentations = $presentation['parameters'] ?? null;
        $parameters = $entry['authoring']['parameters'] ?? null;
        if (! is_array($parameterPresentations)
            || ($parameterPresentations !== [] && array_is_list($parameterPresentations))
            || ! is_array($parameters)
            || $this->sortedStrings(array_keys($parameterPresentations))
                !== $this->sortedStrings(array_column($parameters, 'name'))
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'The Russian presentation for [' . (string) ($entry['id'] ?? '')
                . '] must describe every author parameter exactly once.',
            );
        }
        foreach ($parameters as $parameter) {
            if (! is_array($parameter) || ! is_string($parameter['name'] ?? null)) {
                continue;
            }
            $localizedValues = $parameterPresentations[$parameter['name']]['values'] ?? null;
            $values = $parameter['values'] ?? null;
            if (is_array($values)) {
                if (! is_array($localizedValues)
                    || ($localizedValues !== [] && array_is_list($localizedValues))
                    || $this->sortedStrings(array_keys($localizedValues))
                        !== $this->sortedStrings(array_map('strval', $values))
                ) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_PRESENTATION_INVALID',
                        'The Russian presentation for [' . (string) ($entry['id'] ?? '')
                        . ':' . $parameter['name'] . '] must label every value exactly once.',
                    );
                }
            } elseif ($localizedValues !== null) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_PRESENTATION_INVALID',
                    'A parameter without enumerated values cannot declare localized value labels.',
                );
            }
        }

        $supported = ($entry['lifecycle'] ?? null) === 'supported';
        if ($supported && ! is_string($presentation['example_ref'] ?? null)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'Every supported catalogue entry needs a localized Russian example fixture.',
            );
        }
        if (! $supported && array_key_exists('example_ref', $presentation)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'Unavailable catalogue entries cannot present a localized live example.',
            );
        }
        if ($supported && array_key_exists('gap', $presentation)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'Supported catalogue entries cannot present an availability gap.',
            );
        }
        if (! $supported && ! is_array($presentation['gap'] ?? null)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_INVALID',
                'Unavailable catalogue entries need a localized availability explanation.',
            );
        }
    }

    /** @param array<int, mixed> $values
     * @return list<string>
     */
    private function sortedStrings(array $values): array
    {
        $values = array_map('strval', $values);
        sort($values, SORT_STRING);

        return array_values($values);
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
                || isset($entry['example_ref'])
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
        foreach (['docs_ref', 'example_ref'] as $key) {
            if (is_string($entry[$key] ?? null)) {
                $paths[] = $entry[$key];
            }
        }
        $presentation = $entry['presentation'] ?? null;
        if (is_array($presentation)) {
            foreach ($presentation as $localized) {
                if (is_array($localized) && is_string($localized['example_ref'] ?? null)) {
                    $paths[] = $localized['example_ref'];
                }
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
