<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ContentComponentRenderer
{
    public function __construct(private ContentComponentRegistry $registry = new ContentComponentRegistry) {}

    public function render(ComponentNode $node): RenderArtifact
    {
        $definition = $this->registry->definition($node->component);
        if ($this->registry->canonical($node->alias) !== $node->component) {
            throw $this->error('CONTENT_COMPONENT_ALIAS_MISMATCH', $node, 'Alias does not resolve to the requested component.');
        }

        $properties = $definition['props'];
        foreach (array_keys($node->props) as $name) {
            if (! array_key_exists($name, $properties)) {
                throw $this->error('CONTENT_COMPONENT_PROP_UNKNOWN', $node, "Unknown prop [$name].");
            }
        }
        $props = [];
        foreach ($properties as $name => $contract) {
            if (! is_array($contract) || ! is_string($contract['default'] ?? null) || ! is_array($contract['enum'] ?? null)) {
                throw $this->error('CONTENT_COMPONENT_PROP_CONTRACT_INVALID', $node, "Invalid prop contract [$name].");
            }
            $value = $node->props[$name] ?? $contract['default'];
            if (! is_string($value) || ! in_array($value, $contract['enum'], true)) {
                throw $this->error('CONTENT_COMPONENT_PROP_INVALID', $node, "Invalid value for prop [$name].");
            }
            $props[$name] = $value;
        }
        foreach (($definition['normalization'] ?? []) as $normalization) {
            if (! is_array($normalization) || ! is_array($normalization['when'] ?? null) || ! is_array($normalization['set'] ?? null)) {
                throw $this->error('CONTENT_COMPONENT_NORMALIZATION_INVALID', $node, 'Invalid normalization contract.');
            }
            if (array_intersect_assoc($normalization['when'], $props) === $normalization['when']) {
                $props = array_replace($props, $normalization['set']);
            }
        }

        $label = trim($node->label);
        if ($label === '') {
            throw $this->error('CONTENT_COMPONENT_SLOT_REQUIRED', $node, 'The default plain-text slot is required.');
        }
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $html = $this->template((string) $definition['_template'], $props, $label);

        return new RenderArtifact(
            rtrim($html, "\r\n"),
            array_values($definition['assets'] ?? []),
            [
                'runtime' => 'docara.content.smart',
                'smart' => $node->component,
                'alias' => $node->alias,
                'source' => $node->location()->toArray(),
            ],
            [
                'definition' => (string) $definition['_source'],
                'template' => (string) $definition['_template'],
                'source' => $node->location()->toArray(),
            ],
        );
    }

    /** @param array<string, string> $props */
    private function template(string $path, array $props, string $label): string
    {
        ob_start();
        try {
            require $path;

            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    private function error(string $code, ComponentNode $node, string $message): PortableConfigurationException
    {
        return new PortableConfigurationException($code, $message . " Source [{$node->location()->label()}].");
    }
}
