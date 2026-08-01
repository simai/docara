<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use League\CommonMark\Util\RegexHelper;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ContentComponentRenderer
{
    public function __construct(private ContentComponentRegistry $registry = new ContentComponentRegistry) {}

    public function render(ComponentNode $node): RenderArtifact
    {
        $definition = $this->registry->definition($node->component);
        $props = $this->props($node, $definition);
        $slot = $definition['slots']['default'] ?? null;
        if (! is_array($slot) || ($slot['required'] ?? null) !== true || ($slot['kind'] ?? null) !== 'plain_text') {
            throw $this->error('CONTENT_COMPONENT_SLOT_CONTRACT_INVALID', $node, 'The default plain-text slot contract is invalid.');
        }

        $label = trim($node->label);
        if ($label === '') {
            throw $this->error('CONTENT_COMPONENT_SLOT_REQUIRED', $node, 'The default plain-text slot is required.');
        }
        if (($slot['pattern'] ?? null) === 'identifier'
            && preg_match('/^[a-z][a-z0-9_]*$/D', $label) !== 1
        ) {
            throw $this->error('CONTENT_COMPONENT_SLOT_INVALID', $node, 'The default plain-text slot must be an identifier.');
        }
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $html = $this->template((string) $definition['_template'], $props, label: $label);

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

    public function renderBlock(ComponentBlockNode $node, string $bodyHtml): RenderArtifact
    {
        $definition = $this->registry->definition($node->component);
        $props = $this->props($node, $definition);
        $slot = $definition['slots']['default'] ?? null;
        if (! is_array($slot) || ($slot['required'] ?? null) !== true || ($slot['kind'] ?? null) !== 'document') {
            throw $this->error('CONTENT_COMPONENT_SLOT_CONTRACT_INVALID', $node, 'The default document slot contract is invalid.');
        }
        $children = $node->children();
        $heading = $children[0] ?? null;
        $levels = $slot['heading_levels'] ?? [];
        if (! $heading instanceof SourceNode
            || $heading->type() !== 'heading'
            || ! is_array($levels)
            || ! in_array($heading->data['level'] ?? null, $levels, true)
        ) {
            throw $this->error(
                'CONTENT_COMPONENT_BLOCK_HEADING_REQUIRED',
                $node,
                'The document slot must start with an allowed heading.',
            );
        }
        $title = trim((string) ($heading->data['text'] ?? ''));
        $content = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/su', '', trim($bodyHtml), 1) ?? '';
        if ($title === '' || count($children) < 2 || trim(strip_tags($content)) === '') {
            throw $this->error(
                'CONTENT_COMPONENT_BLOCK_CONTENT_REQUIRED',
                $node,
                'The document slot requires a visible heading and supporting content.',
            );
        }
        $title = htmlspecialchars(
            preg_replace('/[*_`~]+/u', '', $title) ?? $title,
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
            'UTF-8',
        );
        $html = $this->template(
            (string) $definition['_template'],
            $props,
            title: $title,
            content: $content,
        );

        return new RenderArtifact(
            rtrim($html, "\r\n"),
            array_values($definition['assets'] ?? []),
            [
                'runtime' => 'docara.content.smart',
                'smart' => $node->component,
                'alias' => $node->alias,
                'node_type' => $node->type(),
                'source' => $node->location()->toArray(),
            ],
            [
                'definition' => (string) $definition['_source'],
                'template' => (string) $definition['_template'],
                'source' => $node->location()->toArray(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, string>
     */
    private function props(ComponentNode|ComponentBlockNode $node, array $definition): array
    {
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
            if (! is_array($contract) || ! is_string($contract['default'] ?? null)) {
                throw $this->error('CONTENT_COMPONENT_PROP_CONTRACT_INVALID', $node, "Invalid prop contract [$name].");
            }
            $value = $node->props[$name] ?? $contract['default'];
            $kind = (string) ($contract['kind'] ?? 'enum');
            if (! is_string($value) || ! $this->validProp($kind, $value, $contract)) {
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
        foreach (($definition['invalid_combinations'] ?? []) as $combination) {
            if (! is_array($combination)) {
                throw $this->error('CONTENT_COMPONENT_PROP_CONTRACT_INVALID', $node, 'Invalid prop combination contract.');
            }
            if (array_intersect_assoc($combination, $props) === $combination) {
                throw $this->error('CONTENT_COMPONENT_PROP_COMBINATION_INVALID', $node, 'Invalid prop combination.');
            }
        }

        return $props;
    }

    /** @param array<string, mixed> $contract */
    private function validProp(string $kind, string $value, array $contract): bool
    {
        if (($contract['required'] ?? false) === true && trim($value) === '') {
            return false;
        }

        return match ($kind) {
            'enum' => is_array($contract['enum'] ?? null) && in_array($value, $contract['enum'], true),
            'string' => true,
            'identifier' => $value === '' || preg_match('/^[a-z][a-z0-9_]*$/D', $value) === 1,
            'safe_url' => $value !== '' && preg_match(RegexHelper::REGEX_UNSAFE_PROTOCOL, $value) !== 1,
            default => false,
        };
    }

    /** @param array<string, string> $props */
    private function template(
        string $path,
        array $props,
        string $label = '',
        string $title = '',
        string $content = '',
    ): string {
        ob_start();
        try {
            require $path;

            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    private function error(
        string $code,
        ComponentNode|ComponentBlockNode $node,
        string $message,
    ): PortableConfigurationException {
        return new PortableConfigurationException($code, $message . " Source [{$node->location()->label()}].");
    }
}
