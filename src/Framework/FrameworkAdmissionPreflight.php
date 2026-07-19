<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final readonly class FrameworkAdmissionPreflight
{
    public function __construct(
        private FrameworkManifestRepository $manifests,
        private FrameworkConsumerPolicy $consumerPolicy,
        private FrameworkPropsValidator $propsValidator,
        private FrameworkHostRenderer $renderer,
        private FrameworkAssetPlanner $assetPlanner,
    ) {}

    public function assertReady(): void
    {
        $keys = $this->manifests->keys();
        // Validate the complete projected surface, then prove each component's
        // own dependency closure independently. A sibling component must never
        // satisfy another manifest's critical asset requirement.
        $this->assetPlanner->assertExactProjection($keys);

        foreach ($keys as $key) {
            $manifest = $this->manifests->get($key);
            $this->consumerPolicy->assertNarrowing($key, $manifest);
            $componentPlan = $this->assetPlanner->plan([$key]);
            $plannedAssets = array_fill_keys(array_column($componentPlan->assets, 'key'), true);
            $omittedAssets = array_fill_keys($this->consumerPolicy->omittedAssets($key), true);
            foreach ($manifest['assets'] as $asset) {
                if (($asset['critical'] ?? null) !== true) {
                    continue;
                }
                $assetKey = (string) ($asset['key'] ?? '');
                if (! isset($plannedAssets[$assetKey]) && ! isset($omittedAssets[$assetKey])) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_CRITICAL_ASSET_UNACCOUNTED',
                        $key . ':' . $assetKey,
                    );
                }
            }
            foreach (array_keys($omittedAssets) as $assetKey) {
                if (isset($plannedAssets[$assetKey])) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_CONSUMER_POLICY_ASSET_NOT_OMITTED',
                        $key . ':' . $assetKey,
                    );
                }
            }
            $props = $manifest['atlas']['example_props'] ?? null;
            if (! is_array($props) || array_is_list($props)) {
                throw new FrameworkComponentException('FRAMEWORK_EXAMPLE_PROPS_MISSING', $key);
            }
            $this->assertPropsReady($key, $manifest, $props, '@framework-admission:example');
            foreach ($manifest['presets'] as $preset => $record) {
                if (! is_string($preset)
                    || preg_match('/^[a-z][a-z0-9_]*$/D', $preset) !== 1
                    || ! is_array($record)
                    || array_is_list($record)
                    || array_diff(['props'], array_keys($record)) !== []
                    || array_diff(array_keys($record), ['props']) !== []
                    || ! is_array($record['props'])
                    || array_is_list($record['props'])
                ) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_PRESET_CONTRACT_INVALID',
                        $key . ':' . (string) $preset,
                    );
                }
                $this->assertPropsReady(
                    $key,
                    $manifest,
                    array_replace($props, $record['props']),
                    '@framework-admission:preset:' . $preset,
                );
            }
        }
    }

    /** @param array<string, mixed> $manifest @param array<string, mixed> $props */
    private function assertPropsReady(string $key, array $manifest, array $props, string $pagePath): void
    {
        $props = $this->consumerPolicy->apply($key, $props, $pagePath, 0);
        $this->propsValidator->validate($manifest, $props);
        $this->renderer->render($manifest, $props, $this->manifests->pairId());
    }
}
