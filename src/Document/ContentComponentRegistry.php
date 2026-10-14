<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use JsonException;
use Simai\Docara\Content\SourceBoundaryValidator;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ContentComponentRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $definitions;

    /** @var array<string, string> */
    private array $aliases;

    public function __construct(?string $root = null)
    {
        $root ??= __DIR__ . '/../../resources/components';
        $definitions = [];
        $aliases = [];
        foreach (glob(rtrim($root, '/\\') . '/*.json') ?: [] as $path) {
            try {
                $definition = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'CONTENT_COMPONENT_DEFINITION_INVALID',
                    "Content component definition [$path] is not valid JSON.",
                    $exception,
                );
            }
            if (! is_array($definition)
                || ($definition['schema'] ?? null) !== 'docara.content_component.v1'
                || ! is_string($definition['id'] ?? null)
                || ! is_array($definition['aliases'] ?? null)
                || ! is_array($definition['props'] ?? null)
                || ! is_array($definition['slots'] ?? null)
                || ! is_string($definition['template'] ?? null)
                || ($definition['renderer'] ?? null) !== 'docara.content.template'
            ) {
                throw new PortableConfigurationException(
                    'CONTENT_COMPONENT_DEFINITION_INVALID',
                    "Content component definition [$path] has an invalid structural contract.",
                );
            }
            (new SourceBoundaryValidator)->assertComponentManifest($definition);
            $id = $definition['id'];
            $template = realpath(rtrim($root, '/\\') . '/' . $definition['template']);
            $resolvedRoot = realpath($root);
            if ($template === false || $resolvedRoot === false
                || ! str_starts_with($template, rtrim($resolvedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
            ) {
                throw new PortableConfigurationException(
                    'CONTENT_COMPONENT_TEMPLATE_INVALID',
                    "Content component [$id] has an unsafe template.",
                );
            }
            $definition['_source'] = $path;
            $definition['_template'] = $template;
            $definitions[$id] = $definition;
            foreach ($definition['aliases'] as $alias) {
                if (! is_string($alias) || isset($aliases[$alias])) {
                    throw new PortableConfigurationException(
                        'CONTENT_COMPONENT_ALIAS_INVALID',
                        "Content component [$id] has an invalid or duplicate alias.",
                    );
                }
                $aliases[$alias] = $id;
            }
        }
        if ($definitions === []) {
            throw new PortableConfigurationException(
                'CONTENT_COMPONENT_REGISTRY_EMPTY',
                'The content component registry is empty.',
            );
        }
        $this->definitions = $definitions;
        $this->aliases = $aliases;
    }

    /** @return array<string, mixed> */
    public function definition(string $id): array
    {
        return $this->definitions[$id] ?? throw new PortableConfigurationException(
            'CONTENT_COMPONENT_UNKNOWN',
            "Content component [$id] is not registered.",
        );
    }

    public function canonical(string $alias): string
    {
        return $this->aliases[$alias] ?? $alias;
    }

    /** @return array<string, string> */
    public function aliases(): array
    {
        return $this->aliases;
    }

    /** @return array<string, string> */
    public function inlineAliases(): array
    {
        return array_filter(
            $this->aliases,
            fn (string $id): bool => ($this->definitions[$id]['slots']['default']['kind'] ?? null) === 'plain_text',
        );
    }
}
