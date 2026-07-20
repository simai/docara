<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class CompositeSmartPlanResolver
{
    public function __construct(
        private DefinitionRepository $definitions = new DefinitionRepository,
    ) {}

    /** @param array<string, mixed> $props */
    public function resolve(string $smart, string $nodeId, array $props): ResolvedSmartPlan
    {
        $manifest = $this->definitions->smartManifest($smart);
        $view = $this->definitions->smartView($smart, 'default');
        $this->assertCanonicalManifest($smart, $manifest);
        $this->assertProps($smart, $props);

        $assets = [];
        foreach ($manifest['assets'] as $asset) {
            if (is_array($asset) && is_string($asset['key'] ?? null)) {
                $assets[] = $asset['key'];
            }
        }
        $assets = array_values(array_unique($assets));
        sort($assets, SORT_STRING);

        return new ResolvedSmartPlan(
            $nodeId,
            $smart,
            'default',
            (string) $view['template'],
            $props,
            $assets,
            [
                'manifest' => (string) $manifest['_source'],
                'manifest_sha256' => (string) $manifest['_sha256'],
                'manifest_schema' => (string) $manifest['schema'],
                'manifest_version' => (string) $manifest['version'],
                'provider' => (string) $manifest['owner_package'],
                'renderer' => (string) $manifest['render']['renderer'],
                'view' => (string) $view['_source'],
                'view_sha256' => (string) $view['_sha256'],
            ],
        );
    }

    /** @param array<string, mixed> $manifest */
    private function assertCanonicalManifest(string $smart, array $manifest): void
    {
        if (($manifest['schema'] ?? null) !== 'larena.ui.smart_manifest.v1'
            || ($manifest['key'] ?? null) !== $smart
            || preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $smart) !== 1
            || preg_match('/^\d+\.\d+\.\d+$/D', (string) ($manifest['version'] ?? '')) !== 1
            || ($manifest['owner_package'] ?? null) !== 'simai/docara'
            || ($manifest['kind'] ?? null) !== 'composite'
            || ($manifest['render']['strategy'] ?? null) !== 'host'
            || ($manifest['render']['renderer'] ?? null) !== 'docara.smart.template'
            || ! is_array($manifest['props'] ?? null)
            || ! is_array($manifest['assets'] ?? null)
            || ! array_is_list($manifest['assets'])
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_COMPOSITE_MANIFEST_INVALID',
                "Composite Smart manifest [$smart] is invalid.",
            );
        }
    }

    /** @param array<string, mixed> $props */
    private function assertProps(string $smart, array $props): void
    {
        $valid = match ($smart) {
            'docara.header' => isset($props['branding']) && is_array($props['branding']),
            'docara.navigation' => isset($props['items'])
                && is_array($props['items'])
                && array_is_list($props['items'])
                && ($props['maximum_depth'] ?? null) === 4,
            'docara.outline' => isset($props['items'])
                && is_array($props['items'])
                && array_is_list($props['items']),
            default => false,
        };
        if (! $valid) {
            throw new PortableConfigurationException(
                'DECLARATIVE_COMPOSITE_PROPS_INVALID',
                "Composite Smart props [$smart] are invalid.",
            );
        }
    }
}
