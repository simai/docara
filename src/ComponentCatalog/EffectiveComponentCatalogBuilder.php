<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use JsonException;
use Simai\Docara\Framework\FrameworkAdmissionPreflight;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Framework\FrameworkHostRenderer;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestContract;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkPropsValidator;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\PortableMarkdownProfile;

final readonly class EffectiveComponentCatalogBuilder
{
    public function __construct(
        private string $packageRoot,
        private PortableMarkdownProfile $nativeProfile,
        private TypedComponentDefinitionRepository $typedDefinitions,
        private FrameworkLock $frameworkLock,
        private FrameworkManifestRepository $manifests,
        private FrameworkConsumerPolicy $consumerPolicy,
        private SchemaRepository $schemas = new SchemaRepository,
        private EffectiveComponentCatalogValidator $validator = new EffectiveComponentCatalogValidator,
    ) {}

    public static function bundled(FrameworkLock $frameworkLock): self
    {
        $root = dirname(__DIR__, 2);

        return new self(
            $root,
            PortableMarkdownProfile::bundled(),
            TypedComponentDefinitionRepository::bundled(),
            $frameworkLock,
            FrameworkManifestRepository::bundled($frameworkLock),
            new FrameworkConsumerPolicy,
        );
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $entries = [];
        foreach ($this->nativeProfile->entries() as $entry) {
            $this->addEntry($entries, $entry);
        }
        foreach ($this->typedDefinitions->all() as $definition) {
            unset($definition['name'], $definition['renderer']);
            $this->addEntry($entries, $definition);
        }
        foreach ($this->smartEntries() as $entry) {
            $this->addEntry($entries, $entry);
        }
        foreach ($this->loadDirectory('requirements') as $entry) {
            if (($entry['family'] ?? null) !== 'requirement'
                || ($entry['lifecycle'] ?? null) === 'supported'
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_REQUIREMENT_EXECUTABLE_FORBIDDEN',
                    'Requirement records cannot admit executable components.',
                );
            }
            $this->addEntry($entries, $entry);
        }

        ksort($entries, SORT_STRING);
        $providerRevisions = [];
        foreach ($this->manifests->keys() as $key) {
            $providerRevisions[$this->manifests->providerRevision($key)] = true;
        }
        if (count($providerRevisions) !== 1) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PROVIDER_REVISION_MISMATCH',
                'The bounded effective catalogue requires one exact Smart provider revision.',
            );
        }

        $publicEntries = array_values($entries);
        $catalog = [
            'schema' => 'docara.effective_component_catalog.v1',
            'version' => 1,
            'framework_pair' => $this->frameworkLock->pairId(),
            'provider_revision' => (string) array_key_first($providerRevisions),
            'content_sha256' => hash('sha256', CanonicalJson::encode($publicEntries)),
            'entries' => $publicEntries,
            'nonclaims' => [
                'catalog_is_canonical_framework_registry' => false,
                'all_framework_components_supported' => false,
                'production_ready' => false,
                'public_release_ready' => false,
            ],
        ];
        $this->validator->assertValid($catalog);
        $this->assertReferencesExist($catalog);

        return $catalog;
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $entry
     */
    private function addEntry(array &$entries, array $entry): void
    {
        $id = $entry['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_ENTRY_INVALID',
                'An effective catalogue source is missing its ID.',
            );
        }
        if (isset($entries[$id])) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_DUPLICATE_ID',
                "Effective catalogue ID [$id] is declared by more than one owner source.",
            );
        }
        $entries[$id] = $entry;
    }

    /** @return list<array<string, mixed>> */
    private function smartEntries(): array
    {
        $metadata = [];
        foreach ($this->loadDirectory('smart') as $entry) {
            $id = $entry['id'] ?? null;
            if (! is_string($id)
                || ($entry['family'] ?? null) !== 'framework_smart'
                || ($entry['lifecycle'] ?? null) !== 'supported'
                || ($entry['authoring']['syntax'] ?? null) !== 'directive_json'
                || ($entry['authoring']['call'] ?? null) !== ':::' . $id
                || ($entry['provenance']['source_kind'] ?? null) !== 'smart_consumer_metadata'
                || array_key_exists('consumer_policy', $entry)
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_METADATA_INVALID',
                    'Smart catalogue metadata cannot duplicate admission or consumer-policy facts.',
                );
            }
            $provenance = $entry['provenance'] ?? null;
            if (! is_array($provenance)
                || array_intersect(
                    [
                        'provider',
                        'provider_revision',
                        'upstream_revision',
                        'manifest_sha256',
                        'runtime_pair',
                    ],
                    array_keys($provenance),
                ) !== []
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_METADATA_PROVENANCE_FORBIDDEN',
                    'Exact Smart provenance is derived from the admitted manifest and lock.',
                );
            }
            if (isset($metadata[$id])) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_DUPLICATE_ID',
                    "Smart catalogue ID [$id] is declared more than once.",
                );
            }
            $metadata[$id] = $entry;
        }
        ksort($metadata, SORT_STRING);
        if (array_keys($metadata) !== $this->manifests->keys()) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_ADMISSION_MISMATCH',
                'Smart catalogue metadata must exactly describe the manifests admitted by the lock.',
            );
        }
        $this->assertSmartAdmissionReady();

        $entries = [];
        foreach ($this->manifests->keys() as $key) {
            $manifest = $this->manifests->get($key);
            $this->consumerPolicy->assertNarrowing($key, $manifest);
            $entry = $metadata[$key];
            $this->assertSmartMetadataNarrowing($key, $entry, $manifest);
            if (($entry['provenance']['manifest_ref'] ?? null) !== $this->manifests->manifestReference($key)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_MANIFEST_REFERENCE_MISMATCH',
                    "Smart catalogue metadata for [$key] does not reference the admitted manifest.",
                );
            }
            $entry['authoring']['parameters'] = $this->manifestParameters($key, $manifest);
            $entry['authoring']['constraints'] = $this->manifestConstraints($key, $manifest);
            $entry['consumer_policy'] = $this->consumerPolicy->catalogMetadata($key);
            $lockRecord = $this->frameworkLock->manifest($key);
            $entry['provenance'] = array_merge($entry['provenance'], [
                'provider' => (string) $lockRecord['provider'],
                'provider_revision' => (string) $lockRecord['provider_revision'],
                'upstream_revision' => (string) ($manifest['provenance']['upstream_revision'] ?? ''),
                'manifest_sha256' => (string) $lockRecord['sha256'],
                'runtime_pair' => $this->frameworkLock->pairId(),
            ]);
            $entries[] = $entry;
        }

        return $entries;
    }

    /** @param array<string, mixed> $entry @param array<string, mixed> $manifest */
    private function assertSmartMetadataNarrowing(string $component, array $entry, array $manifest): void
    {
        $atlas = $manifest['atlas'] ?? null;
        $manifestStates = is_array($atlas) ? ($atlas['states'] ?? null) : null;
        $metadataStates = $entry['states'] ?? null;
        if (! is_array($atlas)
            || ($atlas['readiness']['safe_to_suggest'] ?? null) !== true
            || ($atlas['readiness']['safe_to_render'] ?? null) !== true
            || ! is_string($atlas['category'] ?? null)
            || $entry['category'] !== $atlas['category']
            || ! is_array($manifestStates)
            || ! array_is_list($manifestStates)
            || $manifestStates === []
            || count($manifestStates) !== count(array_unique($manifestStates))
            || ! is_array($metadataStates)
            || ! array_is_list($metadataStates)
            || count($metadataStates) !== count(array_unique($metadataStates))
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_METADATA_WIDENS_MANIFEST',
                "Smart catalogue metadata for [$component] does not narrow exact manifest semantics.",
            );
        }
        $admittedStates = $this->consumerPolicy->admittedStates($component, $manifestStates);
        if ($admittedStates !== $metadataStates) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_METADATA_WIDENS_MANIFEST',
                "Smart catalogue states for [$component] do not match the enforced consumer policy.",
            );
        }
    }

    private function assertSmartAdmissionReady(): void
    {
        (new FrameworkAdmissionPreflight(
            $this->manifests,
            $this->consumerPolicy,
            new FrameworkPropsValidator,
            new FrameworkHostRenderer,
            new FrameworkAssetPlanner($this->manifests, '/_docara/framework'),
        ))->assertReady();
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array<string, mixed>>
     */
    private function manifestParameters(string $component, array $manifest): array
    {
        $managed = array_fill_keys($this->consumerPolicy->managedProperties($component), true);
        $mirrors = (new FrameworkManifestContract)->mirrorMap($component, $manifest);
        $defaults = is_array($manifest['atlas']['example_props'] ?? null)
            ? $manifest['atlas']['example_props']
            : [];
        $parameters = [];
        foreach ($manifest['props']['properties'] as $name => $schema) {
            if (! is_string($name)
                || preg_match('/^[a-z][a-z0-9_-]*$/D', $name) !== 1
                || ! is_array($schema)
                || array_diff(
                    array_keys($schema),
                    ['type', 'enum', 'minLength', 'maxLength', 'pattern', 'minimum', 'maximum'],
                ) !== []
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_PROP_INVALID',
                    "Smart manifest property [$component] cannot be projected safely.",
                );
            }
            if (isset($managed[$name])) {
                continue;
            }
            $type = $schema['type'] ?? null;
            if (! in_array($type, ['string', 'boolean', 'integer', 'number'], true)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_PROP_TYPE_UNSUPPORTED',
                    "Smart manifest property [$component:$name] uses an unsupported authoring type.",
                );
            }
            $parameter = [
                'name' => $name,
                'type' => isset($schema['enum']) ? 'enum' : $type,
                // The runtime starts with exact manifest example_props, then
                // applies a preset and explicit author input. "required"
                // therefore describes author input, not the final host props.
                'required' => false,
            ];
            if (array_key_exists('enum', $schema)) {
                if (! is_array($schema['enum'])
                    || ! array_is_list($schema['enum'])
                    || $schema['enum'] === []
                ) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SMART_PROP_ENUM_INVALID',
                        "Smart manifest enum [$component:$name] cannot be projected safely.",
                    );
                }
                $blockedValues = $this->consumerPolicy->blockedValues($component, $name);
                $schema['enum'] = array_values(array_filter(
                    $schema['enum'],
                    static fn (mixed $value): bool => ! in_array($value, $blockedValues, true),
                ));
                if ($schema['enum'] === []) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SMART_PROP_ENUM_INVALID',
                        "Smart manifest enum [$component:$name] is empty after consumer narrowing.",
                    );
                }
                $encodedValues = [];
                foreach ($schema['enum'] as $value) {
                    $this->assertCatalogConstraintScalar($component, $value);
                    if (! $this->matchesManifestType($value, $type)) {
                        throw new PortableConfigurationException(
                            'COMPONENT_CATALOG_SMART_PROP_ENUM_INVALID',
                            "Smart manifest enum [$component:$name] contains a value of the wrong type.",
                        );
                    }
                    $encodedValues[] = CanonicalJson::encode($value);
                }
                if (count($encodedValues) !== count(array_unique($encodedValues))) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SMART_PROP_ENUM_INVALID',
                        "Smart manifest enum [$component:$name] contains duplicate values.",
                    );
                }
                $parameter['values'] = $schema['enum'];
            }
            $validation = $this->manifestParameterValidation($component, $name, $type, $schema);
            if ($validation !== []) {
                $parameter['validation'] = $validation;
            }
            if (isset($mirrors[$name])) {
                $parameter['mirrors'] = $mirrors[$name];
            }
            if (array_key_exists($name, $defaults)
                && (is_string($defaults[$name])
                    || is_bool($defaults[$name])
                    || is_int($defaults[$name])
                    || is_float($defaults[$name]))
            ) {
                $parameter['default'] = $defaults[$name];
            }
            $parameters[] = $parameter;
        }
        $presets = array_keys($manifest['presets']);
        sort($presets, SORT_STRING);
        if ($presets !== []) {
            $parameters[] = [
                'name' => 'preset',
                'type' => 'enum',
                'required' => false,
                'values' => $presets,
            ];
        }
        usort(
            $parameters,
            static fn (array $left, array $right): int => (string) $left['name'] <=> (string) $right['name'],
        );

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, string|int|float>
     */
    private function manifestParameterValidation(
        string $component,
        string $name,
        string $type,
        array $schema,
    ): array {
        $validation = [];
        foreach (['minLength' => 'min_length', 'maxLength' => 'max_length'] as $source => $target) {
            if (! array_key_exists($source, $schema)) {
                continue;
            }
            if ($type !== 'string' || ! is_int($schema[$source]) || $schema[$source] < 0) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_PROP_RULE_INVALID',
                    "Smart manifest rule [$component:$name:$source] cannot be projected safely.",
                );
            }
            $validation[$target] = $schema[$source];
        }
        if (isset($validation['min_length'], $validation['max_length'])
            && $validation['min_length'] > $validation['max_length']
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_PROP_RULE_INVALID',
                "Smart manifest length rules [$component:$name] are inconsistent.",
            );
        }
        if (array_key_exists('pattern', $schema)) {
            if ($type !== 'string' || ! is_string($schema['pattern']) || $schema['pattern'] === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_PROP_RULE_INVALID',
                    "Smart manifest pattern [$component:$name] cannot be projected safely.",
                );
            }
            $validation['pattern'] = $schema['pattern'];
        }
        foreach (['minimum', 'maximum'] as $rule) {
            if (! array_key_exists($rule, $schema)) {
                continue;
            }
            if (! in_array($type, ['integer', 'number'], true)
                || (! is_int($schema[$rule]) && ! is_float($schema[$rule]))
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_PROP_RULE_INVALID',
                    "Smart manifest numeric rule [$component:$name:$rule] cannot be projected safely.",
                );
            }
            $validation[$rule] = $schema[$rule];
        }
        if (isset($validation['minimum'], $validation['maximum'])
            && $validation['minimum'] > $validation['maximum']
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_PROP_RULE_INVALID',
                "Smart manifest numeric rules [$component:$name] are inconsistent.",
            );
        }

        return $validation;
    }

    private function matchesManifestType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{
     *     allowed_combinations: list<array{keys: list<string>, values: list<list<string|bool|int|float>>}>,
     *     requires: list<array{when: array<string, string|bool|int|float>, then: array<string, string|bool|int|float>}>
     * }
     */
    private function manifestConstraints(string $component, array $manifest): array
    {
        $source = $manifest['constraints'] ?? null;
        if (! is_array($source)
            || ($source !== [] && array_is_list($source))
            || array_diff(array_keys($source), ['allowed_combinations', 'requires']) !== []
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                "Smart manifest constraints for [$component] cannot be projected safely.",
            );
        }
        $properties = $manifest['props']['properties'] ?? null;
        $allowed = $source['allowed_combinations'] ?? [];
        $requires = $source['requires'] ?? [];
        if (! is_array($properties)
            || ! is_array($allowed)
            || ! array_is_list($allowed)
            || ! is_array($requires)
            || ! array_is_list($requires)
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                "Smart manifest constraints for [$component] cannot be projected safely.",
            );
        }

        foreach ($allowed as $combination) {
            if (! is_array($combination)
                || array_is_list($combination)
                || ! $this->hasExactKeys($combination, ['keys', 'values'])
                || ! is_array($combination['keys'])
                || ! array_is_list($combination['keys'])
                || $combination['keys'] === []
                || count($combination['keys']) !== count(array_unique($combination['keys']))
                || ! is_array($combination['values'])
                || ! array_is_list($combination['values'])
                || $combination['values'] === []
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                    "Smart allowed combinations for [$component] cannot be projected safely.",
                );
            }
            foreach ($combination['keys'] as $key) {
                if (! is_string($key)
                    || preg_match('/^[a-z][a-z0-9_-]*$/D', $key) !== 1
                    || ! array_key_exists($key, $properties)
                ) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                        "Smart allowed combinations for [$component] reference an invalid property.",
                    );
                }
            }
            foreach ($combination['values'] as $values) {
                if (! is_array($values)
                    || ! array_is_list($values)
                    || count($values) !== count($combination['keys'])
                ) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                        "Smart allowed combinations for [$component] contain an invalid tuple.",
                    );
                }
                foreach ($values as $value) {
                    $this->assertCatalogConstraintScalar($component, $value);
                }
            }
        }

        foreach ($requires as $requirement) {
            if (! is_array($requirement)
                || array_is_list($requirement)
                || ! $this->hasExactKeys($requirement, ['when', 'then'])
                || ! is_array($requirement['when'])
                || array_is_list($requirement['when'])
                || $requirement['when'] === []
                || ! is_array($requirement['then'])
                || array_is_list($requirement['then'])
                || $requirement['then'] === []
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                    "Smart requirements for [$component] cannot be projected safely.",
                );
            }
            foreach ([$requirement['when'], $requirement['then']] as $condition) {
                foreach ($condition as $key => $value) {
                    if (! is_string($key)
                        || preg_match('/^[a-z][a-z0-9_-]*$/D', $key) !== 1
                        || ! array_key_exists($key, $properties)
                    ) {
                        throw new PortableConfigurationException(
                            'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                            "Smart requirements for [$component] reference an invalid property.",
                        );
                    }
                    $this->assertCatalogConstraintScalar($component, $value);
                }
            }
        }

        return [
            'allowed_combinations' => $allowed,
            'requires' => $requires,
        ];
    }

    private function assertCatalogConstraintScalar(string $component, mixed $value): void
    {
        if (! is_string($value) && ! is_bool($value) && ! is_int($value) && ! is_float($value)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SMART_CONSTRAINTS_INVALID',
                "Smart constraints for [$component] contain an unsupported value.",
            );
        }
    }

    /** @param list<string> $expected */
    private function hasExactKeys(array $value, array $expected): bool
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);

        return $keys === $expected;
    }

    /** @return list<array<string, mixed>> */
    private function loadDirectory(string $directory): array
    {
        $root = $this->packageRoot . '/resources/component-catalog/' . $directory;
        if (is_link($root) || ! is_dir($root)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SOURCE_INVALID',
                "Component catalogue source [$directory] is missing or unsafe.",
            );
        }
        $paths = glob($root . '/*.json', GLOB_NOSORT);
        if (! is_array($paths) || $paths === []) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SOURCE_INVALID',
                "Component catalogue source [$directory] is empty.",
            );
        }
        sort($paths, SORT_STRING);

        $entries = [];
        foreach ($paths as $path) {
            if (is_link($path) || ! is_file($path)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SOURCE_INVALID',
                    'A component catalogue source record is missing or unsafe.',
                );
            }
            try {
                $entry = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SOURCE_INVALID',
                    'A component catalogue source record is not valid JSON.',
                    $exception,
                );
            }
            if (! is_array($entry)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_SOURCE_INVALID',
                    'A component catalogue source record must be an object.',
                );
            }
            $this->schemas->assertValid($entry, 'component-catalog-entry.schema.json');
            $entries[] = $entry;
        }

        return $entries;
    }

    /** @param array<string, mixed> $catalog */
    private function assertReferencesExist(array $catalog): void
    {
        foreach ($catalog['entries'] as $entry) {
            foreach (['docs_ref'] as $key) {
                $path = $entry[$key] ?? null;
                if (! is_string($path)) {
                    continue;
                }
                $absolute = $this->packageRoot . '/' . $path;
                if (is_link($absolute) || ! is_file($absolute)) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_REFERENCE_MISSING',
                        "Catalogue reference [$path] is missing or unsafe.",
                    );
                }
            }
        }
    }
}
