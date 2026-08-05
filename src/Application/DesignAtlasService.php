<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\Declarative\Binding\BindingDescriptor;
use Simai\Docara\Design\Artifact\DesignArtifactDescriptor;
use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Smart\SmartComponentDefinition;

final readonly class DesignAtlasService
{
    public function atlas(string $root): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $entries = [];

        foreach ($runtime->designs->all() as $descriptor) {
            $entries[] = $this->design($descriptor);
        }
        foreach ($runtime->smarts->keys() as $id) {
            $definition = $runtime->smarts->definition($id);
            $entries[] = $this->smart($definition);
            foreach ($definition->presets as $preset => $record) {
                $entries[] = $this->preset($definition, $preset, $record);
            }
        }
        foreach ($runtime->bindings->all() as $descriptor) {
            $entries[] = $this->binding($descriptor);
            foreach ($descriptor->presentations as $presentation) {
                $entries[] = $this->bindingPreset($descriptor, $presentation);
            }
        }
        $frameworkLock = $runtime->site['framework_lock'] ?? null;
        if (is_string($frameworkLock)
            && $frameworkLock !== ''
            && is_file($runtime->root . '/' . ltrim($frameworkLock, '/'))
        ) {
            $catalog = EffectiveComponentCatalogBuilder::bundled(
                FrameworkLock::fromJsonFile($runtime->root . '/' . ltrim($frameworkLock, '/')),
            )->build();
            foreach ($catalog['entries'] as $entry) {
                if (is_array($entry) && ($entry['family'] ?? null) !== 'framework_smart') {
                    $entries[] = $this->component($entry);
                }
            }
        }

        usort($entries, static fn (array $left, array $right): int => [$left['kind'], $left['id']] <=> [$right['kind'], $right['id']]);
        $core = [
            'schema' => 'docara.design_atlas.v1',
            'vocabulary' => [
                'kinds' => ['binding', 'block', 'layout', 'preset', 'section', 'smart', 'view'],
                'authoring_kinds' => ['configuration', 'container', 'inline', 'block', 'markdown', 'none'],
                'support_states' => ['supported', 'compatibility', 'project', 'proposal', 'rejected'],
                'origins' => ['docara', 'framework', 'native', 'project', 'requirement'],
                'typing_source' => 'admitted_registry_descriptor',
                'fence_length_semantics' => 'none',
            ],
            'registry_fingerprints' => [
                'bindings' => $runtime->bindings->fingerprint(),
                'components' => isset($catalog) ? (string) $catalog['content_sha256'] : hash('sha256', ''),
                'design' => $runtime->designs->fingerprint(),
                'smart' => $this->smartFingerprint($runtime),
            ],
            'entries' => $entries,
        ];

        return OperationResult::success('atlas', 'effective', $core + [
            'count' => count($entries),
            'fingerprint' => hash('sha256', CanonicalJson::encode($core)),
        ], $runtime->provenance());
    }

    /** @return array<string, mixed> */
    private function design(DesignArtifactDescriptor $descriptor): array
    {
        $contract = match ($descriptor->kind) {
            DesignArtifactKind::Layout => $this->layoutContract($descriptor),
            DesignArtifactKind::Section => $this->sectionContract($descriptor),
            default => null,
        };

        return [
            'id' => $descriptor->id,
            'kind' => $descriptor->kind->value,
            ...$this->facets($descriptor->id, $descriptor->ownerNamespace, $this->ownerPackage($descriptor->provider, $descriptor->ownerNamespace), $descriptor->provider, $this->support($descriptor->provider)),
            'authoring_kind' => $contract === null ? 'configuration' : 'container',
            'capabilities' => $this->strings($descriptor->definition['capabilities'] ?? []),
            'preview_supported' => true,
            'schema' => $descriptor->kind->schema(),
            'container_contract' => $contract,
            'provenance' => $descriptor->provenance(),
        ];
    }

    /** @return array<string, mixed> */
    private function smart(SmartComponentDefinition $definition): array
    {
        $manifest = $definition->portableManifest;
        $children = is_array($manifest['children'] ?? null) ? $manifest['children'] : [];
        $slotContracts = is_array($manifest['slots'] ?? null) ? $manifest['slots'] : [];
        $slots = array_keys($slotContracts);
        sort($slots, SORT_STRING);
        $allowedChildren = array_values(array_unique(array_filter([
            ...array_map(
                static fn (array $child): string => (string) ($child['smart'] ?? ''),
                array_values(array_filter($children, 'is_array')),
            ),
            ...array_merge(...array_map(
                static fn (mixed $slot): array => is_array($slot) && is_array($slot['accepts'] ?? null)
                    ? array_values(array_filter($slot['accepts'], 'is_string'))
                    : [],
                array_values($slotContracts),
            )),
        ], static fn (string $id): bool => $id !== '')));
        sort($allowedChildren, SORT_STRING);
        $requiredSlots = array_filter(
            $slotContracts,
            static fn (mixed $slot): bool => is_array($slot) && ($slot['required'] ?? false) === true,
        );
        $contract = $children === [] && $slots === [] ? null : [
            'allowed_children' => $allowedChildren,
            'slots' => $slots,
            'min_children' => count($requiredSlots),
            'max_children' => array_filter(
                $slotContracts,
                static fn (mixed $slot): bool => is_array($slot) && ($slot['multiple'] ?? false) === true,
            ) === [] ? max(count($children), count($slots), 1) : 64,
            'order' => 'declared',
            'max_depth' => 8,
        ];

        return [
            'id' => $definition->key,
            'kind' => 'smart',
            ...$this->facets($definition->key, $this->namespace($definition->key), $definition->ownerPackage, $definition->providerId, $this->support($definition->providerId, $definition->adapterId !== null)),
            'authoring_kind' => $contract === null ? 'block' : 'container',
            'capabilities' => $this->smartCapabilities($manifest),
            'preview_supported' => true,
            'schema' => 'smart.manifest.schema.json',
            'container_contract' => $contract,
            'provenance' => $definition->provenance,
        ];
    }

    /** @param array<string, mixed> $record @return array<string, mixed> */
    private function preset(SmartComponentDefinition $definition, string $preset, array $record): array
    {
        return [
            'id' => $definition->key . ':' . $preset,
            'kind' => 'preset',
            ...$this->facets($definition->key . ':' . $preset, $this->namespace($definition->key), $definition->ownerPackage, $definition->providerId, $this->support($definition->providerId)),
            'authoring_kind' => 'configuration',
            'capabilities' => ['smart.preset'],
            'preview_supported' => true,
            'schema' => (string) ($record['schema'] ?? 'smart.preset.schema.json'),
            'container_contract' => null,
            'provenance' => $definition->provenance + ['source' => (string) ($record['path'] ?? $definition->manifest['path'])],
        ];
    }

    /** @return array<string, mixed> */
    private function binding(BindingDescriptor $descriptor): array
    {
        return [
            'id' => $descriptor->id,
            'kind' => 'binding',
            ...$this->facets($descriptor->id, $descriptor->ownerNamespace, $this->ownerPackage($descriptor->provider, $descriptor->ownerNamespace), $descriptor->provider, $this->support($descriptor->provider)),
            'authoring_kind' => 'configuration',
            'capabilities' => $descriptor->capabilities,
            'preview_supported' => true,
            'schema' => $descriptor->outputSchema,
            'container_contract' => null,
            'provenance' => $descriptor->provenance(),
        ];
    }

    /** @return array<string, mixed> */
    private function bindingPreset(BindingDescriptor $descriptor, string $presentation): array
    {
        return [
            'id' => $descriptor->id . ':' . $presentation,
            'kind' => 'preset',
            ...$this->facets($descriptor->id . ':' . $presentation, $descriptor->ownerNamespace, $this->ownerPackage($descriptor->provider, $descriptor->ownerNamespace), $descriptor->provider, $this->support($descriptor->provider)),
            'authoring_kind' => 'configuration',
            'capabilities' => $descriptor->capabilities,
            'preview_supported' => true,
            'schema' => $descriptor->outputSchema,
            'container_contract' => null,
            'provenance' => $descriptor->provenance() + ['presentation' => $presentation],
        ];
    }

    /** @return array<string, mixed> */
    private function layoutContract(DesignArtifactDescriptor $descriptor): array
    {
        $regions = is_array($descriptor->definition['regions'] ?? null) ? $descriptor->definition['regions'] : [];
        $max = (int) ($descriptor->definition['configuration']['container']['max'] ?? count($regions));
        $required = array_filter($regions, static fn (mixed $region): bool => is_array($region) && ($region['required'] ?? false) === true);

        return [
            'allowed_children' => ['section'],
            'slots' => array_keys($regions),
            'min_children' => count($required),
            'max_children' => $max,
            'order' => 'layout_regions',
            'max_depth' => 3,
        ];
    }

    /** @return array<string, mixed> */
    private function sectionContract(DesignArtifactDescriptor $descriptor): array
    {
        return [
            'allowed_children' => $this->strings($descriptor->definition['allowed_blocks'] ?? []),
            'slots' => $this->strings($descriptor->definition['slots'] ?? []),
            'min_children' => 0,
            'max_children' => 64,
            'order' => 'declared',
            'max_depth' => 2,
        ];
    }

    /** @param array<string, mixed> $manifest @return list<string> */
    private function smartCapabilities(array $manifest): array
    {
        $capabilities = ['smart.render'];
        if (($manifest['render']['hydration'] ?? 'none') !== 'none') {
            $capabilities[] = 'smart.hydration';
        }
        if (($manifest['slots'] ?? []) !== [] || ($manifest['children'] ?? []) !== []) {
            $capabilities[] = 'smart.children';
        }
        sort($capabilities, SORT_STRING);

        return $capabilities;
    }

    /** @param mixed $values @return list<string> */
    private function strings(mixed $values): array
    {
        $strings = is_array($values) ? array_values(array_filter($values, 'is_string')) : [];
        sort($strings, SORT_STRING);

        return $strings;
    }

    private function smartFingerprint(ProjectRuntime $runtime): string
    {
        $records = [];
        foreach ($runtime->smarts->keys() as $id) {
            $definition = $runtime->smarts->definition($id);
            $records[] = [$id, $definition->providerId, $definition->provenance['manifest_sha256'] ?? null];
        }

        return hash('sha256', CanonicalJson::encode($records));
    }

    /** @param array<string,mixed> $entry @return array<string,mixed> */
    private function component(array $entry): array
    {
        $id = (string) ($entry['id'] ?? '');
        $authoring = is_array($entry['authoring'] ?? null) ? $entry['authoring'] : [];
        $metadata = is_array($entry['metadata'] ?? null) ? $entry['metadata'] : [];
        $provenance = is_array($entry['provenance'] ?? null) ? $entry['provenance'] : [];
        $family = (string) ($entry['family'] ?? '');
        $lifecycle = (string) ($entry['lifecycle'] ?? 'deferred');
        $syntax = (string) ($authoring['syntax'] ?? 'unavailable');
        $container = is_array($entry['container_contract'] ?? null) ? $entry['container_contract'] : null;
        $authoringKind = match (true) {
            $family === 'native_markdown' => 'markdown',
            $syntax === 'inline' => 'inline',
            $container !== null => 'container',
            default => 'block',
        };
        $support = match ($lifecycle) {
            'supported' => 'supported',
            'framework_gap' => 'rejected',
            default => 'proposal',
        };
        $sourceKind = (string) ($provenance['source_kind'] ?? 'catalog');
        $origin = match (true) {
            $family === 'native_markdown' => 'native',
            $family === 'requirement' => 'requirement',
            default => 'docara',
        };

        return [
            'id' => $id,
            'kind' => 'block',
            ...$this->facets(
                $id,
                $this->namespace($id),
                (string) ($metadata['owner'] ?? $metadata['package'] ?? 'simai/docara'),
                'component.catalog.' . $sourceKind,
                $support,
                $origin,
                $lifecycle,
            ),
            'authoring_kind' => $authoringKind,
            'capabilities' => $this->strings($metadata['capabilities'] ?? $authoring['jobs'] ?? []),
            'preview_supported' => $lifecycle === 'supported',
            'schema' => 'component-catalog-entry.schema.json',
            'container_contract' => $container,
            'provenance' => $provenance + ['docs_ref' => (string) ($entry['docs_ref'] ?? '')],
        ];
    }

    /** @return array<string,string> */
    private function facets(string $id, string $namespace, string $ownerPackage, string $provider, string $support, ?string $origin = null, ?string $status = null): array
    {
        $origin ??= match (true) {
            str_starts_with($provider, 'project.') => 'project',
            str_starts_with($provider, 'framework.') => 'framework',
            default => 'docara',
        };

        return [
            'namespace' => $namespace !== '' ? $namespace : $this->namespace($id),
            'owner' => $ownerPackage,
            'owner_package' => $ownerPackage,
            'origin' => $origin,
            'provider' => $provider,
            'support' => $support,
            'status' => $status ?? $support,
        ];
    }

    private function namespace(string $id): string
    {
        $separator = strpos($id, '.');

        return $separator === false ? $id : substr($id, 0, $separator);
    }

    private function support(string $provider, bool $compatibility = false): string
    {
        if (str_starts_with($provider, 'project.')) {
            return 'project';
        }

        return $compatibility ? 'compatibility' : 'supported';
    }

    private function ownerPackage(string $provider, string $namespace): string
    {
        return str_starts_with($provider, 'project.') ? 'project/' . $namespace : 'simai/docara';
    }
}
