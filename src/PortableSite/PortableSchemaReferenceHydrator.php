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
     * One row per field: the path, then what the field does with its default
     * on a second line. The type, level, validation rule and schema source sit
     * in a disclosure under it, out of the reader's way.
     *
     * @param  list<array<string,mixed>>  $records
     * @param  array<string,string>  $copy
     */
    private function render(array $records, array $copy, string $locale): string
    {
        $rows = '';
        foreach ($records as $record) {
            $path = (string) $record['path'];
            $display = $this->displayPath($path, $copy);
            $field = '<code>' . $this->escape($display) . '</code>';
            if (($record['required'] ?? false) === true) {
                $field .= '<br><small>' . $this->escape($copy['schema.required']) . '</small>';
            }
            $description = $this->descriptions->description($record, $locale);
            $details = '<details><summary>' . $this->escape($copy['schema.details']) . '</summary>'
                . ($display === $path ? '' : '<p>' . $this->escape($copy['schema.path']) . ': <code>' . $this->escape($path) . '</code></p>')
                . '<p>' . $this->escape($copy['schema.type']) . ': <code>' . $this->escape((string) $record['type']) . '</code>'
                . ' · ' . $this->escape($copy['schema.scope']) . ': <code>' . $this->escape((string) $record['scope']) . '</code></p>'
                . '<p>' . $this->escape($copy['schema.validation']) . ': <code>' . $this->escape((string) $record['validation']) . '</code></p>'
                . '<p>' . $this->escape($copy['schema.provenance']) . ': <code>' . $this->escape((string) $record['provenance']) . '</code></p>'
                . '</details>';
            $default = $this->defaultCell($record, $copy, $locale);
            $rows .= '<tr><td>' . $field . '</td>'
                . '<td>' . ($description === null
                    ? $this->escape($copy['schema.description_missing'])
                    : $this->inlineCode($description))
                . ($default === null ? '' : '<br>' . $this->escape($copy['schema.default']) . ': ' . $default)
                . $details . '</td></tr>';
        }

        return '<div data-docara-schema-reference-view class="overflow-auto"><table><thead><tr>'
            . implode('', array_map(
                fn (string $id): string => '<th>' . $this->escape($copy[$id]) . '</th>',
                ['schema.field', 'schema.description'],
            ))
            . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
    }

    /**
     * @param  array<string,mixed>  $record
     * @param  array<string,string>  $copy
     */
    private function defaultCell(array $record, array $copy, string $locale): ?string
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

        return $inheritable ? $this->escape($copy['schema.inherited']) : null;
    }

    /**
     * Shows a pattern key such as "<^[a-z][a-z0-9_.-]+$>" by what it names:
     * "<region>" under regions, "<locale>" under locales. The exact pattern
     * stays in the technical details.
     *
     * @param  array<string,string>  $copy
     */
    private function displayPath(string $path, array $copy): string
    {
        $segments = explode('/', $path);
        foreach ($segments as $index => $segment) {
            if (! str_starts_with($segment, '<')) {
                continue;
            }
            $segments[$index] = '<' . match ($segments[$index - 1] ?? '') {
                'regions' => $copy['schema.key_region'],
                'locales' => $copy['schema.key_locale'],
                default => $copy['schema.key_any'],
            } . '>';
        }

        return implode('/', $segments);
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
