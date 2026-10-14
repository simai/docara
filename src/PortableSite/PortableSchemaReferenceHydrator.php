<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\UiCopy;

final class PortableSchemaReferenceHydrator
{
    public function __construct(private readonly PortableSchemaReferenceProjector $projector = new PortableSchemaReferenceProjector) {}

    /** @param list<array<string,mixed>> $pages @return list<array<string,mixed>> */
    public function hydrate(array $pages): array
    {
        foreach ($pages as &$page) {
            $copy = UiCopy::buildDefaults((string) ($page['locale'] ?? 'en'));
            $page['content_html'] = preg_replace_callback(
                '/<div\b(?<attributes>[^>]*)\bdata-docara-schema-reference\b(?<tail>[^>]*)><\/div>/iu',
                function (array $match) use ($copy): string {
                    $attributes = (string) ($match['attributes'] ?? '') . (string) ($match['tail'] ?? '');
                    preg_match('/\bdata-schema-name="(?<value>[^"]+)"/u', $attributes, $schema);
                    preg_match('/\bdata-schema-scope="(?<value>[^"]+)"/u', $attributes, $scope);

                    return $this->render($this->projector->project((string) ($schema['value'] ?? ''), (string) ($scope['value'] ?? '')), $copy);
                },
                (string) ($page['content_html'] ?? ''),
            ) ?? (string) ($page['content_html'] ?? '');
        }
        unset($page);

        return $pages;
    }

    /**
     * @param  list<array<string,mixed>>  $records
     * @param  array<string,string>  $copy
     */
    private function render(array $records, array $copy): string
    {
        $rows = '';
        foreach ($records as $record) {
            $default = ($record['has_default'] ?? false) === true
                ? $this->value($record['default'] ?? null)
                : $copy['schema.default_undeclared'];
            $rows .= '<tr><td><code>' . $this->escape((string) $record['path']) . '</code></td>'
                . '<td>' . $this->escape((string) $record['scope']) . '</td>'
                . '<td>' . $this->escape(($record['required'] ?? false) === true ? $copy['schema.yes'] : $copy['schema.no']) . '</td>'
                . '<td>' . $this->escape((string) $record['type']) . '</td>'
                . '<td>' . $this->escape($default) . '</td>'
                . '<td>' . $this->escape((string) $record['validation']) . '</td>'
                . '<td><code>' . $this->escape((string) $record['provenance']) . '</code></td></tr>';
        }

        return '<div data-docara-schema-reference-view class="overflow-auto"><table><thead><tr>'
            . implode('', array_map(
                fn (string $id): string => '<th>' . $this->escape($copy[$id]) . '</th>',
                ['schema.field', 'schema.scope', 'schema.required', 'schema.type', 'schema.default', 'schema.validation', 'schema.provenance'],
            ))
            . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
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
