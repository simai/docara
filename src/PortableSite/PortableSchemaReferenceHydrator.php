<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\UiCopy;

final class PortableSchemaReferenceHydrator
{
    public function __construct(
        private readonly PortableSchemaReferenceProjector $projector = new PortableSchemaReferenceProjector,
        private readonly PortableSchemaDescriptions $descriptions = new PortableSchemaDescriptions,
    ) {}

    /** @param list<array<string,mixed>> $pages @return list<array<string,mixed>> */
    public function hydrate(array $pages): array
    {
        foreach ($pages as &$page) {
            $locale = (string) ($page['locale'] ?? 'en');
            $copy = UiCopy::buildDefaults($locale);
            $page['content_html'] = preg_replace_callback(
                '/<div\b(?<attributes>[^>]*)\bdata-docara-schema-reference\b(?<tail>[^>]*)><\/div>/iu',
                function (array $match) use ($copy, $locale): string {
                    $attributes = (string) ($match['attributes'] ?? '') . (string) ($match['tail'] ?? '');
                    preg_match('/\bdata-schema-name="(?<value>[^"]+)"/u', $attributes, $schema);
                    preg_match('/\bdata-schema-scope="(?<value>[^"]+)"/u', $attributes, $scope);
                    preg_match('/\bdata-schema-fields="(?<value>[^"]+)"/u', $attributes, $fields);
                    $records = $this->projector->project((string) ($schema['value'] ?? ''), (string) ($scope['value'] ?? ''));
                    if (($fields['value'] ?? '') !== '') {
                        $selected = explode(',', (string) $fields['value']);
                        $records = array_values(array_filter(
                            $records,
                            static fn (array $record): bool => in_array(
                                PortableSchemaReferenceProjector::topLevelFields([$record])[0],
                                $selected,
                                true,
                            ),
                        ));
                    }

                    return $this->render($records, $copy, $locale);
                },
                (string) ($page['content_html'] ?? ''),
            ) ?? (string) ($page['content_html'] ?? '');
        }
        unset($page);

        return $pages;
    }

    /**
     * One row per field: the path, what the field does and its default. The
     * type, level, validation rule and schema source sit in a disclosure under
     * the description, out of the reader's way.
     *
     * @param  list<array<string,mixed>>  $records
     * @param  array<string,string>  $copy
     */
    private function render(array $records, array $copy, string $locale): string
    {
        $rows = '';
        foreach ($records as $record) {
            $field = '<code>' . $this->escape((string) $record['path']) . '</code>';
            if (($record['required'] ?? false) === true) {
                $field .= '<br><small>' . $this->escape($copy['schema.required']) . '</small>';
            }
            $description = $this->descriptions->description($record, $locale);
            $details = '<details><summary>' . $this->escape($copy['schema.details']) . '</summary>'
                . '<p>' . $this->escape($copy['schema.type']) . ': <code>' . $this->escape((string) $record['type']) . '</code>'
                . ' · ' . $this->escape($copy['schema.scope']) . ': <code>' . $this->escape((string) $record['scope']) . '</code></p>'
                . '<p>' . $this->escape($copy['schema.validation']) . ': <code>' . $this->escape((string) $record['validation']) . '</code></p>'
                . '<p>' . $this->escape($copy['schema.provenance']) . ': <code>' . $this->escape((string) $record['provenance']) . '</code></p>'
                . '</details>';
            $rows .= '<tr><td>' . $field . '</td>'
                . '<td>' . ($description === null
                    ? $this->escape($copy['schema.description_missing'])
                    : $this->inlineCode($description)) . $details . '</td>'
                . '<td>' . $this->defaultCell($record, $copy, $locale) . '</td></tr>';
        }

        return '<div data-docara-schema-reference-view class="overflow-auto"><table><thead><tr>'
            . implode('', array_map(
                fn (string $id): string => '<th>' . $this->escape($copy[$id]) . '</th>',
                ['schema.field', 'schema.description', 'schema.default'],
            ))
            . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
    }

    /**
     * @param  array<string,mixed>  $record
     * @param  array<string,string>  $copy
     */
    private function defaultCell(array $record, array $copy, string $locale): string
    {
        $inheritable = in_array($record['scope'] ?? '', ['section', 'page', 'shared'], true)
            && str_starts_with((string) $record['provenance'], 'resources/schemas/presentation.schema.json#')
            && ! str_ends_with((string) $record['path'], '/$reset');
        if (($record['has_default'] ?? false) === true) {
            $value = '<code>' . $this->escape($this->value($record['default'] ?? null)) . '</code>';

            return $inheritable
                ? str_replace('{value}', $value, $this->escape($copy['schema.default_inherited']))
                : $value;
        }
        $derived = $this->descriptions->derivedDefault($record, $locale);
        if ($derived !== null) {
            return $this->inlineCode($derived);
        }

        return $this->escape($inheritable ? $copy['schema.inherited'] : $copy['schema.default_none']);
    }

    /** Escapes text and turns `code` spans of the description catalogue into <code>. */
    private function inlineCode(string $text): string
    {
        $parts = explode('`', $text);
        $html = '';
        foreach ($parts as $index => $part) {
            $html .= $index % 2 === 1 && $index < count($parts) - 1
                ? '<code>' . $this->escape($part) . '</code>'
                : ($index % 2 === 1 ? '`' : '') . $this->escape($part);
        }

        return $html;
    }

    private function value(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
