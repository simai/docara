<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PageCompositionContext
{
    /**
     * @param  array{title:string,label:string|null,mode:string,size:string,logo:string|null,logo_dark:string|null,home_url:string}  $branding
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<array<string, mixed>>  $headerNavigation
     * @param  list<array{id:string,level:int,text:string}>  $outline
     * @param  array{label:string,expand:string,collapse:string,contains_current:string}  $navigationCopy
     */
    public function __construct(
        public array $branding,
        public array $navigation,
        public array $headerNavigation,
        public array $outline,
        public array $navigationCopy,
        public string $headerNavigationLabel,
        public string $tocLabel,
    ) {
        $this->assertBranding();
        $this->assertNavigation($navigation);
        $this->assertHeaderNavigation($headerNavigation);
        $this->assertOutline();
    }

    /**
     * @param  array<string, mixed>  $branding
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<array<string, mixed>>  $outline
     */
    public static function fromBuilder(
        array $branding,
        string $homeUrl,
        array $navigation,
        array $outline,
        array $copy = [],
        array $headerNavigation = [],
        string $currentUrl = '',
    ): self {
        $normalizedOutline = [];
        foreach ($outline as $item) {
            $normalizedOutline[] = [
                'id' => is_string($item['id'] ?? null) ? $item['id'] : '',
                'level' => is_int($item['level'] ?? null) ? $item['level'] : 0,
                'text' => is_string($item['text'] ?? null) ? $item['text'] : '',
            ];
        }

        return new self(
            [
                'title' => is_string($branding['title'] ?? null) ? $branding['title'] : '',
                'label' => is_string($branding['label'] ?? null) ? $branding['label'] : null,
                'mode' => is_string($branding['mode'] ?? null) ? $branding['mode'] : 'full',
                'size' => is_string($branding['size'] ?? null) ? $branding['size'] : 'medium',
                'logo' => is_string($branding['logo'] ?? null) ? $branding['logo'] : null,
                'logo_dark' => is_string($branding['logo_dark'] ?? null) ? $branding['logo_dark'] : null,
                'home_url' => $homeUrl,
            ],
            self::normalizeNavigation($navigation),
            self::normalizeHeaderNavigation($headerNavigation, $currentUrl),
            $normalizedOutline,
            [
                'label' => self::copy($copy, 'navigation.title', 'Sections'),
                'expand' => self::copy($copy, 'navigation.expand', 'Expand: '),
                'collapse' => self::copy($copy, 'navigation.collapse', 'Collapse: '),
                'contains_current' => self::copy($copy, 'navigation.contains_current', ', contains the current page'),
            ],
            self::copy($copy, 'navigation.primary', 'Primary navigation'),
            self::copy($copy, 'navigation.outline', 'Contents'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'branding' => $this->branding,
            'navigation' => $this->navigation,
            'header_navigation' => $this->headerNavigation,
            'outline' => $this->outline,
            'navigation_copy' => $this->navigationCopy,
            'header_navigation_label' => $this->headerNavigationLabel,
            'toc_label' => $this->tocLabel,
        ];
    }

    /** @param array<string, mixed> $copy */
    private static function copy(array $copy, string $key, string $fallback): string
    {
        return is_string($copy[$key] ?? null) && trim($copy[$key]) !== ''
            ? $copy[$key]
            : $fallback;
    }

    private function assertBranding(): void
    {
        if (trim($this->branding['title']) === ''
            || ! self::safeUrl($this->branding['home_url'])
            || ($this->branding['label'] !== null && trim($this->branding['label']) === '')
            || ! in_array($this->branding['mode'], ['full', 'compact', 'logo', 'text'], true)
            || ! in_array($this->branding['size'], ['small', 'medium', 'large'], true)
            || ($this->branding['logo'] !== null && ! self::safeUrl($this->branding['logo']))
            || ($this->branding['logo_dark'] !== null && ! self::safeUrl($this->branding['logo_dark']))
            || ($this->branding['logo_dark'] !== null && $this->branding['logo'] === null)
            || ($this->branding['mode'] === 'logo' && $this->branding['logo'] === null)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_SHELL_BRANDING_INVALID',
                'Declarative shell branding is invalid.',
            );
        }
    }

    /** @param list<array<string, mixed>> $nodes */
    private function assertNavigation(array $nodes, int $depth = 1): void
    {
        if ($depth > 4) {
            throw new PortableConfigurationException(
                'DECLARATIVE_NAVIGATION_DEPTH_EXCEEDED',
                'Declarative navigation supports at most four levels.',
            );
        }
        foreach ($nodes as $node) {
            $children = $node['children'] ?? null;
            if (! is_string($node['key'] ?? null)
                || trim((string) $node['key']) === ''
                || ! is_string($node['title'] ?? null)
                || trim((string) $node['title']) === ''
                || (! is_null($node['url'] ?? null) && ! self::safeUrl((string) $node['url']))
                || ! is_bool($node['active'] ?? null)
                || ! is_bool($node['active_ancestor'] ?? null)
                || ! is_bool($node['current_section'] ?? null)
                || ! is_bool($node['open'] ?? null)
                || ! is_array($children)
                || ! array_is_list($children)
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_NAVIGATION_NODE_INVALID',
                    'Declarative navigation contains an invalid node.',
                );
            }
            if ($children !== []) {
                $this->assertNavigation($children, $depth + 1);
            }
        }
    }

    private function assertOutline(): void
    {
        foreach ($this->outline as $item) {
            if ($item['id'] === ''
                || preg_match('/[\x00-\x20"\'<>]/u', $item['id']) === 1
                || $item['level'] < 2
                || $item['level'] > 6
                || trim($item['text']) === ''
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_OUTLINE_ITEM_INVALID',
                    'Declarative outline contains an invalid item.',
                );
            }
        }
    }

    /** @param list<array<string, mixed>> $nodes */
    private function assertHeaderNavigation(array $nodes): void
    {
        if (count($nodes) > 8) {
            throw new PortableConfigurationException(
                'DECLARATIVE_HEADER_NAVIGATION_LIMIT_EXCEEDED',
                'Header navigation supports at most eight items.',
            );
        }
        $keys = [];
        foreach ($nodes as $node) {
            $key = $node['key'] ?? null;
            if (! is_string($key)
                || trim($key) === ''
                || isset($keys[$key])
                || ! is_string($node['title'] ?? null)
                || trim((string) $node['title']) === ''
                || ! is_string($node['url'] ?? null)
                || ! self::safeHeaderUrl((string) $node['url'])
                || ! is_bool($node['active'] ?? null)
                || ($node['active_ancestor'] ?? null) !== false
                || ($node['current_section'] ?? null) !== false
                || ($node['open'] ?? null) !== false
                || ($node['children'] ?? null) !== []
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_HEADER_NAVIGATION_INVALID',
                    'Header navigation contains an invalid or duplicate item.',
                );
            }
            $keys[$key] = true;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private static function normalizeNavigation(array $nodes): array
    {
        $normalized = [];
        foreach ($nodes as $node) {
            $children = is_array($node['children'] ?? null) && array_is_list($node['children'])
                ? self::normalizeNavigation($node['children'])
                : [];
            $normalized[] = [
                'key' => is_string($node['key'] ?? null) ? $node['key'] : '',
                'title' => is_string($node['title'] ?? null) ? $node['title'] : '',
                'url' => is_string($node['url'] ?? null) ? $node['url'] : null,
                'active' => ($node['active'] ?? false) === true,
                'active_ancestor' => ($node['active_ancestor'] ?? false) === true,
                'current_section' => ($node['current_section'] ?? false) === true,
                'open' => ($node['open'] ?? false) === true,
                'children' => $children,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<array<string, mixed>>
     */
    private static function normalizeHeaderNavigation(array $configuration, string $currentUrl): array
    {
        if (($configuration['enabled'] ?? false) !== true) {
            return [];
        }
        $items = $configuration['items'] ?? null;
        if (! is_array($items) || ! array_is_list($items) || $items === []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_HEADER_NAVIGATION_ITEMS_REQUIRED',
                'Enabled header navigation requires at least one item.',
            );
        }
        $normalized = [];
        foreach ($items as $item) {
            $url = is_string($item['href'] ?? null) ? $item['href'] : '';
            $normalized[] = [
                'key' => is_string($item['id'] ?? null) ? $item['id'] : '',
                'title' => is_string($item['label'] ?? null) ? $item['label'] : '',
                'url' => $url,
                'active' => self::headerUrlIsActive($url, $currentUrl),
                'active_ancestor' => false,
                'current_section' => false,
                'open' => false,
                'children' => [],
            ];
        }

        return $normalized;
    }

    private static function safeUrl(string $url): bool
    {
        return $url !== ''
            && str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && preg_match('/[\x00-\x20"\'<>\\\\]/', $url) !== 1;
    }

    private static function safeHeaderUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20"\'<>\\\\]/', $url) === 1) {
            return false;
        }

        return (str_starts_with($url, '/') && ! str_starts_with($url, '//'))
            || (str_starts_with($url, '#') && strlen($url) > 1)
            || preg_match('/^https:\/\/[^\/\s]+(?:\/[^\s]*)?$/D', $url) === 1;
    }

    private static function headerUrlIsActive(string $url, string $currentUrl): bool
    {
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return false;
        }
        $targetPath = parse_url($url, PHP_URL_PATH);
        $currentPath = parse_url($currentUrl, PHP_URL_PATH);
        if (! is_string($targetPath) || ! is_string($currentPath)) {
            return false;
        }

        return rtrim($targetPath, '/') === rtrim($currentPath, '/');
    }
}
