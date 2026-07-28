<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final class FrameworkPropsValidator
{
    /** @param array<string, mixed> $manifest @param array<string, mixed> $props */
    public function validate(array $manifest, array $props): void
    {
        $component = (string) ($manifest['key'] ?? 'unknown');
        $schema = $manifest['props'] ?? null;
        if (! is_array($schema)
            || ($schema['type'] ?? null) !== 'object'
            || ! is_array($schema['properties'] ?? null)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_PROPS_SCHEMA_INVALID', $component);
        }

        $properties = $schema['properties'];
        foreach ($props as $name => $value) {
            if (! is_string($name) || ! isset($properties[$name]) || ! is_array($properties[$name])) {
                throw new FrameworkComponentException('FRAMEWORK_PROP_UNKNOWN', $component . ':' . (string) $name);
            }
            $property = $properties[$name];
            $this->assertType($component, $name, $value, (string) ($property['type'] ?? ''));
            if (is_array($property['enum'] ?? null) && ! in_array($value, $property['enum'], true)) {
                throw new FrameworkComponentException('FRAMEWORK_PROP_ENUM_INVALID', $component . ':' . $name);
            }
            if (is_string($value)) {
                $this->assertStringRules($component, $name, $value, $property);
            }
            if (is_int($value) || is_float($value)) {
                $this->assertNumericRules($component, $name, $value, $property);
            }
        }

        foreach (is_array($schema['required'] ?? null) ? $schema['required'] : [] as $required) {
            if (! is_string($required) || ! array_key_exists($required, $props)) {
                throw new FrameworkComponentException('FRAMEWORK_PROP_REQUIRED', $component . ':' . (string) $required);
            }
        }

        $this->assertConstraints($component, is_array($manifest['constraints'] ?? null) ? $manifest['constraints'] : [], $props);
    }

    /** @param array<string, mixed> $property */
    private function assertNumericRules(
        string $component,
        string $name,
        int|float $value,
        array $property,
    ): void {
        if (array_key_exists('minimum', $property)
            && ((! is_int($property['minimum']) && ! is_float($property['minimum']))
                || $value < $property['minimum'])
        ) {
            throw new FrameworkComponentException('FRAMEWORK_PROP_MINIMUM_INVALID', $component . ':' . $name);
        }
        if (array_key_exists('maximum', $property)
            && ((! is_int($property['maximum']) && ! is_float($property['maximum']))
                || $value > $property['maximum'])
        ) {
            throw new FrameworkComponentException('FRAMEWORK_PROP_MAXIMUM_INVALID', $component . ':' . $name);
        }
    }

    /** @param array<string, mixed> $property */
    private function assertStringRules(string $component, string $name, string $value, array $property): void
    {
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if (isset($property['minLength']) && (! is_int($property['minLength']) || $length < $property['minLength'])) {
            throw new FrameworkComponentException('FRAMEWORK_PROP_MIN_LENGTH_INVALID', $component . ':' . $name);
        }
        if (isset($property['maxLength']) && (! is_int($property['maxLength']) || $length > $property['maxLength'])) {
            throw new FrameworkComponentException('FRAMEWORK_PROP_MAX_LENGTH_INVALID', $component . ':' . $name);
        }
        if (isset($property['pattern'])) {
            if (! is_string($property['pattern']) || $property['pattern'] === '') {
                throw new FrameworkComponentException('FRAMEWORK_PROPS_SCHEMA_INVALID', $component);
            }
            $matched = @preg_match('~' . str_replace('~', '\\~', $property['pattern']) . '~u', $value);
            if ($matched !== 1) {
                throw new FrameworkComponentException('FRAMEWORK_PROP_PATTERN_INVALID', $component . ':' . $name);
            }
        }
    }

    /** @param array<string, mixed> $constraints @param array<string, mixed> $props */
    private function assertConstraints(string $component, array $constraints, array $props): void
    {
        $combinations = $constraints['allowed_combinations'] ?? [];
        if (! is_array($combinations)) {
            throw new FrameworkComponentException('FRAMEWORK_CONSTRAINTS_INVALID', $component);
        }
        foreach ($combinations as $combination) {
            $keys = is_array($combination) ? ($combination['keys'] ?? null) : null;
            $values = is_array($combination) ? ($combination['values'] ?? null) : null;
            if (! is_array($keys) || ! array_is_list($keys) || $keys === [] || ! is_array($values) || ! array_is_list($values)) {
                throw new FrameworkComponentException('FRAMEWORK_CONSTRAINTS_INVALID', $component);
            }
            $actual = [];
            foreach ($keys as $key) {
                if (! is_string($key) || ! array_key_exists($key, $props)) {
                    continue 2;
                }
                $actual[] = $props[$key];
            }
            $matched = false;
            foreach ($values as $allowed) {
                if (is_array($allowed) && array_is_list($allowed) && $allowed === $actual) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                throw new FrameworkComponentException('FRAMEWORK_CONSTRAINT_COMBINATION_INVALID', $component . ':' . implode(',', $keys));
            }
        }

        $requirements = $constraints['requires'] ?? [];
        if (! is_array($requirements)) {
            throw new FrameworkComponentException('FRAMEWORK_CONSTRAINTS_INVALID', $component);
        }
        foreach ($requirements as $requirement) {
            $when = is_array($requirement) && is_array($requirement['when'] ?? null) ? $requirement['when'] : null;
            $then = is_array($requirement) && is_array($requirement['then'] ?? null) ? $requirement['then'] : null;
            if ($when === null || $when === [] || $then === null || $then === []) {
                throw new FrameworkComponentException('FRAMEWORK_CONSTRAINTS_INVALID', $component);
            }
            foreach ($when as $name => $value) {
                if (! is_string($name) || ! array_key_exists($name, $props) || $props[$name] !== $value) {
                    continue 2;
                }
            }
            foreach ($then as $name => $value) {
                if (! is_string($name) || ! array_key_exists($name, $props) || $props[$name] !== $value) {
                    throw new FrameworkComponentException('FRAMEWORK_CONSTRAINT_REQUIREMENT_INVALID', $component . ':' . (string) $name);
                }
            }
        }
    }

    private function assertType(string $component, string $name, mixed $value, string $type): void
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && ! array_is_list($value),
            default => false,
        };
        if (! $valid) {
            throw new FrameworkComponentException('FRAMEWORK_PROP_TYPE_INVALID', $component . ':' . $name . ':' . $type);
        }
    }
}
