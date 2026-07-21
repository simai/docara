<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final class SmartPropsValidator
{
    /** @param array<string, mixed> $manifest @param array<string, mixed> $props */
    public function assertValid(string $component, array $manifest, array $props): void
    {
        $this->assertValue($component, 'props', $props, $manifest['props']);
    }

    /** @param array<string, mixed> $schema */
    private function assertValue(string $component, string $path, mixed $value, array $schema): void
    {
        $type = $schema['type'] ?? null;
        $types = is_string($type) ? [$type] : $type;
        if (! is_array($types) || ! $this->matchesAnyType($value, $types)) {
            throw new SmartPropsValidationException($component, $path);
        }
        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            throw new SmartPropsValidationException($component, $path);
        }
        if (is_string($value)) {
            $length = mb_strlen($value);
            if ((isset($schema['minLength']) && $length < $schema['minLength'])
                || (isset($schema['maxLength']) && $length > $schema['maxLength'])
                || (isset($schema['pattern']) && preg_match('~' . str_replace('~', '\\~', $schema['pattern']) . '~u', $value) !== 1)
            ) {
                throw new SmartPropsValidationException($component, $path);
            }
        }
        if ((is_int($value) || is_float($value))
            && ((isset($schema['minimum']) && $value < $schema['minimum'])
                || (isset($schema['maximum']) && $value > $schema['maximum']))
        ) {
            throw new SmartPropsValidationException($component, $path);
        }
        if (is_array($value) && array_is_list($value)) {
            if ((isset($schema['minItems']) && count($value) < $schema['minItems'])
                || (isset($schema['maxItems']) && count($value) > $schema['maxItems'])
            ) {
                throw new SmartPropsValidationException($component, $path);
            }
            if (is_array($schema['items'] ?? null)) {
                foreach ($value as $index => $item) {
                    $this->assertValue($component, $path . '.' . $index, $item, $schema['items']);
                }
            }
        }
        if (is_array($value) && ! array_is_list($value)) {
            $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
            foreach ($schema['required'] ?? [] as $required) {
                if (! array_key_exists($required, $value)) {
                    throw new SmartPropsValidationException($component, $path . '.' . $required);
                }
            }
            if (($schema['additionalProperties'] ?? true) === false) {
                foreach (array_keys($value) as $name) {
                    if (! array_key_exists($name, $properties)) {
                        throw new SmartPropsValidationException($component, $path . '.' . $name);
                    }
                }
            }
            foreach ($value as $name => $nested) {
                if (is_array($properties[$name] ?? null)) {
                    $this->assertValue($component, $path . '.' . $name, $nested, $properties[$name]);
                }
            }
        }
    }

    /** @param list<string> $types */
    private function matchesAnyType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            if (match ($type) {
                'null' => $value === null,
                'string' => is_string($value),
                'boolean' => is_bool($value),
                'integer' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'array' => is_array($value) && array_is_list($value),
                'object' => is_array($value) && ! array_is_list($value),
                default => false,
            }) {
                return true;
            }
        }

        return false;
    }
}
