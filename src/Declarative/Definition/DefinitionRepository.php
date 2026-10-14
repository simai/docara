<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Definition;

use Simai\Docara\Declarative\Binding\BindingRegistry;
use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Design\Provider\BuiltinDesignProvider;
use Simai\Docara\Design\Registry\DesignRegistry;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Smart\SmartManifestValidationException;
use Simai\Docara\Smart\SmartManifestValidator;
use Simai\Docara\Smart\SmartRegistry;

final class DefinitionRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $loaded = [];

    private readonly SmartRegistry $smarts;

    private readonly DesignRegistry $designs;

    private readonly BindingRegistry $bindings;

    public function __construct(
        private readonly string $resourceRoot = __DIR__ . '/../../../resources',
        private readonly SchemaRepository $schemas = new SchemaRepository,
        ?SmartRegistry $smarts = null,
        private readonly SmartManifestValidator $manifestValidator = new SmartManifestValidator,
        ?DesignRegistry $designs = null,
        ?BindingRegistry $bindings = null,
    ) {
        $this->smarts = $smarts ?? SmartRegistry::bundled();
        $this->designs = $designs ?? new DesignRegistry([
            new BuiltinDesignProvider($this->resourceRoot),
        ], $this->schemas);
        $this->bindings = $bindings ?? BindingRegistry::bundled();
    }

    /** @return array<string, mixed> */
    public function layout(string $key): array
    {
        return $this->definition('layout:' . $key);
    }

    /** @return array<string, mixed> */
    public function defaultLayout(): array
    {
        return $this->layout($this->designs->defaultLayout()->id);
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
            $definition->root,
        );
    }

    /** @return array<string, mixed> */
    public function smartManifest(string $smart): array
    {
        $definition = $this->smarts->definition($smart);
        $manifest = $this->load(
            'smart-manifest:' . $definition->key,
            $definition->manifest,
            $definition->root,
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

    public function assertSmartRegistered(string $smart): void
    {
        $this->smarts->definition($smart);
    }

    public function bindings(): BindingRegistry
    {
        return $this->bindings;
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
        [$kind, $key] = explode(':', $id, 2) + [null, null];
        $artifactKind = match ($kind) {
            'layout' => DesignArtifactKind::Layout,
            'view' => DesignArtifactKind::View,
            'section' => DesignArtifactKind::Section,
            'block' => DesignArtifactKind::Block,
            default => throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_NOT_ALLOWED',
                "Definition [$id] is not registered.",
            ),
        };
        $descriptor = $this->designs->get($artifactKind, (string) $key);

        return $this->loaded[$id] = $descriptor->definition + [
            '_source' => $descriptor->relativePath,
            '_sha256' => $descriptor->sha256,
            '_design' => $descriptor->provenance(),
        ];
    }

    /**
     * @param  array{path:string,schema:?string}  $record
     * @return array<string, mixed>
     */
    private function load(string $id, array $record, ?string $root = null): array
    {
        if (isset($this->loaded[$id])) {
            return $this->loaded[$id];
        }
        $path = rtrim($root ?? $this->resourceRoot, DIRECTORY_SEPARATOR)
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
