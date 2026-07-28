<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

/**
 * Platform-neutral contract shared by Framework-owned and Docara-owned Smart
 * manifests. Product-specific admission rules remain in their owner layers.
 */
final class SmartManifestValidator
{
    /** @param array<string, mixed> $manifest */
    public function assertValid(string $expectedKey, array $manifest): void
    {
        $this->expect(($manifest['schema'] ?? null) === 'larena.ui.smart_manifest.v1', $expectedKey, 'schema');
        $this->expect(($manifest['key'] ?? null) === $expectedKey, $expectedKey, 'key');
        $this->expect(
            preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $expectedKey) === 1,
            $expectedKey,
            'key',
        );
        $this->expect(
            is_string($manifest['version'] ?? null)
                && preg_match('/^v?\d+\.\d+\.\d+$/D', $manifest['version']) === 1,
            $expectedKey,
            'version',
        );
        $this->expect(
            is_string($manifest['owner_package'] ?? null)
                && preg_match('#^[a-z0-9_.-]+/[a-z0-9_.-]+$#D', $manifest['owner_package']) === 1,
            $expectedKey,
            'owner_package',
        );
        $this->expect(in_array($manifest['kind'] ?? null, ['smart', 'composite'], true), $expectedKey, 'kind');

        $props = $this->object($manifest['props'] ?? null, $expectedKey, 'props');
        $this->expect(($props['type'] ?? null) === 'object', $expectedKey, 'props.type');
        $properties = $this->object($props['properties'] ?? null, $expectedKey, 'props.properties');
        foreach ($properties as $name => $schema) {
            $this->expect(
                is_string($name) && preg_match('/^[a-z][a-z0-9_-]*$/D', $name) === 1 && is_array($schema),
                $expectedKey,
                'props.properties',
            );
            $this->assertPropertySchema($expectedKey, $name, $schema);
        }
        $required = $this->list($props['required'] ?? null, $expectedKey, 'props.required');
        $this->expect(count($required) === count(array_unique($required)), $expectedKey, 'props.required');
        foreach ($required as $name) {
            $this->expect(is_string($name) && array_key_exists($name, $properties), $expectedKey, 'props.required');
        }
        $this->expect(($props['additionalProperties'] ?? null) === false, $expectedKey, 'props.additionalProperties');

        $this->object($manifest['slots'] ?? null, $expectedKey, 'slots');
        $events = $this->object($manifest['events'] ?? null, $expectedKey, 'events');
        foreach ($events as $name => $event) {
            $this->expect(
                is_string($name)
                    && preg_match('/^[a-z][a-z0-9_-]*$/D', $name) === 1
                    && is_array($event)
                    && in_array($event['kind'] ?? null, ['dom', 'custom', 'lifecycle'], true)
                    && is_bool($event['backend_handler_binding'] ?? null),
                $expectedKey,
                'events',
            );
        }
        $views = $this->object($manifest['views'] ?? null, $expectedKey, 'views');
        $this->expect($views !== [] && isset($views['default']), $expectedKey, 'views.default');
        foreach ($views as $name => $view) {
            $this->expect(
                is_string($name)
                    && preg_match('/^[a-z][a-z0-9_-]*$/D', $name) === 1
                    && is_array($view),
                $expectedKey,
                'views',
            );
        }
        $presets = $this->object($manifest['presets'] ?? null, $expectedKey, 'presets');
        foreach ($presets as $name => $preset) {
            $presetView = is_array($preset) ? ($preset['view'] ?? null) : null;
            $presetProps = is_array($preset) ? ($preset['props'] ?? null) : null;
            $this->expect(
                is_string($name)
                    && preg_match('/^[a-z][a-z0-9_-]*$/D', $name) === 1
                    && is_array($preset)
                    && ((is_string($presetView) && isset($views[$presetView]))
                        || (is_array($presetProps) && ! array_is_list($presetProps))),
                $expectedKey,
                'presets',
            );
            if (is_array($presetProps)) {
                foreach (array_keys($presetProps) as $prop) {
                    $this->expect(is_string($prop) && isset($properties[$prop]), $expectedKey, 'presets.' . $name . '.props');
                }
            }
        }
        $this->object($manifest['constraints'] ?? null, $expectedKey, 'constraints');

        $render = $this->object($manifest['render'] ?? null, $expectedKey, 'render');
        $this->expect(($render['strategy'] ?? null) === 'host', $expectedKey, 'render.strategy');
        $this->expect(
            is_string($render['renderer'] ?? null)
                && preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $render['renderer']) === 1,
            $expectedKey,
            'render.renderer',
        );
        $frontend = $this->object($manifest['frontend'] ?? null, $expectedKey, 'frontend');
        $runtime = $frontend['runtime'] ?? null;
        $tag = $frontend['tag'] ?? null;
        $this->expect(
            ($runtime === null && $tag === null)
                || ($runtime === 'simai-framework'
                    && is_string($tag)
                    && preg_match('/^sf-[a-z][a-z0-9-]*$/D', $tag) === 1),
            $expectedKey,
            'frontend',
        );

        $assets = $this->list($manifest['assets'] ?? null, $expectedKey, 'assets');
        $this->expect($assets !== [], $expectedKey, 'assets');
        $assetKeys = [];
        foreach ($assets as $asset) {
            $this->expect(
                is_array($asset)
                    && is_string($asset['key'] ?? null)
                    && preg_match('/^[a-z][a-z0-9_.-]+$/D', $asset['key']) === 1
                    && in_array($asset['kind'] ?? null, ['css', 'javascript', 'smart_javascript', 'inline_css', 'boot'], true)
                    && is_bool($asset['critical'] ?? null),
                $expectedKey,
                'assets',
            );
            $assetKeys[] = $asset['key'];
        }
        $this->expect(count($assetKeys) === count(array_unique($assetKeys)), $expectedKey, 'assets');

        $atlas = $this->object($manifest['atlas'] ?? null, $expectedKey, 'atlas');
        $this->expect(is_bool($atlas['visible'] ?? null), $expectedKey, 'atlas.visible');
        foreach (['title', 'description', 'category', 'status'] as $field) {
            $this->expect(is_string($atlas[$field] ?? null) && trim($atlas[$field]) !== '', $expectedKey, 'atlas.' . $field);
        }
        $states = $this->list($atlas['states'] ?? null, $expectedKey, 'atlas.states');
        $this->expect($states !== [], $expectedKey, 'atlas.states');
        foreach ($states as $state) {
            $this->expect(is_string($state) && preg_match('/^[a-z][a-z0-9_-]*$/D', $state) === 1, $expectedKey, 'atlas.states');
        }
        $readiness = $this->object($atlas['readiness'] ?? null, $expectedKey, 'atlas.readiness');
        $readinessKeys = ['safe_to_suggest', 'safe_to_render', 'safe_to_bind_data', 'safe_to_execute_effect'];
        $this->expect(array_keys($readiness) === $readinessKeys, $expectedKey, 'atlas.readiness');
        foreach ($readiness as $value) {
            $this->expect(is_bool($value), $expectedKey, 'atlas.readiness');
        }
        $accessibility = $this->list($atlas['accessibility'] ?? null, $expectedKey, 'atlas.accessibility');
        foreach ($accessibility as $capability) {
            $this->expect(
                is_string($capability) && preg_match('/^[a-z][a-z0-9_]*$/D', $capability) === 1,
                $expectedKey,
                'atlas.accessibility',
            );
        }
        $exampleProps = $this->object($atlas['example_props'] ?? null, $expectedKey, 'atlas.example_props');
        try {
            (new SmartPropsValidator)->assertValid($expectedKey, $manifest, $exampleProps);
        } catch (SmartPropsValidationException) {
            throw new SmartManifestValidationException('SMART_MANIFEST_INVALID', $expectedKey, 'atlas.example_props');
        }
        $controls = $this->list($atlas['controls'] ?? null, $expectedKey, 'atlas.controls');
        foreach ($controls as $control) {
            $source = is_array($control) ? ($control['source'] ?? null) : null;
            $controlKey = is_array($control) ? ($control['key'] ?? null) : null;
            $this->expect(
                is_array($control)
                    && is_string($controlKey)
                    && preg_match('/^[a-z][a-z0-9_-]*$/D', $controlKey) === 1
                    && in_array($source, ['prop', 'preset'], true)
                    && (($source === 'prop' && isset($properties[$controlKey]))
                        || $source === 'preset')
                    && is_string($control['widget'] ?? null)
                    && preg_match('/^[a-z][a-z0-9_-]*$/D', $control['widget']) === 1,
                $expectedKey,
                'atlas.controls',
            );
        }

        $provenance = $this->object($manifest['provenance'] ?? null, $expectedKey, 'provenance');
        $this->expect(is_string($provenance['source'] ?? null) && $provenance['source'] !== '', $expectedKey, 'provenance.source');
        $this->expect(
            is_string($provenance['reference_status'] ?? null)
                && preg_match('/^[a-z][a-z0-9_]*$/D', $provenance['reference_status']) === 1,
            $expectedKey,
            'provenance.reference_status',
        );
    }

    /** @param array<string, mixed> $schema */
    private function assertPropertySchema(string $component, string $name, array $schema): void
    {
        $type = $schema['type'] ?? null;
        $types = is_string($type) ? [$type] : $type;
        $this->expect(
            is_array($types)
                && array_is_list($types)
                && $types !== []
                && count($types) === count(array_unique($types))
                && array_diff($types, ['string', 'boolean', 'integer', 'number', 'array', 'object', 'null']) === [],
            $component,
            'props.properties.' . $name . '.type',
        );
        if (isset($schema['enum'])) {
            $values = $this->list($schema['enum'], $component, 'props.properties.' . $name . '.enum');
            $this->expect($values !== [], $component, 'props.properties.' . $name . '.enum');
        }
        foreach (['minLength', 'maxLength'] as $rule) {
            if (array_key_exists($rule, $schema)) {
                $this->expect(in_array('string', $types, true) && is_int($schema[$rule]) && $schema[$rule] >= 0, $component, 'props.properties.' . $name . '.' . $rule);
            }
        }
        foreach (['minimum', 'maximum'] as $rule) {
            if (array_key_exists($rule, $schema)) {
                $this->expect(array_intersect($types, ['integer', 'number']) !== [] && is_numeric($schema[$rule]), $component, 'props.properties.' . $name . '.' . $rule);
            }
        }
        if (in_array('object', $types, true) && isset($schema['properties'])) {
            $nested = $this->object($schema['properties'], $component, 'props.properties.' . $name . '.properties');
            foreach ($nested as $nestedName => $nestedSchema) {
                $this->expect(is_string($nestedName) && is_array($nestedSchema), $component, 'props.properties.' . $name . '.properties');
                $this->assertPropertySchema($component, $name . '.' . $nestedName, $nestedSchema);
            }
            $required = $schema['required'] ?? [];
            $required = $this->list($required, $component, 'props.properties.' . $name . '.required');
            foreach ($required as $requiredName) {
                $this->expect(is_string($requiredName) && array_key_exists($requiredName, $nested), $component, 'props.properties.' . $name . '.required');
            }
            if (array_key_exists('additionalProperties', $schema)) {
                $this->expect($schema['additionalProperties'] === false, $component, 'props.properties.' . $name . '.additionalProperties');
            }
        }
        if (in_array('array', $types, true) && isset($schema['items'])) {
            $this->expect(is_array($schema['items']), $component, 'props.properties.' . $name . '.items');
            $this->assertPropertySchema($component, $name . '[]', $schema['items']);
        }
        foreach (['minItems', 'maxItems'] as $rule) {
            if (array_key_exists($rule, $schema)) {
                $this->expect(in_array('array', $types, true) && is_int($schema[$rule]) && $schema[$rule] >= 0, $component, 'props.properties.' . $name . '.' . $rule);
            }
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $component, string $path): array
    {
        $this->expect(is_array($value) && ($value === [] || ! array_is_list($value)), $component, $path);

        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value, string $component, string $path): array
    {
        $this->expect(is_array($value) && array_is_list($value), $component, $path);

        return $value;
    }

    private function expect(bool $condition, string $component, string $path): void
    {
        if (! $condition) {
            throw new SmartManifestValidationException('SMART_MANIFEST_INVALID', $component, $path);
        }
    }
}
