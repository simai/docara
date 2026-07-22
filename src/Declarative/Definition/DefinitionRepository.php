<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Definition;

use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Smart\SmartManifestValidationException;
use Simai\Docara\Smart\SmartManifestValidator;
use Simai\Docara\Smart\SmartRegistry;

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
        'section:docara.header' => [
            'path' => 'sections/docara.header.json',
            'schema' => 'declarative-section.schema.json',
        ],
        'section:docara.navigation' => [
            'path' => 'sections/docara.navigation.json',
            'schema' => 'declarative-section.schema.json',
        ],
        'section:docara.outline' => [
            'path' => 'sections/docara.outline.json',
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
        'block:shell.element' => [
            'path' => 'blocks/shell.element.json',
            'schema' => 'declarative-block.schema.json',
        ],
        'view:layout.docara.docs' => [
            'path' => 'views/layout.docara.docs.json',
            'schema' => 'declarative-view-tree.schema.json',
        ],
        'view:section.docara.article' => [
            'path' => 'views/section.docara.article.json',
            'schema' => 'declarative-view-tree.schema.json',
        ],
        'view:section.docara.shell' => [
            'path' => 'views/section.docara.shell.json',
            'schema' => 'declarative-view-tree.schema.json',
        ],
    ];

    /** @var array<string, array<string, mixed>> */
    private array $loaded = [];

    private readonly SmartRegistry $smarts;

    public function __construct(
        private readonly string $resourceRoot = __DIR__ . '/../../../resources',
        private readonly SchemaRepository $schemas = new SchemaRepository,
        ?SmartRegistry $smarts = null,
        private readonly SmartManifestValidator $manifestValidator = new SmartManifestValidator,
    ) {
        $this->smarts = $smarts ?? SmartRegistry::bundled();
    }

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
        $definition = $this->smarts->definition($smart);
        $record = $definition->views[$view] ?? null;
        if (! is_array($record)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_NOT_ALLOWED',
                "Definition [smart-view:$smart:$view] is not registered.",
            );
        }

        return $this->load(
            'smart-view:' . $this->smarts->canonicalKey($smart) . ':' . $view,
            ['path' => $record['path'], 'schema' => $record['schema']],
        );
    }

    /** @return array<string, mixed> */
    public function smartManifest(string $smart): array
    {
        $definition = $this->smarts->definition($smart);
        $manifest = $this->load(
            'smart-manifest:' . $definition->key,
            $definition->manifest,
        );
        try {
            $this->manifestValidator->assertValid($definition->key, $manifest);
        } catch (SmartManifestValidationException $exception) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SMART_MANIFEST_INVALID',
                $exception->getMessage(),
                $exception,
            );
        }

        return $manifest + ['_resolution' => $this->smarts->resolution($smart)];
    }

    /** @return array<string, mixed> */
    public function view(string $key): array
    {
        return $this->definition('view:' . $key);
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

        return $this->load($id, $record);
    }

    /**
     * @param  array{path:string,schema:?string}  $record
     * @return array<string, mixed>
     */
    private function load(string $id, array $record): array
    {
        if (isset($this->loaded[$id])) {
            return $this->loaded[$id];
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
        if (is_string($record['schema'])) {
            $this->schemas->assertValid($decoded, $record['schema']);
        }

        return $this->loaded[$id] = $decoded + [
            '_source' => $record['path'],
            '_sha256' => hash_file('sha256', $path),
        ];
    }
}
