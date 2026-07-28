<?php

namespace Simai\Docara\Portable;

use JsonException;

final class SchemaRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $schemas = [];

    public function __construct(
        private readonly string $schemaPath = __DIR__ . '/../../resources/schemas',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $schema): array
    {
        $schema = basename($schema);

        if (! str_ends_with($schema, '.schema.json')) {
            throw new PortableConfigurationException('SCHEMA_NOT_FOUND', "Unknown schema [$schema].");
        }

        if (isset($this->schemas[$schema])) {
            return $this->schemas[$schema];
        }

        $path = rtrim($this->schemaPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $schema;

        if (! is_file($path)) {
            throw new PortableConfigurationException('SCHEMA_NOT_FOUND', "Schema [$schema] was not found.");
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'SCHEMA_INVALID',
                "Schema [$schema] is not valid JSON: {$exception->getMessage()}",
                $exception,
            );
        }

        if (! is_array($decoded)) {
            throw new PortableConfigurationException('SCHEMA_INVALID', "Schema [$schema] must be a JSON object.");
        }

        return $this->schemas[$schema] = $decoded;
    }

    public function assertValid(mixed $data, string $schema): void
    {
        (new JsonSchemaValidator($this))->assertValid($data, $schema);
    }
}
