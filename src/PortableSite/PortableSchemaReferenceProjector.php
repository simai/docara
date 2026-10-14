<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class PortableSchemaReferenceProjector
{
    /** @var array<string,string> */
    private const SCHEMAS = [
        'site' => 'site.schema.json',
        'section' => 'section.schema.json',
        'page' => 'page.schema.json',
        'presentation' => 'presentation.schema.json',
        'framework-lock' => 'framework-lock.schema.json',
    ];

    public function __construct(private readonly SchemaRepository $schemas = new SchemaRepository) {}

    /** @return array{schema:string,content_sha256:string,sources:array<string,string>} */
    public function receipt(): array
    {
        $sources = [];
        foreach (self::SCHEMAS as $name => $file) {
            $sources[$name] = hash('sha256', CanonicalJson::encode($this->schemas->get($file)));
        }

        return [
            'schema' => 'docara.public_schema_reference.v1',
            'content_sha256' => hash('sha256', CanonicalJson::encode($sources)),
            'sources' => $sources,
        ];
    }

    /** @return list<array<string,mixed>> */
    public function project(string $name, string $scope): array
    {
        $file = self::SCHEMAS[$name] ?? null;
        if ($file === null) {
            throw new PortableConfigurationException('PUBLIC_SCHEMA_REFERENCE_UNKNOWN', "Unknown public schema [$name].");
        }
        $records = [];
        $schema = $this->schemas->get($file);
        $this->walk($schema, $file, '', '', $scope, $records, []);
        if (($schema['properties'] ?? []) === [] && is_array($schema['$defs'] ?? null)) {
            foreach ($this->publicDefinitions($file) as $definition) {
                $node = $schema['$defs'][$definition] ?? null;
                if (! is_array($node)) {
                    continue;
                }
                $path = '/' . $definition;
                $pointer = '/$defs/' . $this->pointerSegment($definition);
                [$resolved, $sourceFile, $sourcePointer] = $this->resolve($node, $file, $pointer);
                $records[$path . '@' . $sourceFile . '#' . $sourcePointer] = $this->record(
                    $path,
                    $scope,
                    false,
                    $resolved,
                    $sourceFile,
                    $sourcePointer,
                );
                $this->walk($resolved, $sourceFile, $sourcePointer, $path, $scope, $records, []);
            }
        }
        $records = array_values($records);
        usort($records, static fn (array $left, array $right): int => $left['path'] <=> $right['path']);

        return $records;
    }

    /**
     * @param  array<string,mixed>  $node
     * @param  array<string,array<string,mixed>>  $records
     * @param  array<string,true>  $stack
     */
    private function walk(array $node, string $file, string $pointer, string $path, string $scope, array &$records, array $stack): void
    {
        [$node, $sourceFile, $sourcePointer] = $this->resolve($node, $file, $pointer);
        $identity = $sourceFile . '#' . $sourcePointer;
        if (isset($stack[$identity])) {
            return;
        }
        $stack[$identity] = true;
        $required = array_fill_keys(array_values(array_filter($node['required'] ?? [], 'is_string')), true);
        foreach (($node['properties'] ?? []) as $name => $property) {
            if (! is_string($name) || ! is_array($property)) {
                continue;
            }
            $fieldPath = $path . '/' . $name;
            $propertyPointer = $sourcePointer . '/properties/' . $this->pointerSegment($name);
            [$resolved, $propertyFile, $propertyPointer] = $this->resolve($property, $sourceFile, $propertyPointer);
            $records[$fieldPath . '@' . $propertyFile . '#' . $propertyPointer] = $this->record(
                $fieldPath,
                $scope,
                isset($required[$name]),
                $resolved,
                $propertyFile,
                $propertyPointer,
            );
            $this->walk($resolved, $propertyFile, $propertyPointer, $fieldPath, $scope, $records, $stack);
        }
        foreach (($node['patternProperties'] ?? []) as $pattern => $property) {
            if (! is_string($pattern) || ! is_array($property)) {
                continue;
            }
            $fieldPath = $path . '/<' . $pattern . '>';
            $propertyPointer = $sourcePointer . '/patternProperties/' . $this->pointerSegment($pattern);
            [$resolved, $propertyFile, $propertyPointer] = $this->resolve($property, $sourceFile, $propertyPointer);
            $records[$fieldPath . '@' . $propertyFile . '#' . $propertyPointer] = $this->record($fieldPath, $scope, false, $resolved, $propertyFile, $propertyPointer);
            $this->walk($resolved, $propertyFile, $propertyPointer, $fieldPath, $scope, $records, $stack);
        }
        if (is_array($node['additionalProperties'] ?? null)) {
            $fieldPath = $path . '/*';
            $propertyPointer = $sourcePointer . '/additionalProperties';
            [$resolved, $propertyFile, $propertyPointer] = $this->resolve($node['additionalProperties'], $sourceFile, $propertyPointer);
            $records[$fieldPath . '@' . $propertyFile . '#' . $propertyPointer] = $this->record($fieldPath, $scope, false, $resolved, $propertyFile, $propertyPointer);
            $this->walk($resolved, $propertyFile, $propertyPointer, $fieldPath, $scope, $records, $stack);
        }
        if (is_array($node['items'] ?? null)) {
            $itemPath = $path . '/*';
            $itemPointer = $sourcePointer . '/items';
            [$resolved, $itemFile, $itemPointer] = $this->resolve($node['items'], $sourceFile, $itemPointer);
            $records[$itemPath . '@' . $itemFile . '#' . $itemPointer] ??= $this->record($itemPath, $scope, false, $resolved, $itemFile, $itemPointer);
            $this->walk($resolved, $itemFile, $itemPointer, $itemPath, $scope, $records, $stack);
        }
        foreach (['allOf', 'anyOf', 'oneOf'] as $keyword) {
            foreach (($node[$keyword] ?? []) as $index => $branch) {
                if (is_array($branch)) {
                    $this->walk($branch, $sourceFile, $sourcePointer . '/' . $keyword . '/' . $index, $path, $scope, $records, $stack);
                }
            }
        }
        foreach (['if', 'then', 'else'] as $keyword) {
            if (is_array($node[$keyword] ?? null)) {
                $this->walk($node[$keyword], $sourceFile, $sourcePointer . '/' . $keyword, $path, $scope, $records, $stack);
            }
        }
    }

    /** @param array<string,mixed> $node @return array{0:array<string,mixed>,1:string,2:string} */
    private function resolve(array $node, string $file, string $pointer): array
    {
        $ref = $node['$ref'] ?? null;
        if (! is_string($ref)) {
            return [$node, $file, $pointer];
        }
        [$targetFile, $fragment] = str_contains($ref, '#') ? explode('#', $ref, 2) : [$ref, ''];
        $targetFile = $targetFile === '' ? $file : basename($targetFile);
        $target = $this->schemas->get($targetFile);
        $targetPointer = $fragment === '' ? '' : $fragment;
        if ($targetPointer !== '') {
            foreach (explode('/', ltrim($targetPointer, '/')) as $segment) {
                $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
                if (! is_array($target) || ! array_key_exists($segment, $target)) {
                    throw new PortableConfigurationException('PUBLIC_SCHEMA_REFERENCE_INVALID', "Unresolvable schema reference [$ref].");
                }
                $target = $target[$segment];
            }
        }
        if (! is_array($target)) {
            throw new PortableConfigurationException('PUBLIC_SCHEMA_REFERENCE_INVALID', "Schema reference [$ref] is not an object.");
        }
        unset($node['$ref']);

        return [array_replace_recursive($target, $node), $targetFile, $targetPointer];
    }

    /** @param array<string,mixed> $node @return array<string,mixed> */
    private function record(string $path, string $scope, bool $required, array $node, string $file, string $pointer): array
    {
        $type = $node['type'] ?? null;
        if (! is_string($type)) {
            $type = array_key_exists('const', $node) ? get_debug_type($node['const']) : (isset($node['oneOf']) ? 'oneOf' : 'any');
        }
        $validation = [];
        foreach (['const', 'enum', 'pattern', 'format', 'minimum', 'maximum', 'minLength', 'maxLength', 'minItems', 'maxItems', 'minProperties', 'maxProperties', 'uniqueItems', 'propertyNames', 'dependentRequired'] as $keyword) {
            if (array_key_exists($keyword, $node)) {
                $validation[] = $keyword . '=' . $this->value($node[$keyword]);
            }
        }
        if (($node['additionalProperties'] ?? null) === false) {
            $validation[] = 'additionalProperties=false';
        }
        foreach (['allOf', 'anyOf', 'oneOf'] as $keyword) {
            if (is_array($node[$keyword] ?? null)) {
                $validation[] = $keyword . '=' . count($node[$keyword]);
            }
        }
        $conditionals = array_values(array_filter(
            ['if', 'then', 'else'],
            static fn (string $keyword): bool => is_array($node[$keyword] ?? null),
        ));
        if ($conditionals !== []) {
            $validation[] = 'conditional=' . implode('+', $conditionals);
        }

        return [
            'path' => $path,
            'scope' => $scope,
            'required' => $required,
            'type' => $type,
            'has_default' => array_key_exists('default', $node),
            'default' => $node['default'] ?? null,
            'validation' => $validation === [] ? 'schema type only' : implode('; ', $validation),
            'provenance' => 'resources/schemas/' . $file . '#' . ($pointer === '' ? '/' : $pointer),
        ];
    }

    private function pointerSegment(string $value): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $value);
    }

    private function value(mixed $value): string
    {
        if (! is_scalar($value) && $value !== null) {
            return CanonicalJson::encode($value);
        }
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : 'null';
    }

    /** @return list<string> */
    private function publicDefinitions(string $file): array
    {
        $definitions = [];
        foreach (self::SCHEMAS as $candidate) {
            if ($candidate === $file) {
                continue;
            }
            $this->collectDefinitionReferences($this->schemas->get($candidate), $file, $definitions);
        }
        $definitions = array_keys($definitions);
        sort($definitions, SORT_STRING);

        return $definitions;
    }

    /** @param array<string,mixed> $node @param array<string,true> $definitions */
    private function collectDefinitionReferences(array $node, string $file, array &$definitions): void
    {
        $prefix = $file . '#/$defs/';
        $reference = $node['$ref'] ?? null;
        if (is_string($reference) && str_starts_with($reference, $prefix)) {
            $definition = substr($reference, strlen($prefix));
            if ($definition !== '' && ! str_contains($definition, '/')) {
                $definitions[str_replace(['~1', '~0'], ['/', '~'], $definition)] = true;
            }
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectDefinitionReferences($value, $file, $definitions);
            }
        }
    }
}
