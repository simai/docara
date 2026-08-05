<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final class PortableAtlasIndexHydrator
{
    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  array<string, mixed>  $atlas
     * @return list<array<string, mixed>>
     */
    public function hydrate(array $pages, array $atlas): array
    {
        $entries = is_array($atlas['entries'] ?? null) ? $atlas['entries'] : [];
        $fingerprint = (string) ($atlas['fingerprint'] ?? '');
        foreach ($pages as &$page) {
            $page['content_html'] = preg_replace_callback(
                '/<nav\b(?<attributes>[^>]*)\bdata-docara-atlas-index\b(?<tail>[^>]*)><\/nav>/iu',
                function (array $match) use ($entries, $fingerprint): string {
                    $attributes = (string) ($match['attributes'] ?? '') . (string) ($match['tail'] ?? '');
                    $filters = [];
                    foreach (['kind', 'authoring', 'support', 'owner', 'ids'] as $name) {
                        if (preg_match('/\bdata-atlas-' . $name . '="(?<value>[^"]*)"/u', $attributes, $value) === 1) {
                            $filters[$name] = explode(',', html_entity_decode($value['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        }
                    }

                    return $this->render($this->filter($entries, $filters), $fingerprint);
                },
                (string) ($page['content_html'] ?? ''),
            ) ?? (string) ($page['content_html'] ?? '');
        }
        unset($page);

        return $pages;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, list<string>>  $filters
     * @return list<array<string, mixed>>
     */
    private function filter(array $entries, array $filters): array
    {
        $map = ['kind' => 'kind', 'authoring' => 'authoring_kind', 'support' => 'support', 'owner' => 'owner', 'ids' => 'id'];
        $filtered = array_values(array_filter($entries, static function (mixed $entry) use ($filters, $map): bool {
            if (! is_array($entry)) {
                return false;
            }
            foreach ($filters as $filter => $values) {
                if (! in_array((string) ($entry[$map[$filter]] ?? ''), $values, true)) {
                    return false;
                }
            }

            return true;
        }));
        usort($filtered, static fn (array $left, array $right): int => [$left['kind'], $left['id']] <=> [$right['kind'], $right['id']]);

        return $filtered;
    }

    /** @param list<array<string, mixed>> $entries */
    private function render(array $entries, string $fingerprint): string
    {
        if ($entries === []) {
            return '<section data-docara-atlas-index-view data-atlas-fingerprint="' . $this->escape($fingerprint)
                . '"><p>В принятом Atlas нет элементов для этого фильтра.</p></section>';
        }
        $items = '';
        foreach ($entries as $entry) {
            $capabilities = array_values(array_filter($entry['capabilities'] ?? [], 'is_string'));
            $items .= '<li class="p-block-2 border-bottom border-outline-variant">'
                . '<div class="flex flex-wrap gap-1 items-center"><code>' . $this->escape((string) $entry['id']) . '</code>'
                . '<span class="text-label-small">' . $this->escape((string) $entry['kind']) . '</span>'
                . '<span class="text-label-small">' . $this->escape((string) $entry['support']) . '</span></div>'
                . '<p class="m-block-start-1 m-block-end-0 color-on-surface-variant">Владелец: '
                . $this->escape((string) $entry['owner']) . '; provider: '
                . $this->escape((string) $entry['provider']) . '; authoring: '
                . $this->escape((string) $entry['authoring_kind']) . '.</p>'
                . ($capabilities === [] ? '' : '<p class="m-block-start-1 m-block-end-0">Capabilities: '
                    . $this->escape(implode(', ', $capabilities)) . '.</p>')
                . '</li>';
        }

        return '<section data-docara-atlas-index-view data-atlas-fingerprint="' . $this->escape($fingerprint)
            . '"><ul class="list-none p-0 m-0 border-top border-outline-variant">' . $items . '</ul></section>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
