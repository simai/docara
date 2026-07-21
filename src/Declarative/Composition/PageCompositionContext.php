<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Composition;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class PageCompositionContext
{
    /**
     * @param  array{title:string,label:string|null,logo:string|null,logo_dark:string|null,home_url:string}  $branding
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<array{id:string,level:int,text:string}>  $outline
     * @param  array{label:string,expand:string,collapse:string,contains_current:string}  $navigationCopy
     */
    public function __construct(
        public array $branding,
        public array $navigation,
        public array $outline,
        public array $navigationCopy,
        public string $tocLabel,
    ) {
        $this->assertBranding();
        $this->assertNavigation($navigation);
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
                'logo' => is_string($branding['logo'] ?? null) ? $branding['logo'] : null,
                'logo_dark' => is_string($branding['logo_dark'] ?? null) ? $branding['logo_dark'] : null,
                'home_url' => $homeUrl,
            ],
            self::normalizeNavigation($navigation),
            $normalizedOutline,
            [
                'label' => self::copy($copy, 'navigation.title', 'Sections'),
                'expand' => self::copy($copy, 'navigation.expand', 'Expand: '),
                'collapse' => self::copy($copy, 'navigation.collapse', 'Collapse: '),
                'contains_current' => self::copy($copy, 'navigation.contains_current', ', contains the current page'),
            ],
            self::copy($copy, 'navigation.outline', 'On this page'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'branding' => $this->branding,
            'navigation' => $this->navigation,
            'outline' => $this->outline,
            'navigation_copy' => $this->navigationCopy,
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
            || ($this->branding['logo'] !== null && ! self::safeUrl($this->branding['logo']))
            || ($this->branding['logo_dark'] !== null && ! self::safeUrl($this->branding['logo_dark']))
            || ($this->branding['logo_dark'] !== null && $this->branding['logo'] === null)
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

    private static function safeUrl(string $url): bool
    {
        return $url !== ''
            && str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && preg_match('/[\x00-\x20"\'<>\\\\]/', $url) !== 1;
    }
}
