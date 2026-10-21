<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Field descriptions for the settings reference in one language.
 *
 * The JSON Schemas keep one English `description` (what editors show); the
 * text for another language lives in resources/schema-descriptions/<lang>.json,
 * keyed by the field's schema pointer ("site.schema.json#/properties/title"),
 * next to the schemas it describes. A pointer names the definition, so one
 * text serves docara.json, section.json and page.json where they share a
 * field. A language without its own file falls back to the schema text.
 */
final class PortableSchemaDescriptions
{
    /** @var array<string, array{fields:array<string,string>,derived_defaults:array<string,string>}> */
    private static array $catalogues = [];

    public function __construct(
        private readonly string $directory = __DIR__ . '/../../resources/schema-descriptions',
    ) {}

    /** @param array<string,mixed> $record a PortableSchemaReferenceProjector record */
    public function description(array $record, string $locale): ?string
    {
        $own = $this->catalogue($locale)['fields'][$this->key($record)] ?? null;
        if (is_string($own)) {
            return $own;
        }

        return is_string($record['description'] ?? null) ? $record['description'] : null;
    }

    /**
     * The fallback that Docara computes rather than declares, such as
     * "the page's first heading", in the page language.
     *
     * @param  array<string,mixed>  $record
     */
    public function derivedDefault(array $record, string $locale): ?string
    {
        $text = $this->catalogue($locale)['derived_defaults'][$this->key($record)] ?? null;

        return is_string($text) ? $text : null;
    }

    /** @return array{fields:array<string,string>,derived_defaults:array<string,string>} */
    public function catalogue(string $locale): array
    {
        $language = strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);
        if (isset(self::$catalogues[$this->directory . '#' . $language])) {
            return self::$catalogues[$this->directory . '#' . $language];
        }
        $empty = ['fields' => [], 'derived_defaults' => []];
        $path = $this->directory . '/' . $language . '.json';
        if (preg_match('/^[a-z]{2,8}$/D', $language) !== 1 || ! is_file($path)) {
            return self::$catalogues[$this->directory . '#' . $language] = $empty;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)
            || ($decoded['schema'] ?? null) !== 'docara.schema_descriptions.v1'
            || ! is_array($decoded['fields'] ?? null)
            || ! is_array($decoded['derived_defaults'] ?? null)
        ) {
            throw new PortableConfigurationException(
                'PUBLIC_SCHEMA_DESCRIPTIONS_INVALID',
                "Schema descriptions [$language] are not a docara.schema_descriptions.v1 document.",
            );
        }

        return self::$catalogues[$this->directory . '#' . $language] = [
            'fields' => array_filter($decoded['fields'], 'is_string'),
            'derived_defaults' => array_filter($decoded['derived_defaults'], 'is_string'),
        ];
    }

    /** @param array<string,mixed> $record */
    private function key(array $record): string
    {
        return substr((string) ($record['provenance'] ?? ''), strlen('resources/schemas/'));
    }
}
