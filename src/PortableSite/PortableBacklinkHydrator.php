<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final class PortableBacklinkHydrator
{
    /**
     * @param list<array<string, mixed>> $pages
     * @return list<array<string, mixed>>
     */
    public function hydrate(array $pages): array
    {
        $targets = [];
        foreach ($pages as $index => $page) {
            $url = $this->normalizedInternalUrl((string) ($page['url'] ?? '/'));
            $targets[$url] = $index;
            $targets[rtrim($url, '/') ?: '/'] = $index;
        }

        $backlinks = [];
        foreach ($pages as $sourceIndex => $page) {
            $sourceUrl = $this->normalizedInternalUrl((string) ($page['url'] ?? '/'));
            $html = (string) ($page['content_html'] ?? '');
            if (preg_match_all('/<a\b[^>]*\bhref="(?<href>[^"]+)"/iu', $html, $matches) !== 1
                && ($matches['href'] ?? []) === []) {
                continue;
            }
            foreach (array_unique($matches['href'] ?? []) as $href) {
                $targetUrl = $this->resolveInternalHref(
                    $sourceUrl,
                    html_entity_decode((string) $href, ENT_QUOTES | ENT_HTML5),
                );
                if ($targetUrl === null) {
                    continue;
                }
                $targetIndex = $targets[$targetUrl] ?? $targets[rtrim($targetUrl, '/') ?: '/'] ?? null;
                if (! is_int($targetIndex) || $targetIndex === $sourceIndex) {
                    continue;
                }
                $backlinks[$targetIndex][$sourceUrl] = [
                    'url' => (string) ($page['url'] ?? $sourceUrl),
                    'title' => (string) ($page['title'] ?? $sourceUrl),
                ];
            }
        }

        foreach ($pages as $index => &$page) {
            $locale = (string) ($page['locale'] ?? 'en');
            $items = array_values($backlinks[$index] ?? []);
            usort($items, static fn (array $left, array $right): int => strcmp($left['title'], $right['title']));
            $page['content_html'] = preg_replace_callback(
                '/<nav\b(?<attributes>[^>]*)\bdata-docara-backlinks\b(?<tail>[^>]*)><\/nav>/iu',
                fn (array $match): string => $this->render($match, $items, $locale),
                (string) ($page['content_html'] ?? ''),
            ) ?? (string) ($page['content_html'] ?? '');
        }
        unset($page);

        return $pages;
    }

    /** @param array<string, mixed> $match @param list<array<string, string>> $items */
    private function render(array $match, array $items, string $locale): string
    {
        $attributes = (string) ($match['attributes'] ?? '') . (string) ($match['tail'] ?? '');
        preg_match('/\bdata-docara-backlinks-limit="(?<limit>[0-9]+)"/u', $attributes, $limitMatch);
        $limit = max(1, min(50, (int) ($limitMatch['limit'] ?? 5)));
        $visible = array_slice($items, 0, $limit);
        $heading = $locale === 'ru' ? 'Ссылаются на эту страницу' : 'Referenced by';
        if ($visible === []) {
            $empty = $locale === 'ru' ? 'Обратных ссылок пока нет.' : 'No backlinks yet.';

            return '<section data-docara-block="backlinks" class="m-bottom-1"><h2>'
                . $this->escape($heading) . '</h2><p class="color-on-surface-variant">'
                . $this->escape($empty) . '</p></section>';
        }

        $links = '';
        foreach ($visible as $item) {
            $links .= '<li><a href="' . $this->escape((string) $item['url']) . '">'
                . $this->escape((string) $item['title']) . '</a></li>';
        }

        return '<section data-docara-block="backlinks" class="m-bottom-1"><h2>'
            . $this->escape($heading) . '</h2><ul>' . $links . '</ul></section>';
    }

    private function resolveInternalHref(string $sourceUrl, string $href): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '//')
            || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $href) === 1) {
            return null;
        }
        $path = (string) (parse_url($href, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return null;
        }
        if (! str_starts_with($path, '/')) {
            $path = rtrim(dirname($sourceUrl), '/.') . '/' . $path;
        }

        return $this->normalizedInternalUrl($path);
    }

    private function normalizedInternalUrl(string $url): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $url)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return '/' . implode('/', $segments) . (str_ends_with($url, '/') && $segments !== [] ? '/' : '');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
