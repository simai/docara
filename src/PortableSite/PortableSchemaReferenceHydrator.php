<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final class PortableSchemaReferenceHydrator
{
    public function __construct(private readonly PortableSchemaReferenceProjector $projector = new PortableSchemaReferenceProjector) {}

    /** @param list<array<string,mixed>> $pages @return list<array<string,mixed>> */
    public function hydrate(array $pages): array
    {
        foreach ($pages as &$page) {
            $page['content_html'] = preg_replace_callback(
                '/<div\b(?<attributes>[^>]*)\bdata-docara-schema-reference\b(?<tail>[^>]*)><\/div>/iu',
                function (array $match): string {
                    $attributes = (string) ($match['attributes'] ?? '') . (string) ($match['tail'] ?? '');
                    preg_match('/\bdata-schema-name="(?<value>[^"]+)"/u', $attributes, $schema);
                    preg_match('/\bdata-schema-scope="(?<value>[^"]+)"/u', $attributes, $scope);

                    return $this->render($this->projector->project((string) ($schema['value'] ?? ''), (string) ($scope['value'] ?? '')));
                },
                (string) ($page['content_html'] ?? ''),
            ) ?? (string) ($page['content_html'] ?? '');
        }
        unset($page);

        return $pages;
    }

    /** @param list<array<string,mixed>> $records */
    private function render(array $records): string
    {
        $rows = '';
        foreach ($records as $record) {
            $default = ($record['has_default'] ?? false) === true
                ? $this->value($record['default'] ?? null)
                : 'не объявлен; effective value определяется inheritance/runtime';
            $rows .= '<tr><td><code>' . $this->escape((string) $record['path']) . '</code></td>'
                . '<td>' . $this->escape((string) $record['scope']) . '</td>'
                . '<td>' . (($record['required'] ?? false) === true ? 'да' : 'нет') . '</td>'
                . '<td>' . $this->escape((string) $record['type']) . '</td>'
                . '<td>' . $this->escape($default) . '</td>'
                . '<td>' . $this->escape((string) $record['validation']) . '</td>'
                . '<td><code>' . $this->escape((string) $record['provenance']) . '</code></td></tr>';
        }

        return '<div data-docara-schema-reference-view class="overflow-auto"><table><thead><tr>'
            . '<th>Поле</th><th>Scope</th><th>Обяз.</th><th>Тип</th><th>Default</th><th>Validation</th><th>Provenance</th>'
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
