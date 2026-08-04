<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;

final readonly class DiscoveryService
{
    public function __construct(private string $schemaRoot = __DIR__ . '/../../resources/schemas') {}

    public function doctor(string $root): OperationResult
    {
        $runtime = ProjectRuntime::load($root);

        return OperationResult::success('doctor', $runtime->namespace ?? 'package-only', [
            'checks' => [
                ['code' => 'PROJECT_CONFIG_VALID', 'status' => 'pass'],
                ['code' => 'SMART_REGISTRY_VALID', 'status' => 'pass', 'count' => count($runtime->smarts->keys())],
                ['code' => 'DESIGN_REGISTRY_VALID', 'status' => 'pass', 'count' => count($runtime->designs->all())],
                ['code' => 'SCHEMA_CATALOG_VALID', 'status' => 'pass', 'count' => count($this->schemaNames())],
            ],
            'design_fingerprint' => $runtime->designs->fingerprint(),
        ], $runtime->provenance());
    }

    public function list(string $root, string $kind): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $items = match ($kind) {
            'smart' => array_map(fn (string $id): array => $this->smartSummary($runtime, $id), $runtime->smarts->keys()),
            'layout', 'view', 'section', 'block' => array_map(
                static fn ($descriptor): array => [
                    'id' => $descriptor->id,
                    'kind' => $descriptor->kind->value,
                    'owner' => $descriptor->ownerNamespace,
                    'provider' => $descriptor->provider,
                    'source' => $descriptor->relativePath,
                    'sha256' => $descriptor->sha256,
                ],
                $runtime->designs->all(DesignArtifactKind::from($kind)),
            ),
            'provider' => $this->providers($runtime),
            'schema' => array_map(static fn (string $name): array => ['id' => $name, 'kind' => 'schema'], $this->schemaNames()),
            'fixture', 'state' => $this->fixtures($runtime, $kind),
            default => throw new \InvalidArgumentException('SDK_DISCOVERY_KIND_UNKNOWN:' . $kind),
        };
        usort($items, static fn (array $left, array $right): int => [$left['id'], $left['kind']] <=> [$right['id'], $right['kind']]);

        return OperationResult::success('list', $kind, ['items' => $items, 'count' => count($items)], $runtime->provenance());
    }

    public function inspect(string $root, string $kind, string $id): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $data = match ($kind) {
            'smart' => $this->smartDetail($runtime, $id),
            'layout', 'view', 'section', 'block' => $this->designDetail($runtime, DesignArtifactKind::from($kind), $id),
            'provider' => $this->oneById($this->providers($runtime), $id),
            'schema' => ['id' => $id, 'schema' => (new SchemaRepository($this->schemaRoot))->get($id)],
            'fixture', 'state' => $this->oneById($this->fixtures($runtime, $kind), $id),
            default => throw new \InvalidArgumentException('SDK_DISCOVERY_KIND_UNKNOWN:' . $kind),
        };

        return OperationResult::success('inspect', $kind . ':' . $id, $data, $runtime->provenance());
    }

    public function schema(string $root, string $kind): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $schema = match ($kind) {
            'smart' => 'portable-smart-manifest.schema.json',
            'layout' => DesignArtifactKind::Layout->schema(),
            'view' => DesignArtifactKind::View->schema(),
            'section' => DesignArtifactKind::Section->schema(),
            'block' => DesignArtifactKind::Block->schema(),
            default => str_ends_with($kind, '.schema.json') ? $kind : throw new \InvalidArgumentException('SDK_SCHEMA_KIND_UNKNOWN:' . $kind),
        };

        $data = [
            'schema_id' => $schema,
            'schema' => (new SchemaRepository($this->schemaRoot))->get($schema),
        ];
        if ($kind === 'smart') {
            $data['contract'] = [
                'contract_id' => Sf5SmartArtifactV1Contract::CONTRACT_ID,
                'schema_version' => Sf5SmartArtifactV1Contract::SCHEMA_VERSION,
                'compatibility_id' => Sf5SmartArtifactV1Contract::COMPATIBILITY_ID,
                'storage_compatibility_alias' => Sf5SmartArtifactV1Contract::STORAGE_COMPATIBILITY_ALIAS,
                'source_revision' => Sf5SmartArtifactV1Contract::SOURCE_REVISION,
            ];
        }

        return OperationResult::success('schema', $kind . ':' . $schema, $data, $runtime->provenance());
    }

    private function smartSummary(ProjectRuntime $runtime, string $id): array
    {
        $definition = $runtime->smarts->definition($id);

        return [
            'id' => $id,
            'kind' => 'smart',
            'owner' => $definition->ownerPackage,
            'provider' => $definition->providerId,
            'strategy' => $definition->strategy,
        ];
    }

    private function smartDetail(ProjectRuntime $runtime, string $id): array
    {
        $definition = $runtime->smarts->definition($id);

        return $this->smartSummary($runtime, $id) + [
            'aliases' => $definition->aliases,
            'manifest' => $definition->portableManifest,
            'views' => array_keys($definition->views),
            'presets' => array_keys($definition->presets),
            'templates' => array_keys($definition->templates),
            'assets' => array_keys($definition->assets),
            'provenance' => $definition->provenance,
        ];
    }

    private function designDetail(ProjectRuntime $runtime, DesignArtifactKind $kind, string $id): array
    {
        $descriptor = $runtime->designs->get($kind, $id);

        return [
            'id' => $id,
            'kind' => $kind->value,
            'definition' => $descriptor->definition,
            'provenance' => $descriptor->provenance(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function providers(ProjectRuntime $runtime): array
    {
        $providers = [];
        foreach ($runtime->smarts->keys() as $id) {
            $definition = $runtime->smarts->definition($id);
            $providers[$definition->providerId] = [
                'id' => $definition->providerId,
                'kind' => 'provider',
                'surface' => 'smart',
                'owner' => $definition->ownerPackage,
                'revision' => $definition->provenance['provider_revision'] ?? null,
            ];
        }
        foreach ($runtime->designs->all() as $descriptor) {
            $providers[$descriptor->provider] = [
                'id' => $descriptor->provider,
                'kind' => 'provider',
                'surface' => 'design',
                'owner' => $descriptor->ownerNamespace,
                'revision' => $descriptor->providerRevision,
            ];
        }
        ksort($providers, SORT_STRING);

        return array_values($providers);
    }

    /** @return list<array<string, mixed>> */
    private function fixtures(ProjectRuntime $runtime, string $kind): array
    {
        $items = [];
        foreach ($runtime->smarts->keys() as $id) {
            $manifest = $runtime->smarts->definition($id)->portableManifest;
            $field = $kind === 'fixture' ? 'fixtures' : 'states';
            $definitions = $manifest[$field] ?? $manifest['ai'][$field] ?? [];
            foreach ($definitions as $key => $value) {
                $fixtureId = is_string($key) ? $id . ':' . $key : $id . ':' . (string) $value;
                $items[] = ['id' => $fixtureId, 'kind' => $kind, 'smart' => $id, 'definition' => $value];
            }
        }

        return $items;
    }

    /** @param list<array<string, mixed>> $items @return array<string, mixed> */
    private function oneById(array $items, string $id): array
    {
        foreach ($items as $item) {
            if (($item['id'] ?? null) === $id) {
                return $item;
            }
        }
        throw new \InvalidArgumentException('SDK_DISCOVERY_SUBJECT_UNKNOWN:' . $id);
    }

    /** @return list<string> */
    private function schemaNames(): array
    {
        $paths = glob(rtrim($this->schemaRoot, '/\\') . '/*.schema.json') ?: [];
        $names = array_map('basename', $paths);
        sort($names, SORT_STRING);

        return $names;
    }
}
