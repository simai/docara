<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Artifact;

final readonly class LegacySmartManifestV1Adapter
{
    public function __construct(
        private DocaraPortableSmartAdmissionPolicy $admission = new DocaraPortableSmartAdmissionPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    public function adapt(array $legacy, string $expectedCode): array
    {
        if (($legacy['schema'] ?? null) !== 'larena.ui.smart_manifest.v1'
            || ($legacy['key'] ?? null) !== $expectedCode
        ) {
            throw new PortableSmartContractException('PORTABLE_SMART_LEGACY_ADAPTER_INVALID', $expectedCode, 'identity');
        }

        $assets = is_array($legacy['assets'] ?? null) ? $legacy['assets'] : [];
        $hasBehavior = ($legacy['frontend']['runtime'] ?? null) !== null;
        $depends = [];
        foreach ($assets as $asset) {
            if (! is_array($asset) || ! is_string($asset['key'] ?? null)) {
                continue;
            }
            $depends[] = $asset['key'];
            $hasBehavior = $hasBehavior || in_array($asset['kind'] ?? null, ['javascript', 'smart_javascript', 'boot'], true);
        }
        $depends = array_values(array_unique($depends));
        sort($depends, SORT_STRING);

        $manifest = [
            'schemaVersion' => '1.0',
            'kind' => 'smart',
            'code' => $expectedCode,
            'title' => (string) ($legacy['atlas']['title'] ?? $expectedCode),
            'description' => (string) ($legacy['atlas']['description'] ?? ''),
            'category' => (string) ($legacy['atlas']['category'] ?? ''),
            'render' => [
                'mode' => 'server-first',
                'strategy' => $hasBehavior ? 'server-first-hydratable' : 'server-static',
                'template' => 'default',
                'hydration' => $hasBehavior ? 'required' : 'none',
                'domStrategy' => $hasBehavior ? 'light-dom-adopt' : 'none',
                'updateStrategy' => $hasBehavior ? 'patch' : 'none',
                'initialHtml' => 'complete',
                'frontendOwnership' => $hasBehavior ? 'behavior' : 'none',
            ],
            'props' => $this->props($legacy['props'] ?? []),
            'assets' => ['css' => [], 'js' => [], 'depends' => $depends],
            'ai' => [
                'tags' => is_array($legacy['atlas']['states'] ?? null)
                    ? array_values(array_filter($legacy['atlas']['states'], 'is_string'))
                    : [],
            ],
            'meta' => [
                'ownerPackage' => (string) ($legacy['owner_package'] ?? ''),
                'version' => (string) ($legacy['version'] ?? ''),
            ],
            'extensions' => [
                'docara' => [
                    'legacySchema' => 'larena.ui.smart_manifest.v1',
                    'legacyKind' => (string) ($legacy['kind'] ?? ''),
                    'renderer' => (string) ($legacy['render']['renderer'] ?? ''),
                    'propsSchema' => $legacy['props'] ?? [],
                    'events' => $legacy['events'] ?? [],
                    'frontend' => $legacy['frontend'] ?? [],
                    'provenance' => $legacy['provenance'] ?? [],
                ],
            ],
        ];
        $this->admission->assertAdmitted($manifest, $expectedCode);

        return $manifest;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function props(mixed $schema): array
    {
        if (! is_array($schema)) {
            return [];
        }
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $portable = [];
        foreach ($properties as $name => $definition) {
            if (! is_string($name) || ! is_array($definition)) {
                continue;
            }
            $type = $definition['type'] ?? 'string';
            if (is_array($type)) {
                $type = current(array_values(array_diff($type, ['null']))) ?: 'string';
            }
            $type = match ($type) {
                'integer' => 'number',
                default => $type,
            };
            if (! in_array($type, ['string', 'number', 'boolean', 'object', 'array'], true)) {
                $type = 'string';
            }
            $portable[$name] = ['type' => $type];
            if (in_array($name, $required, true)) {
                $portable[$name]['required'] = true;
            }
            if (is_array($definition['enum'] ?? null) && $definition['enum'] !== []) {
                $portable[$name]['type'] = 'enum';
                $portable[$name]['values'] = array_values($definition['enum']);
            }
        }
        ksort($portable, SORT_STRING);

        return $portable;
    }
}
