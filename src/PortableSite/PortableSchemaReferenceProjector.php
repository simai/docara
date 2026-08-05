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
        $this->walk($this->schemas->get($file), $file, '', $scope, $records, []);
        usort($records, static fn (array $left, array $right): int => $left['path'] <=> $right['path']);

        return $records;
    }

    /**
     * @param  array<string,mixed>  $node
     * @param  list<array<string,mixed>>  $records
     * @param  array<string,true>  $stack
     */
    private function walk(array $node, string $file, string $path, string $scope, array &$records, array $stack): void
    {
        [$node, $sourceFile, $sourcePointer] = $this->resolve($node, $file);
        $identity = $sourceFile . '#' . $sourcePointer . '@' . $path;
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
            [$resolved, $propertyFile, $propertyPointer] = $this->resolve($property, $sourceFile);
            $records[] = $this->record(
                $fieldPath,
                $scope,
                isset($required[$name]),
                $resolved,
                $propertyFile,
                $propertyPointer,
            );
            $this->walk($resolved, $propertyFile, $fieldPath, $scope, $records, $stack);
        }
        foreach (($node['patternProperties'] ?? []) as $pattern => $property) {
            if (! is_string($pattern) || ! is_array($property)) {
                continue;
            }
            $fieldPath = $path . '/<' . $pattern . '>';
            [$resolved, $propertyFile, $propertyPointer] = $this->resolve($property, $sourceFile);
            $records[] = $this->record($fieldPath, $scope, false, $resolved, $propertyFile, $propertyPointer);
            $this->walk($resolved, $propertyFile, $fieldPath, $scope, $records, $stack);
        }
        if (is_array($node['additionalProperties'] ?? null)) {
            $fieldPath = $path . '/*';
            [$resolved, $propertyFile, $propertyPointer] = $this->resolve($node['additionalProperties'], $sourceFile);
            $records[] = $this->record($fieldPath, $scope, false, $resolved, $propertyFile, $propertyPointer);
            $this->walk($resolved, $propertyFile, $fieldPath, $scope, $records, $stack);
        }
        if (is_array($node['items'] ?? null)) {
            $this->walk($node['items'], $sourceFile, $path . '/*', $scope, $records, $stack);
        }
    }

    /** @param array<string,mixed> $node @return array{0:array<string,mixed>,1:string,2:string} */
    private function resolve(array $node, string $file): array
    {
        $ref = $node['$ref'] ?? null;
        if (! is_string($ref)) {
            return [$node, $file, ''];
        }
        [$targetFile, $fragment] = str_contains($ref, '#') ? explode('#', $ref, 2) : [$ref, ''];
        $targetFile = $targetFile === '' ? $file : basename($targetFile);
        $target = $this->schemas->get($targetFile);
        $pointer = $fragment === '' ? '' : $fragment;
        if ($pointer !== '') {
            foreach (explode('/', ltrim($pointer, '/')) as $segment) {
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

        return [array_replace_recursive($target, $node), $targetFile, $pointer];
    }

    /** @param array<string,mixed> $node @return array<string,mixed> */
    private function record(string $path, string $scope, bool $required, array $node, string $file, string $pointer): array
    {
        $type = $node['type'] ?? null;
        if (! is_string($type)) {
            $type = array_key_exists('const', $node) ? get_debug_type($node['const']) : (isset($node['oneOf']) ? 'oneOf' : 'any');
        }
        $validation = [];
        foreach (['const', 'enum', 'pattern', 'format', 'minimum', 'maximum', 'minLength', 'maxLength', 'minItems', 'maxItems', 'minProperties', 'maxProperties'] as $keyword) {
            if (array_key_exists($keyword, $node)) {
                $validation[] = $keyword . '=' . $this->value($node[$keyword]);
            }
        }
        if (($node['additionalProperties'] ?? null) === false) {
            $validation[] = 'additionalProperties=false';
        }

        return [
            'path' => $path,
            'scope' => $scope,
            'required' => $required,
            'type' => $type,
            'has_default' => array_key_exists('default', $node),
            'default' => $node['default'] ?? null,
            'validation' => $validation === [] ? 'schema type only' : implode('; ', $validation),
            'provenance' => 'resources/schemas/' . $file . '#' . $pointer,
        ];
    }

    private function value(mixed $value): string
    {
        return is_scalar($value) || $value === null ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null' : CanonicalJson::encode($value);
    }
}
