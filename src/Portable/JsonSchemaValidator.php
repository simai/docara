<?php

namespace Simai\Docara\Portable;

final class JsonSchemaValidator
{
    public function __construct(
        private readonly SchemaRepository $schemas,
    ) {}

    public function assertValid(mixed $data, string $schema): void
    {
        $this->validate($data, $this->schemas->get($schema), '/', basename($schema));
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function validate(mixed $data, array $schema, string $pointer, string $schemaName): void
    {
        if (isset($schema['$ref'])) {
            [$referencedSchema, $referencedName] = $this->resolveReference((string) $schema['$ref'], $schemaName);
            $this->validate($data, $referencedSchema, $pointer, $referencedName);

            return;
        }

        if (array_key_exists('const', $schema) && $data !== $schema['const']) {
            $this->fail($pointer, 'must equal the supported constant value');
        }

        if (isset($schema['enum']) && ! in_array($data, $schema['enum'], true)) {
            $this->fail($pointer, 'contains an unsupported value');
        }

        if (isset($schema['type']) && ! $this->matchesType($data, $schema['type'])) {
            $expected = is_array($schema['type']) ? implode('|', $schema['type']) : (string) $schema['type'];
            $this->fail($pointer, "must be of type $expected");
        }

        if (is_string($data)) {
            if (isset($schema['minLength']) && strlen($data) < (int) $schema['minLength']) {
                $this->fail($pointer, "must contain at least {$schema['minLength']} characters");
            }

            if (isset($schema['pattern'])
                && preg_match('~' . str_replace('~', '\\~', (string) $schema['pattern']) . '~u', $data) !== 1
            ) {
                $this->fail($pointer, 'does not match the required pattern');
            }

            if (isset($schema['format'])) {
                $this->validateFormat($data, (string) $schema['format'], $pointer);
            }
        }

        if (is_int($data) || is_float($data)) {
            if (isset($schema['minimum']) && $data < $schema['minimum']) {
                $this->fail($pointer, "must be at least {$schema['minimum']}");
            }
        }

        if (! is_array($data)) {
            return;
        }

        if (($schema['type'] ?? null) === 'array') {
            if (isset($schema['minItems']) && count($data) < (int) $schema['minItems']) {
                $this->fail($pointer, "must contain at least {$schema['minItems']} items");
            }

            if (isset($schema['maxItems']) && count($data) > (int) $schema['maxItems']) {
                $this->fail($pointer, "must contain no more than {$schema['maxItems']} items");
            }

            if (($schema['uniqueItems'] ?? false) === true) {
                $encoded = array_map(CanonicalJson::encode(...), $data);

                if (count($encoded) !== count(array_unique($encoded))) {
                    $this->fail($pointer, 'must contain unique items');
                }
            }

            if (isset($schema['items']) && is_array($schema['items'])) {
                foreach ($data as $index => $item) {
                    $this->validate($item, $schema['items'], $this->child($pointer, (string) $index), $schemaName);
                }
            }

            return;
        }

        if (isset($schema['required'])) {
            foreach ($schema['required'] as $required) {
                if (! array_key_exists((string) $required, $data)) {
                    $this->fail($pointer, "is missing required property [$required]");
                }
            }
        }

        if (isset($schema['minProperties']) && count($data) < (int) $schema['minProperties']) {
            $this->fail($pointer, "must contain at least {$schema['minProperties']} properties");
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

        foreach ($data as $key => $value) {
            $key = (string) $key;

            if (isset($properties[$key]) && is_array($properties[$key])) {
                $this->validate($value, $properties[$key], $this->child($pointer, $key), $schemaName);

                continue;
            }

            if (($schema['additionalProperties'] ?? true) === false) {
                $this->fail($this->child($pointer, $key), 'is not an allowed property');
            }

            if (is_array($schema['additionalProperties'] ?? null)) {
                $this->validate(
                    $value,
                    $schema['additionalProperties'],
                    $this->child($pointer, $key),
                    $schemaName,
                );
            }
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function resolveReference(string $reference, string $currentSchema): array
    {
        [$schemaName, $fragment] = array_pad(explode('#', $reference, 2), 2, '');
        $schemaName = $schemaName === '' ? $currentSchema : basename($schemaName);
        $schema = $this->schemas->get($schemaName);

        if ($fragment !== '') {
            if (! str_starts_with($fragment, '/')) {
                throw new PortableConfigurationException('SCHEMA_INVALID', "Unsupported schema reference [$reference].");
            }

            foreach (explode('/', ltrim($fragment, '/')) as $part) {
                $part = str_replace(['~1', '~0'], ['/', '~'], $part);

                if (! is_array($schema) || ! array_key_exists($part, $schema)) {
                    throw new PortableConfigurationException('SCHEMA_INVALID', "Unresolvable schema reference [$reference].");
                }

                $schema = $schema[$part];
            }
        }

        if (! is_array($schema)) {
            throw new PortableConfigurationException('SCHEMA_INVALID', "Schema reference [$reference] is not an object.");
        }

        return [$schema, $schemaName];
    }

    /**
     * @param  string|list<string>  $type
     */
    private function matchesType(mixed $data, string|array $type): bool
    {
        if (is_array($type)) {
            foreach ($type as $candidate) {
                if ($this->matchesType($data, $candidate)) {
                    return true;
                }
            }

            return false;
        }

        return match ($type) {
            'object' => is_array($data) && (! array_is_list($data) || $data === []),
            'array' => is_array($data) && array_is_list($data),
            'string' => is_string($data),
            'integer' => is_int($data),
            'number' => is_int($data) || is_float($data),
            'boolean' => is_bool($data),
            'null' => $data === null,
            default => throw new PortableConfigurationException('SCHEMA_INVALID', "Unsupported JSON Schema type [$type]."),
        };
    }

    private function validateFormat(string $value, string $format, string $pointer): void
    {
        $valid = match ($format) {
            'docara-relative-path' => $this->isRelativePath($value),
            'docara-relative-directory' => $this->isRelativeDirectory($value),
            'docara-immutable-revision' => preg_match('/^[a-f0-9]{40}$/', $value) === 1,
            default => throw new PortableConfigurationException('SCHEMA_INVALID', "Unsupported JSON Schema format [$format]."),
        };

        if (! $valid) {
            $this->fail($pointer, "must satisfy format [$format]");
        }
    }

    private function isRelativePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function isRelativeDirectory(string $path): bool
    {
        return str_ends_with($path, '/') && $this->isRelativePath(rtrim($path, '/'));
    }

    private function child(string $pointer, string $key): string
    {
        $escaped = str_replace(['~', '/'], ['~0', '~1'], $key);

        return rtrim($pointer, '/') . '/' . $escaped;
    }

    private function fail(string $pointer, string $message): never
    {
        throw new PortableConfigurationException(
            'SCHEMA_VALIDATION_FAILED',
            "JSON value at [$pointer] $message.",
        );
    }
}
