<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Definition\DefinitionRepository;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\Artifact\LegacySmartManifestV1Adapter;
use Simai\Docara\Smart\SmartPropsValidationException;
use Simai\Docara\Smart\SmartPropsValidator;
use Simai\Docara\Smart\SmartRegistry;

final readonly class CompositeSmartPlanResolver
{
    private SmartRegistry $smarts;

    public function __construct(
        private DefinitionRepository $definitions = new DefinitionRepository,
        ?SmartRegistry $smarts = null,
        private SmartPropsValidator $propsValidator = new SmartPropsValidator,
        private LegacySmartManifestV1Adapter $portable = new LegacySmartManifestV1Adapter,
    ) {
        $this->smarts = $smarts ?? SmartRegistry::bundled();
    }

    /** @param array<string, mixed> $props */
    public function resolve(
        string $smart,
        string $nodeId,
        array $props,
        string $requestedView = 'default',
    ): ResolvedSmartPlan {
        $resolution = $this->smarts->resolution($smart);
        $canonical = $resolution['canonical'];
        $manifest = $this->definitions->smartManifest($smart);
        $view = $this->definitions->smartView($smart, $requestedView);
        $this->assertProductManifest($canonical, $manifest);
        try {
            $this->propsValidator->assertValid($canonical, $manifest, $props);
        } catch (SmartPropsValidationException $exception) {
            throw new PortableConfigurationException(
                'DECLARATIVE_COMPOSITE_PROPS_INVALID',
                $exception->getMessage(),
                $exception,
            );
        }
        $portableManifest = $this->portable->adapt($manifest, $canonical);

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
            $canonical,
            $requestedView,
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
                'requested_smart' => $smart,
                'canonical_smart' => $canonical,
                'deprecated_alias' => $resolution['deprecated'],
                'alias_reason' => $resolution['reason'],
                'view' => (string) $view['_source'],
                'view_sha256' => (string) $view['_sha256'],
                'runtime' => 'docara.smart.template',
                'portable_strategy' => (string) $portableManifest['render']['strategy'],
                'input_adapter' => (string) ($manifest['provenance']['input_adapter'] ?? 'smart.props'),
                'portable_manifest' => $portableManifest,
            ],
        );
    }

    /** @param array<string, mixed> $manifest */
    private function assertProductManifest(string $smart, array $manifest): void
    {
        if (($manifest['key'] ?? null) !== $smart
            || ($manifest['owner_package'] ?? null) !== 'simai/docara'
            || ($manifest['kind'] ?? null) !== 'composite'
            || ($manifest['render']['strategy'] ?? null) !== 'host'
            || ($manifest['render']['renderer'] ?? null) !== 'docara.smart.template'
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_COMPOSITE_MANIFEST_INVALID',
                "Composite Smart manifest [$smart] is invalid.",
            );
        }
    }
}
