<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;

final class FrameworkManifestContract
{
    /** @param array<string, mixed> $manifest */
    public function assertValid(string $component, array $manifest): void
    {
        $properties = $this->properties($component, $manifest);
        $required = $manifest['props']['required'] ?? null;
        if (! is_array($required)
            || ! array_is_list($required)
            || count($required) !== count(array_unique($required))
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID', $component);
        }
        foreach ($required as $name) {
            if (! is_string($name) || ! array_key_exists($name, $properties)) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                    $component . ':' . (string) $name,
                );
            }
        }

        $this->assertAtlas($component, $manifest);
        $this->assertPresets($component, $manifest, $properties);
        $this->assertConstraints($component, $manifest, $properties);
        $this->analyzeControls($component, $manifest, $properties);
    }

    /** @param array<string, mixed> $manifest */
    private function assertAtlas(string $component, array $manifest): void
    {
        $atlas = $manifest['atlas'] ?? null;
        $readiness = is_array($atlas) ? ($atlas['readiness'] ?? null) : null;
        $states = is_array($atlas) ? ($atlas['states'] ?? null) : null;
        if (! is_array($atlas)
            || ! is_string($atlas['title'] ?? null)
            || trim($atlas['title']) === ''
            || ! is_string($atlas['description'] ?? null)
            || trim($atlas['description']) === ''
            || ! is_string($atlas['category'] ?? null)
            || preg_match('/^[a-z][a-z0-9_]*$/D', $atlas['category']) !== 1
            || ! is_array($states)
            || ! array_is_list($states)
            || $states === []
            || count($states) !== count(array_unique($states))
            || ! is_array($readiness)
            || array_is_list($readiness)
            || ! $this->hasExactKeys($readiness, [
                'safe_to_suggest',
                'safe_to_render',
                'safe_to_bind_data',
                'safe_to_execute_effect',
            ])
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_ATLAS_INVALID', $component);
        }
        foreach ($states as $state) {
            if (! is_string($state) || preg_match('/^[a-z][a-z0-9_-]*$/D', $state) !== 1) {
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_ATLAS_INVALID', $component);
            }
        }
        foreach ($readiness as $value) {
            if (! is_bool($value)) {
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_ATLAS_INVALID', $component);
            }
        }
        if ($readiness['safe_to_suggest'] !== true || $readiness['safe_to_render'] !== true) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_READINESS_NOT_ADMITTED', $component);
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, list<string>>
     */
    public function mirrorMap(string $component, array $manifest): array
    {
        return $this->analyzeControls($component, $manifest, $this->properties($component, $manifest));
    }

    /** @param array<string, mixed> $manifest */
    public function assertPropertyValue(
        string $component,
        array $manifest,
        string $name,
        mixed $value,
        string $errorCode,
    ): void {
        $properties = $this->properties($component, $manifest);
        if (! isset($properties[$name])) {
            throw new FrameworkComponentException($errorCode, $component . ':' . $name);
        }
        $this->assertValue($component, $name, $value, $properties[$name], true, $errorCode);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array<string, mixed>>
     */
    private function properties(string $component, array $manifest): array
    {
        $properties = $manifest['props']['properties'] ?? null;
        if (! is_array($properties) || array_is_list($properties) || $properties === []) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID', $component);
        }
        foreach ($properties as $name => $schema) {
            if (! is_string($name)
                || preg_match('/^[a-z][a-z0-9_-]*$/D', $name) !== 1
                || ! is_array($schema)
                || array_is_list($schema)
                || array_diff(
                    array_keys($schema),
                    ['type', 'enum', 'minLength', 'maxLength', 'pattern', 'minimum', 'maximum'],
                ) !== []
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                    $component . ':' . (string) $name,
                );
            }
            $type = $schema['type'] ?? null;
            if (! is_string($type)
                || ! in_array($type, ['string', 'boolean', 'integer', 'number'], true)
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_TYPE_UNSUPPORTED',
                    $component . ':' . $name,
                );
            }
            $this->assertSchemaRules($component, $name, $type, $schema);
        }

        return $properties;
    }

    /** @param array<string, mixed> $schema */
    private function assertSchemaRules(string $component, string $name, string $type, array $schema): void
    {
        if (array_key_exists('enum', $schema)) {
            if (! is_array($schema['enum'])
                || ! array_is_list($schema['enum'])
                || $schema['enum'] === []
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                    $component . ':' . $name . ':enum',
                );
            }
            $encoded = [];
            foreach ($schema['enum'] as $value) {
                $this->assertValue(
                    $component,
                    $name,
                    $value,
                    $schema,
                    false,
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                );
                $encoded[] = serialize($value);
            }
            if (count($encoded) !== count(array_unique($encoded))) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                    $component . ':' . $name . ':enum',
                );
            }
        }

        foreach (['minLength', 'maxLength'] as $rule) {
            if (array_key_exists($rule, $schema)
                && ($type !== 'string' || ! is_int($schema[$rule]) || $schema[$rule] < 0)
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                    $component . ':' . $name . ':' . $rule,
                );
            }
        }
        if (isset($schema['minLength'], $schema['maxLength'])
            && $schema['minLength'] > $schema['maxLength']
        ) {
            throw new FrameworkComponentException(
                'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                $component . ':' . $name . ':length',
            );
        }
        if (array_key_exists('pattern', $schema)) {
            if ($type !== 'string' || ! is_string($schema['pattern']) || $schema['pattern'] === '') {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_PATTERN_INVALID',
                    $component . ':' . $name,
                );
            }
            $result = @preg_match(
                '~' . str_replace('~', '\\~', $schema['pattern']) . '~u',
                '',
            );
            if ($result === false) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_PATTERN_INVALID',
                    $component . ':' . $name,
                );
            }
        }
        foreach (['minimum', 'maximum'] as $rule) {
            if (array_key_exists($rule, $schema)
                && (! in_array($type, ['integer', 'number'], true)
                    || (! is_int($schema[$rule]) && ! is_float($schema[$rule])))
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                    $component . ':' . $name . ':' . $rule,
                );
            }
        }
        if (isset($schema['minimum'], $schema['maximum'])
            && $schema['minimum'] > $schema['maximum']
        ) {
            throw new FrameworkComponentException(
                'FRAMEWORK_MANIFEST_PROP_SCHEMA_INVALID',
                $component . ':' . $name . ':range',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<string, mixed>>  $properties
     */
    private function assertPresets(string $component, array $manifest, array $properties): void
    {
        $presets = $manifest['presets'] ?? null;
        if (! is_array($presets) || ($presets !== [] && array_is_list($presets))) {
            throw new FrameworkComponentException('FRAMEWORK_PRESET_CONTRACT_INVALID', $component);
        }
        foreach ($presets as $name => $record) {
            if (! is_string($name)
                || preg_match('/^[a-z][a-z0-9_]*$/D', $name) !== 1
                || ! is_array($record)
                || array_is_list($record)
                || ! $this->hasExactKeys($record, ['props'])
                || ! is_array($record['props'])
                || array_is_list($record['props'])
                || $record['props'] === []
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_PRESET_CONTRACT_INVALID',
                    $component . ':' . (string) $name,
                );
            }
            foreach ($record['props'] as $prop => $value) {
                $schema = is_string($prop) ? ($properties[$prop] ?? null) : null;
                if (! is_array($schema)) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_PRESET_CONTRACT_INVALID',
                        $component . ':' . (string) $name . ':' . (string) $prop,
                    );
                }
                $this->assertValue(
                    $component,
                    $prop,
                    $value,
                    $schema,
                    true,
                    'FRAMEWORK_PRESET_CONTRACT_INVALID',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<string, mixed>>  $properties
     */
    private function assertConstraints(string $component, array $manifest, array $properties): void
    {
        $constraints = $manifest['constraints'] ?? null;
        if (! is_array($constraints)
            || ($constraints !== [] && array_is_list($constraints))
            || array_diff(array_keys($constraints), ['allowed_combinations', 'requires']) !== []
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONSTRAINT_INVALID', $component);
        }
        $combinations = $constraints['allowed_combinations'] ?? [];
        $requirements = $constraints['requires'] ?? [];
        if (! is_array($combinations)
            || ! array_is_list($combinations)
            || ! is_array($requirements)
            || ! array_is_list($requirements)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONSTRAINT_INVALID', $component);
        }

        foreach ($combinations as $combination) {
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
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONSTRAINT_INVALID', $component);
            }
            foreach ($combination['keys'] as $key) {
                if (! is_string($key) || ! isset($properties[$key])) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_MANIFEST_CONSTRAINT_INVALID',
                        $component . ':' . (string) $key,
                    );
                }
            }
            foreach ($combination['values'] as $tuple) {
                if (! is_array($tuple)
                    || ! array_is_list($tuple)
                    || count($tuple) !== count($combination['keys'])
                ) {
                    throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONSTRAINT_INVALID', $component);
                }
                foreach ($tuple as $index => $value) {
                    $key = $combination['keys'][$index];
                    $this->assertValue($component, $key, $value, $properties[$key]);
                }
            }
        }

        foreach ($requirements as $requirement) {
            if (! is_array($requirement)
                || array_is_list($requirement)
                || ! $this->hasExactKeys($requirement, ['when', 'then'])
            ) {
                throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONSTRAINT_INVALID', $component);
            }
            foreach (['when', 'then'] as $branch) {
                $condition = $requirement[$branch];
                if (! is_array($condition) || array_is_list($condition) || $condition === []) {
                    throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONSTRAINT_INVALID', $component);
                }
                foreach ($condition as $key => $value) {
                    if (! is_string($key) || ! isset($properties[$key])) {
                        throw new FrameworkComponentException(
                            'FRAMEWORK_MANIFEST_CONSTRAINT_INVALID',
                            $component . ':' . (string) $key,
                        );
                    }
                    $this->assertValue($component, $key, $value, $properties[$key]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, array<string, mixed>>  $properties
     * @return array<string, list<string>>
     */
    private function analyzeControls(string $component, array $manifest, array $properties): array
    {
        $controls = $manifest['atlas']['controls'] ?? null;
        if (! is_array($controls) || ! array_is_list($controls)) {
            throw new FrameworkComponentException('FRAMEWORK_MANIFEST_CONTROL_INVALID', $component);
        }
        $seen = [];
        $mirrors = [];
        foreach ($controls as $control) {
            $key = is_array($control) ? ($control['key'] ?? null) : null;
            $source = is_array($control) ? ($control['source'] ?? null) : null;
            $widget = is_array($control) ? ($control['widget'] ?? null) : null;
            if (! is_array($control)
                || array_is_list($control)
                || ! is_string($key)
                || preg_match('/^[a-z][a-z0-9_-]*$/D', $key) !== 1
                || isset($seen[$key])
                || ! in_array($source, ['prop', 'preset'], true)
                || ! is_string($widget)
                || trim($widget) === ''
                || ($source === 'prop' && ! isset($properties[$key]))
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_CONTROL_INVALID',
                    $component . ':' . (string) $key,
                );
            }
            $seen[$key] = true;

            if (array_key_exists('linked_props', $control)) {
                $linked = $control['linked_props'];
                if (! is_array($linked) || array_is_list($linked) || $linked === []) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_MANIFEST_CONTROL_INVALID',
                        $component . ':' . $key . ':linked_props',
                    );
                }
                foreach ($linked as $target => $value) {
                    if (! is_string($target) || ! isset($properties[$target])) {
                        throw new FrameworkComponentException(
                            'FRAMEWORK_MANIFEST_CONTROL_INVALID',
                            $component . ':' . $key . ':linked_props',
                        );
                    }
                    $this->assertValue(
                        $component,
                        $target,
                        $value,
                        $properties[$target],
                        true,
                        'FRAMEWORK_MANIFEST_CONTROL_INVALID',
                    );
                }
            }

            if (! array_key_exists('mirror_props', $control)) {
                continue;
            }
            $targets = $control['mirror_props'];
            if ($source !== 'prop'
                || ! is_array($targets)
                || ! array_is_list($targets)
                || $targets === []
                || count($targets) !== count(array_unique($targets))
            ) {
                throw new FrameworkComponentException(
                    'FRAMEWORK_MANIFEST_CONTROL_INVALID',
                    $component . ':' . $key . ':mirror_props',
                );
            }
            foreach ($targets as $target) {
                if (! is_string($target)
                    || $target === $key
                    || ! isset($properties[$target])
                    || CanonicalJson::encode($properties[$key])
                        !== CanonicalJson::encode($properties[$target])
                ) {
                    throw new FrameworkComponentException(
                        'FRAMEWORK_MANIFEST_CONTROL_INVALID',
                        $component . ':' . $key . ':mirror_props',
                    );
                }
            }
            sort($targets, SORT_STRING);
            $mirrors[$key] = $targets;
        }
        ksort($mirrors, SORT_STRING);

        return $mirrors;
    }

    /** @param array<string, mixed> $schema */
    private function assertValue(
        string $component,
        string $name,
        mixed $value,
        array $schema,
        bool $checkEnum = true,
        string $errorCode = 'FRAMEWORK_MANIFEST_CONSTRAINT_INVALID',
    ): void {
        $type = (string) ($schema['type'] ?? '');
        $validType = match ($type) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            default => false,
        };
        if (! $validType
            || ($checkEnum
                && isset($schema['enum'])
                && ! in_array($value, $schema['enum'], true))
        ) {
            throw new FrameworkComponentException(
                $errorCode,
                $component . ':' . $name,
            );
        }
        if (is_string($value)) {
            $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
            if ((isset($schema['minLength']) && $length < $schema['minLength'])
                || (isset($schema['maxLength']) && $length > $schema['maxLength'])
                || (isset($schema['pattern'])
                    && preg_match(
                        '~' . str_replace('~', '\\~', $schema['pattern']) . '~u',
                        $value,
                    ) !== 1)
            ) {
                throw new FrameworkComponentException(
                    $errorCode,
                    $component . ':' . $name,
                );
            }
        }
        if ((is_int($value) || is_float($value))
            && ((isset($schema['minimum']) && $value < $schema['minimum'])
                || (isset($schema['maximum']) && $value > $schema['maximum']))
        ) {
            throw new FrameworkComponentException(
                $errorCode,
                $component . ':' . $name,
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
}
