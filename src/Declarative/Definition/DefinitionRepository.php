<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Definition;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class DefinitionRepository
{
    /** @var array<string, array{path: string, schema: string}> */
    private const DEFINITIONS = [
        'layout:docara.docs' => [
            'path' => 'layouts/docara.docs.json',
            'schema' => 'declarative-layout.schema.json',
        ],
        'section:docara.article' => [
            'path' => 'sections/docara.article.json',
            'schema' => 'declarative-section.schema.json',
        ],
        'section:docara.shell' => [
            'path' => 'sections/docara.shell.json',
            'schema' => 'declarative-section.schema.json',
        ],
        'block:content.markdown' => [
            'path' => 'blocks/content.markdown.json',
            'schema' => 'declarative-block.schema.json',
        ],
        'block:content.smart' => [
            'path' => 'blocks/content.smart.json',
            'schema' => 'declarative-block.schema.json',
        ],
        'block:shell.smart' => [
            'path' => 'blocks/shell.smart.json',
            'schema' => 'declarative-block.schema.json',
        ],
        'smart-view:ui.alert:default' => [
            'path' => 'smart/ui.alert/views/default.json',
            'schema' => 'declarative-smart-view.schema.json',
        ],
        'smart-manifest:docara.header' => [
            'path' => 'smart/docara.header/manifest.json',
            'schema' => 'declarative-smart-manifest.schema.json',
        ],
        'smart-view:docara.header:default' => [
            'path' => 'smart/docara.header/views/default.json',
            'schema' => 'declarative-smart-view.schema.json',
        ],
        'smart-manifest:docara.navigation' => [
            'path' => 'smart/docara.navigation/manifest.json',
            'schema' => 'declarative-smart-manifest.schema.json',
        ],
        'smart-view:docara.navigation:default' => [
            'path' => 'smart/docara.navigation/views/default.json',
            'schema' => 'declarative-smart-view.schema.json',
        ],
        'smart-manifest:docara.outline' => [
            'path' => 'smart/docara.outline/manifest.json',
            'schema' => 'declarative-smart-manifest.schema.json',
        ],
        'smart-view:docara.outline:default' => [
            'path' => 'smart/docara.outline/views/default.json',
            'schema' => 'declarative-smart-view.schema.json',
        ],
    ];

    /** @var array<string, array<string, mixed>> */
    private array $loaded = [];

    public function __construct(
        private readonly string $resourceRoot = __DIR__ . '/../../../resources',
        private readonly SchemaRepository $schemas = new SchemaRepository,
    ) {}

    /** @return array<string, mixed> */
    public function layout(string $key): array
    {
        return $this->definition('layout:' . $key);
    }

    /** @return array<string, mixed> */
    public function section(string $key): array
    {
        return $this->definition('section:' . $key);
    }

    /** @return array<string, mixed> */
    public function block(string $key): array
    {
        return $this->definition('block:' . $key);
    }

    /** @return array<string, mixed> */
    public function smartView(string $smart, string $view): array
    {
        return $this->definition('smart-view:' . $smart . ':' . $view);
    }

    /** @return array<string, mixed> */
    public function smartManifest(string $smart): array
    {
        return $this->definition('smart-manifest:' . $smart);
    }

    /** @return array<string, mixed> */
    private function definition(string $id): array
    {
        if (isset($this->loaded[$id])) {
            return $this->loaded[$id];
        }
        $record = self::DEFINITIONS[$id] ?? null;
        if (! is_array($record)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_NOT_ALLOWED',
                "Definition [$id] is not registered.",
            );
        }
        $path = rtrim($this->resourceRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $record['path'];
        if (! is_file($path) || is_link($path)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_MISSING',
                "Registered definition [$id] is missing.",
            );
        }
        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_INVALID',
                "Definition [$id] is not valid JSON.",
                $exception,
            );
        }
        if (! is_array($decoded)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_INVALID',
                "Definition [$id] must be an object.",
            );
        }
        $this->schemas->assertValid($decoded, $record['schema']);

        return $this->loaded[$id] = $decoded + [
            '_source' => $record['path'],
            '_sha256' => hash_file('sha256', $path),
        ];
    }
}
