<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime;

use Simai\Docara\Portable\PortableConfigurationException;

final class PortableSmartPropsValidator
{
    /** @param array<string,mixed> $manifest @param array<string,mixed> $props */
    public function validate(string $smart, array $manifest, array $props): void
    {
        $schema = is_array($manifest['props'] ?? null) ? $manifest['props'] : [];
        foreach ($props as $name => $value) {
            if (! is_string($name) || ! isset($schema[$name]) || ! is_array($schema[$name])) {
                $this->fail($smart, 'props.' . (string) $name);
            }
            $this->value($smart, $name, $schema[$name], $value);
        }
        foreach ($schema as $name => $definition) {
            if (is_array($definition) && ($definition['required'] ?? false) === true && ! array_key_exists((string) $name, $props)) {
                $this->fail($smart, 'props.' . $name . '.required');
            }
        }
    }

    /** @param array<string,mixed> $definition */
    private function value(string $smart, string $name, array $definition, mixed $value): void
    {
        $valid = match ($definition['type'] ?? null) {
            'string', 'content', 'asset', 'link' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'object' => is_array($value) && ! array_is_list($value),
            'array' => is_array($value) && array_is_list($value),
            'enum' => in_array($value, is_array($definition['values'] ?? null) ? $definition['values'] : [], true),
            default => false,
        };
        if (! $valid) {
            $this->fail($smart, 'props.' . $name . '.type');
        }
    }

    private function fail(string $smart, string $field): never
    {
        throw new PortableConfigurationException(
            'PORTABLE_SMART_PROPS_INVALID',
            "Portable Smart [$smart] has invalid [$field].",
        );
    }
}
