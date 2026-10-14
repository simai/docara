<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Artifact;

final class Sf5SmartArtifactV1Contract
{
    /** Framework-owned portable ABI identity. The class name is a historical Docara consumer label. */
    public const CONTRACT_ID = 'sf.smart_artifact_abi';

    public const SCHEMA_VERSION = '1.0.0';

    public const COMPATIBILITY_ID = 'sf-smart-artifact-abi-v1';

    /** Storage/source-layout compatibility alias; it is not another ABI dialect. */
    public const STORAGE_COMPATIBILITY_ALIAS = 'sf5.smart.artifact.v1';

    public const SOURCE_REVISION = 'b3cdff87563ff78e7eddf044048a4b298fc69036';

    /** @var list<string> */
    private const STRATEGIES = [
        'server-static',
        'server-first-hydratable',
        'client-owned',
        'shadow-dom-owned',
    ];

    /** @var list<string> */
    private const PROP_TYPES = [
        'string',
        'number',
        'boolean',
        'enum',
        'object',
        'array',
        'content',
        'asset',
        'link',
    ];

    /** @param array<string, mixed> $manifest */
    public function assertManifest(array $manifest, string $expectedCode): void
    {
        $this->same('1.0', $manifest['schemaVersion'] ?? null, $expectedCode, 'schemaVersion');
        $this->same('smart', $manifest['kind'] ?? null, $expectedCode, 'kind');
        $this->same($expectedCode, $manifest['code'] ?? null, $expectedCode, 'code');
        $this->expect(
            preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $expectedCode) === 1,
            $expectedCode,
            'code',
        );
        $this->expect(
            is_string($manifest['title'] ?? null) && trim($manifest['title']) !== '',
            $expectedCode,
            'title',
        );

        $render = $this->object($manifest['render'] ?? null, $expectedCode, 'render');
        $this->member($render['mode'] ?? null, ['server-first', 'client-only', 'hybrid'], $expectedCode, 'render.mode');
        $strategy = $render['strategy'] ?? null;
        $this->member($strategy, self::STRATEGIES, $expectedCode, 'render.strategy');
        $this->expect(
            is_string($render['template'] ?? null)
                && preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $render['template']) === 1,
            $expectedCode,
            'render.template',
        );
        $this->member($render['hydration'] ?? null, ['none', 'optional', 'required'], $expectedCode, 'render.hydration');
        $this->member($render['domStrategy'] ?? null, ['none', 'light-dom-adopt', 'host-attributes', 'shadow-dom'], $expectedCode, 'render.domStrategy');
        $this->member($render['updateStrategy'] ?? null, ['none', 'patch', 'rerender'], $expectedCode, 'render.updateStrategy');
        $this->member($render['initialHtml'] ?? null, ['complete', 'skeleton', 'host-only'], $expectedCode, 'render.initialHtml');
        $this->member($render['frontendOwnership'] ?? null, ['none', 'behavior', 'dom'], $expectedCode, 'render.frontendOwnership');
        $this->assertRenderCombination($expectedCode, $strategy, $render);

        foreach ($this->object($manifest['props'] ?? [], $expectedCode, 'props') as $name => $definition) {
            $this->expect(
                is_string($name)
                    && preg_match('/^[a-z][a-zA-Z0-9_-]*$/D', $name) === 1
                    && is_array($definition)
                    && ! array_is_list($definition),
                $expectedCode,
                'props',
            );
            $this->member($definition['type'] ?? null, self::PROP_TYPES, $expectedCode, 'props.' . $name . '.type');
            if (array_key_exists('required', $definition)) {
                $this->expect(is_bool($definition['required']), $expectedCode, 'props.' . $name . '.required');
            }
            if (($definition['type'] ?? null) === 'enum') {
                $values = $definition['values'] ?? null;
                $this->expect(is_array($values) && array_is_list($values) && $values !== [], $expectedCode, 'props.' . $name . '.values');
            }
        }

        $assets = $this->object($manifest['assets'] ?? [], $expectedCode, 'assets');
        foreach ($assets as $group => $items) {
            $this->expect(in_array($group, ['css', 'js', 'depends'], true), $expectedCode, 'assets.' . $group);
            $this->expect(is_array($items) && array_is_list($items), $expectedCode, 'assets.' . $group);
            foreach ($items as $index => $item) {
                $this->expect(is_string($item) && $item !== '', $expectedCode, 'assets.' . $group . '.' . $index);
            }
        }

        foreach (['slots', 'events', 'regions', 'meta', 'extensions', 'ai', 'cache'] as $field) {
            if (array_key_exists($field, $manifest)) {
                $this->object($manifest[$field], $expectedCode, $field);
            }
        }
    }

    /** @param array<string, mixed> $artifact */
    public function assertView(array $artifact, string $smart, string $code): void
    {
        $this->assertNamedArtifact($artifact, 'smart.view', $smart, $code);
    }

    /** @param array<string, mixed> $artifact */
    public function assertPreset(array $artifact, string $smart, string $code): void
    {
        $this->assertNamedArtifact($artifact, 'smart.preset', $smart, $code);
    }

    /** @return array{contract_id:string,schema_version:string,compatibility_id:string,storage_compatibility_alias:string,source_revision:string} */
    public function provenance(): array
    {
        return [
            'contract_id' => self::CONTRACT_ID,
            'schema_version' => self::SCHEMA_VERSION,
            'compatibility_id' => self::COMPATIBILITY_ID,
            'storage_compatibility_alias' => self::STORAGE_COMPATIBILITY_ALIAS,
            'source_revision' => self::SOURCE_REVISION,
        ];
    }

    /**
     * Public effective-artifact identity shared by every provider adapter.
     *
     * @return array{
     *   provider:string,
     *   provider_revision:string,
     *   contract_id:string,
     *   contract_schema_version:string,
     *   contract_compatibility_id:string,
     *   storage_compatibility_alias:string,
     *   contract_source_revision:string,
     *   provider_adapter:string,
     *   template_abi:string,
     *   manifest_sha256:string
     * }
     */
    public function effectiveProvenance(
        string $provider,
        string $providerRevision,
        string $providerAdapter,
        string $templateAbi,
        string $manifestSha256,
    ): array {
        return [
            'provider' => $provider,
            'provider_revision' => $providerRevision,
            'contract_id' => self::CONTRACT_ID,
            'contract_schema_version' => self::SCHEMA_VERSION,
            'contract_compatibility_id' => self::COMPATIBILITY_ID,
            'storage_compatibility_alias' => self::STORAGE_COMPATIBILITY_ALIAS,
            'contract_source_revision' => self::SOURCE_REVISION,
            'provider_adapter' => $providerAdapter,
            'template_abi' => $templateAbi,
            'manifest_sha256' => $manifestSha256,
        ];
    }

    /** @param array<string, mixed> $artifact */
    private function assertNamedArtifact(array $artifact, string $kind, string $smart, string $code): void
    {
        $label = $smart . ':' . $code;
        $this->same('1.0', $artifact['schemaVersion'] ?? null, $label, 'schemaVersion');
        $this->same($kind, $artifact['kind'] ?? null, $label, 'kind');
        $this->same($smart, $artifact['smart'] ?? null, $label, 'smart');
        $this->same($code, $artifact['code'] ?? null, $label, 'code');
        $this->expect(
            preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $smart) === 1
                && preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $code) === 1,
            $label,
            'code',
        );
        foreach (['props', 'slots', 'children', 'meta', 'ai'] as $field) {
            if (array_key_exists($field, $artifact)) {
                $this->object($artifact[$field], $label, $field);
            }
        }
    }

    /** @param array<string, mixed> $render */
    private function assertRenderCombination(string $artifact, mixed $strategy, array $render): void
    {
        $expected = match ($strategy) {
            'server-static' => ['none', 'none', 'complete', 'none'],
            'server-first-hydratable' => ['required', 'light-dom-adopt', 'complete', 'behavior'],
            'client-owned' => ['required', 'host-attributes', null, 'dom'],
            'shadow-dom-owned' => ['required', 'shadow-dom', null, 'dom'],
            default => null,
        };
        $this->expect(is_array($expected), $artifact, 'render.strategy');
        [$hydration, $dom, $initial, $owner] = $expected;
        $this->same($hydration, $render['hydration'] ?? null, $artifact, 'render.hydration');
        $this->same($dom, $render['domStrategy'] ?? null, $artifact, 'render.domStrategy');
        if ($initial !== null) {
            $this->same($initial, $render['initialHtml'] ?? null, $artifact, 'render.initialHtml');
        } else {
            $this->expect(in_array($render['initialHtml'] ?? null, ['skeleton', 'host-only'], true), $artifact, 'render.initialHtml');
        }
        $this->same($owner, $render['frontendOwnership'] ?? null, $artifact, 'render.frontendOwnership');
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $artifact, string $path): array
    {
        $this->expect(is_array($value) && ($value === [] || ! array_is_list($value)), $artifact, $path);

        return $value;
    }

    /** @param list<string> $allowed */
    private function member(mixed $value, array $allowed, string $artifact, string $path): void
    {
        $this->expect(is_string($value) && in_array($value, $allowed, true), $artifact, $path);
    }

    private function same(mixed $expected, mixed $actual, string $artifact, string $path): void
    {
        $this->expect($actual === $expected, $artifact, $path);
    }

    private function expect(bool $condition, string $artifact, string $path): void
    {
        if (! $condition) {
            throw new PortableSmartContractException('PORTABLE_SMART_CONTRACT_INVALID', $artifact, $path);
        }
    }
}
