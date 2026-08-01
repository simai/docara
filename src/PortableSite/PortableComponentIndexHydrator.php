<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final class PortableComponentIndexHydrator
{
    /**
     * @param list<array<string, mixed>> $pages
     * @param array<string, list<array{url:string,title:string,description:string}>> $indexes
     * @return list<array<string, mixed>>
     */
    public function hydrate(array $pages, array $indexes): array
    {
        foreach ($pages as &$page) {
            $route = '/' . trim((string) ($page['url'] ?? ''), '/') . '/';
            $entries = $indexes[$route] ?? [];
            $page['content_html'] = preg_replace_callback(
                '/<nav\b(?<attributes>[^>]*)\bdata-docara-component-index\b(?<tail>[^>]*)><\/nav>/iu',
                fn (): string => $this->render($entries),
                (string) ($page['content_html'] ?? ''),
            ) ?? (string) ($page['content_html'] ?? '');
        }
        unset($page);

        return $pages;
    }

    /**
     * @param list<array<string, mixed>> $pages
     * @return list<array{url:string,title:string,description:string}>
     */
    public function index(array $pages, string $catalogRoute): array
    {
        $catalogRoute = '/' . trim($catalogRoute, '/') . '/';
        $entries = [];
        foreach ($pages as $page) {
            $url = '/' . trim((string) ($page['url'] ?? ''), '/') . '/';
            if (($page['page_source_kind'] ?? null) !== 'authored_markdown'
                || preg_match('#^' . preg_quote($catalogRoute, '#') . '[^/]+/$#D', $url) !== 1
            ) {
                continue;
            }
            $entries[] = [
                'url' => $url,
                'title' => trim((string) ($page['title'] ?? '')),
                'description' => trim((string) ($page['description'] ?? '')),
            ];
        }
        usort(
            $entries,
            static fn (array $left, array $right): int => strnatcasecmp($left['title'], $right['title'])
                ?: strcmp($left['url'], $right['url']),
        );

        return $entries;
    }

    /** @param list<array{url:string,title:string,description:string}> $entries */
    private function render(array $entries): string
    {
        $items = '';
        foreach ($entries as $entry) {
            $items .= '<li class="p-block-2 border-bottom border-outline-variant">'
                . '<a class="font-title-medium" href="' . $this->escape($entry['url']) . '">'
                . $this->escape($entry['title']) . '</a>'
                . ($entry['description'] === '' ? '' : '<p class="m-block-start-1 m-block-end-0 color-on-surface-variant">'
                    . $this->escape($entry['description']) . '</p>')
                . '</li>';
        }

        return '<section data-docara-block="component-index" data-docara-component-index-view>'
            . '<ul class="list-none p-0 m-0 border-top border-outline-variant">' . $items . '</ul></section>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
