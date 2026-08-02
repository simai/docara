<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedSmartPlan;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\Runtime\PortableSmartPropsValidator;
use Simai\Docara\Smart\SmartRegistry;

final readonly class PortableProviderPlanResolver implements ProviderSmartPlanResolver
{
    public function __construct(
        private string $ownedProviderId,
        private SmartRegistry $smarts,
        private PortableSmartPropsValidator $props = new PortableSmartPropsValidator,
    ) {}

    public function providerId(): string
    {
        return $this->ownedProviderId;
    }

    public function resolve(SmartCallNode $call): ResolvedSmartPlan
    {
        $definition = $this->smarts->definition($call->smart);
        if ($definition->providerId !== $this->ownedProviderId || $definition->root === null) {
            throw new PortableConfigurationException('PORTABLE_SMART_PROVIDER_MISMATCH', $call->smart);
        }
        $viewRecord = $definition->views[$call->view] ?? null;
        if (! is_array($viewRecord)) {
            throw new PortableConfigurationException('PORTABLE_SMART_VIEW_UNKNOWN', $call->smart . ':' . $call->view);
        }
        $view = $this->json($definition->root, $viewRecord['path'], $call->smart . ':view');
        $presetCode = $call->props['preset'] ?? null;
        $authorProps = $call->props;
        unset($authorProps['preset']);
        $preset = [];
        if ($presetCode !== null) {
            if (! is_string($presetCode) || ! isset($definition->presets[$presetCode])) {
                throw new PortableConfigurationException('PORTABLE_SMART_PRESET_UNKNOWN', $call->smart);
            }
            $preset = $this->json($definition->root, $definition->presets[$presetCode]['path'], $call->smart . ':preset');
        }
        $props = array_replace(
            is_array($preset['props'] ?? null) ? $preset['props'] : [],
            is_array($view['props'] ?? null) ? $view['props'] : [],
            $authorProps,
        );
        $this->props->validate($definition->key, $definition->portableManifest, $props);
        $assets = array_values(array_unique([
            ...array_keys($definition->assets),
            ...array_values(array_filter($definition->portableManifest['assets']['depends'] ?? [], 'is_string')),
        ]));
        sort($assets, SORT_STRING);

        return new ResolvedSmartPlan(
            $call->id(),
            $definition->key,
            $call->view,
            $viewRecord['template'],
            $props,
            $assets,
            $definition->provenance + [
                'provider' => $definition->providerId,
                'runtime' => 'portable-smart',
                'portable_strategy' => $definition->strategy,
                'input_adapter' => $definition->adapterId ?? 'smart.props',
                'portable_manifest' => $definition->portableManifest,
                'view' => $viewRecord['path'],
                'view_sha256' => hash_file('sha256', $definition->root . '/' . $viewRecord['path']),
                'preset' => $presetCode,
            ],
        );
    }

    /** @return array<string,mixed> */
    private function json(string $root, string $relative, string $label): array
    {
        $path = $root . '/' . ltrim($relative, '/');
        $real = realpath($path);
        if ($real === false || is_link($path) || ! str_starts_with($real, rtrim($root, '/') . '/')) {
            throw new PortableConfigurationException('PORTABLE_SMART_ARTIFACT_UNSAFE', $label);
        }
        try {
            $value = json_decode((string) file_get_contents($real), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new PortableConfigurationException('PORTABLE_SMART_ARTIFACT_INVALID', $label, $exception);
        }

        return is_array($value) && ! array_is_list($value)
            ? $value
            : throw new PortableConfigurationException('PORTABLE_SMART_ARTIFACT_INVALID', $label);
    }
}
