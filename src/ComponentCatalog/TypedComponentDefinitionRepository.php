<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class TypedComponentDefinitionRepository
{
    /** @var null|list<array<string, mixed>> */
    private ?array $definitions = null;

    public function __construct(
        private readonly string $resourceRoot,
        private readonly SchemaRepository $schemas = new SchemaRepository,
    ) {}

    public static function bundled(): self
    {
        return new self(dirname(__DIR__, 2) . '/resources/component-catalog/typed');
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        if (is_link($this->resourceRoot) || ! is_dir($this->resourceRoot)) {
            throw new PortableConfigurationException(
                'TYPED_COMPONENT_SOURCE_INVALID',
                'The bundled typed-component source is missing or unsafe.',
            );
        }

        $paths = glob($this->resourceRoot . '/*.json', GLOB_NOSORT);
        if (! is_array($paths) || $paths === []) {
            throw new PortableConfigurationException(
                'TYPED_COMPONENT_SOURCE_INVALID',
                'The bundled typed-component source is empty.',
            );
        }
        sort($paths, SORT_STRING);

        $definitions = [];
        $ids = [];
        $names = [];
        foreach ($paths as $path) {
            if (is_link($path) || ! is_file($path)) {
                throw new PortableConfigurationException(
                    'TYPED_COMPONENT_SOURCE_INVALID',
                    'A typed-component definition is missing or unsafe.',
                );
            }
            try {
                $definition = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'TYPED_COMPONENT_DEFINITION_INVALID',
                    'A typed-component definition is not valid JSON.',
                    $exception,
                );
            }
            if (! is_array($definition)) {
                throw new PortableConfigurationException(
                    'TYPED_COMPONENT_DEFINITION_INVALID',
                    'A typed-component definition must be an object.',
                );
            }
            $this->schemas->assertValid($definition, 'typed-component-definition.schema.json');

            $id = (string) ($definition['id'] ?? '');
            $name = (string) ($definition['name'] ?? '');
            if (isset($ids[$id]) || isset($names[$name])) {
                throw new PortableConfigurationException(
                    'TYPED_COMPONENT_DUPLICATE',
                    'Typed-component IDs and directive names must be unique.',
                );
            }
            $renderer = TypedRendererId::tryFrom((string) ($definition['renderer'] ?? ''));
            if (! $renderer instanceof TypedRendererId) {
                throw new PortableConfigurationException(
                    'TYPED_COMPONENT_RENDERER_UNKNOWN',
                    'A typed-component definition names an unsupported renderer.',
                );
            }
            $expectedReference = 'resources/component-catalog/typed/' . basename($path);
            if ($id !== $renderer->componentId()
                || $name !== $renderer->directiveName()
                || ($definition['authoring']['syntax'] ?? null) !== 'directive'
                || ($definition['authoring']['call'] ?? null) !== ':::' . $name
                || ($definition['provenance']['source_kind'] ?? null) !== 'typed_definition'
                || ($definition['provenance']['definition_ref'] ?? null) !== $expectedReference
            ) {
                throw new PortableConfigurationException(
                    'TYPED_COMPONENT_DEFINITION_MISMATCH',
                    "Typed-component definition [$id] does not match its executable renderer and directive contract.",
                );
            }
            $ids[$id] = true;
            $names[$name] = true;
            $definitions[] = $definition;
        }

        usort(
            $definitions,
            static fn (array $left, array $right): int => (string) $left['id'] <=> (string) $right['id'],
        );

        return $this->definitions = $definitions;
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = array_map(
            static fn (array $definition): string => (string) $definition['name'],
            $this->all(),
        );
        sort($names, SORT_STRING);

        return $names;
    }

    /** @return array<string, mixed> */
    public function byName(string $name): array
    {
        $definition = $this->findByName($name);
        if ($definition === null) {
            throw new PortableConfigurationException(
                'TYPED_COMPONENT_UNSUPPORTED',
                "Typed component [$name] is not admitted.",
            );
        }

        return $definition;
    }

    /** @return null|array<string, mixed> */
    public function findByName(string $name): ?array
    {
        foreach ($this->all() as $definition) {
            if (($definition['name'] ?? null) === $name) {
                return $definition;
            }
        }

        return null;
    }
}
