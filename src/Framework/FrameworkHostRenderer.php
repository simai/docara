<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

final class FrameworkHostRenderer
{
    /** @param array<string, mixed> $manifest @param array<string, mixed> $props */
    public function render(array $manifest, array $props, string $runtimePair): string
    {
        $tag = $manifest['frontend']['tag'] ?? null;
        $properties = $manifest['props']['properties'] ?? null;
        if (! is_string($tag)
            || preg_match('/^sf-[a-z][a-z0-9-]*$/', $tag) !== 1
            || ! is_array($properties)
        ) {
            throw new FrameworkComponentException('FRAMEWORK_RENDER_MANIFEST_INVALID');
        }

        $attributes = [];
        if (isset($props['id']) && is_string($props['id'])) {
            $attributes['id'] = $props['id'];
        }
        $attributes['data-larena-smart-runtime'] = $runtimePair;

        foreach (array_keys($properties) as $key) {
            if (! is_string($key) || $key === 'id' || ! array_key_exists($key, $props)) {
                continue;
            }
            $value = $props[$key];
            if (is_bool($value)) {
                if ($value) {
                    $attributes[$key] = '';
                }

                continue;
            }
            if (! is_scalar($value)) {
                throw new FrameworkComponentException('FRAMEWORK_RENDER_PROP_NOT_SCALAR', $tag . ':' . $key);
            }
            $attributes[$key] = (string) $value;
        }

        $html = '<' . $tag;
        foreach ($attributes as $key => $value) {
            $html .= ' ' . $this->escape((string) $key);
            if ($value !== '') {
                $html .= '="' . $this->escape((string) $value) . '"';
            }
        }

        return $html . '></' . $tag . '>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
