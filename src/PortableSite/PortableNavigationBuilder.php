<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableNavigationBuilder
{
    public function __construct(
        private SchemaRepository $schemas = new SchemaRepository,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    public function build(array $pages, string $contentRoot, string $contentPath): array
    {
        $sections = $this->sectionMetadata($contentPath);
        $tree = [];

        foreach ($pages as $page) {
            if (($page['navigation_hidden'] ?? false) === true) {
                continue;
            }

            $relative = $this->relativePagePath((string) $page['page_path'], $contentRoot);
            $withoutExtension = preg_replace('/\.(?:md|markdown)$/i', '', $relative) ?? $relative;
            $segments = explode('/', $withoutExtension);
            $filename = array_pop($segments);
            $isIndex = $filename === 'index';

            if ($isIndex && $segments === []) {
                $tree['@home'] = $this->pageNode($page, '@home');

                continue;
            }

            $cursor = &$tree;
            $directory = '';
            foreach ($segments as $segment) {
                $directory = $directory === '' ? $segment : $directory . '/' . $segment;
                if (! isset($cursor[$segment])) {
                    $metadata = $sections[$directory] ?? [];
                    $cursor[$segment] = [
                        'key' => $directory,
                        'title' => (string) ($metadata['title'] ?? $this->humanize($segment)),
                        'url' => null,
                        'order' => $metadata['order'] ?? null,
                        'children' => [],
                    ];
                }
                $cursor = &$cursor[$segment]['children'];
            }

            if ($isIndex) {
                $branch = &$tree;
                $lastIndex = count($segments) - 1;
                foreach ($segments as $index => $segment) {
                    $branch = &$branch[$segment];
                    if ($index < $lastIndex) {
                        $branch = &$branch['children'];
                    }
                }
                $directory = implode('/', $segments);
                $metadata = $sections[$directory] ?? [];
                $branch['title'] = $this->navigationTitle($page, $metadata);
                $branch['url'] = (string) $page['url'];
                $branch['order'] = $this->navigationOrder($page, $metadata, $branch['order']);
                unset($branch);

                continue;
            }

            $node = $this->pageNode($page, $relative);
            $metadata = $sections[$withoutExtension] ?? [];
            $node['title'] = $this->navigationTitle($page, $metadata);
            $node['order'] = $this->navigationOrder($page, $metadata, $node['order']);
            if (isset($cursor[(string) $filename])) {
                $node['children'] = $cursor[(string) $filename]['children'];
            }
            $cursor[(string) $filename] = $node;
            unset($cursor);
        }

        return $this->sortNodes(array_values($tree));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    public function activate(array $nodes, string $activeUrl): array
    {
        foreach ($nodes as &$node) {
            $node['children'] = $this->activate($node['children'], $activeUrl);
            $node['active'] = is_string($node['url']) && $node['url'] === $activeUrl;
            $node['active_ancestor'] = $this->containsActive($node['children']);
            $node['open'] = $node['active'] || $node['active_ancestor'];
        }
        unset($node);

        return $nodes;
    }

    /** @param list<array<string, mixed>> $nodes */
    private function containsActive(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (($node['active'] ?? false) === true || ($node['active_ancestor'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $page @return array<string, mixed> */
    private function pageNode(array $page, string $key): array
    {
        return [
            'key' => $key,
            'title' => (string) $page['title'],
            'url' => (string) $page['url'],
            'order' => $page['navigation_order'] === null ? null : (int) $page['navigation_order'],
            'children' => [],
        ];
    }

    /**
     * A section descriptor names the linked branch unless the page sidecar
     * explicitly names the overview page. This keeps foo.md + foo/ and
     * foo/index.md + foo/ semantically equivalent.
     *
     * @param  array<string, mixed>  $page
     * @param  array{title?: string|null, order?: int|null, order_reset?: bool}  $metadata
     */
    private function navigationTitle(array $page, array $metadata): string
    {
        if ($this->hasExactPageSidecarProvenance($page, '/title')) {
            return (string) $page['title'];
        }

        return is_string($metadata['title'] ?? null)
            ? $metadata['title']
            : (string) $page['title'];
    }

    /**
     * An explicit page value wins, an explicit page reset clears the order,
     * and matching directory metadata wins over a more distant inherited
     * value. This keeps sibling and index overview forms equivalent.
     *
     * @param  array<string, mixed>  $page
     * @param  array{title?: string|null, order?: int|null, order_reset?: bool}  $metadata
     */
    private function navigationOrder(array $page, array $metadata, mixed $fallback): ?int
    {
        if ($this->hasExactPageSidecarProvenance($page, '/navigation/order')) {
            return is_int($page['navigation_order'] ?? null)
                ? $page['navigation_order']
                : null;
        }
        if ($this->hasExactPageSidecarProvenance($page, '/navigation')) {
            return null;
        }
        if (is_int($metadata['order'] ?? null)) {
            return $metadata['order'];
        }
        if (($metadata['order_reset'] ?? false) === true) {
            return null;
        }
        if (is_int($page['navigation_order'] ?? null)) {
            return $page['navigation_order'];
        }

        return is_int($fallback) ? $fallback : null;
    }

    /** @param array<string, mixed> $page */
    private function hasExactPageSidecarProvenance(array $page, string $pointer): bool
    {
        $plan = $page['plan'] ?? null;
        if (! $plan instanceof ResolvedPagePlan) {
            return false;
        }

        $source = $plan->provenance[$pointer] ?? null;

        return is_string($source) && str_ends_with($source, '.page.json');
    }

    /** @param list<array<string, mixed>> $nodes @return list<array<string, mixed>> */
    private function sortNodes(array $nodes): array
    {
        foreach ($nodes as &$node) {
            $node['children'] = $this->sortNodes(array_values($node['children']));
        }
        unset($node);

        usort($nodes, static function (array $left, array $right): int {
            $leftOrderMissing = $left['order'] === null;
            $rightOrderMissing = $right['order'] === null;
            if ($leftOrderMissing !== $rightOrderMissing) {
                return $leftOrderMissing ? 1 : -1;
            }
            if ($left['order'] !== $right['order']) {
                return $left['order'] <=> $right['order'];
            }
            if ($left['key'] === '@home') {
                return -1;
            }
            if ($right['key'] === '@home') {
                return 1;
            }

            return strcmp((string) $left['key'], (string) $right['key']);
        });

        return $nodes;
    }

    private function relativePagePath(string $pagePath, string $contentRoot): string
    {
        $prefix = rtrim($contentRoot, '/') . '/';
        if (! str_starts_with($pagePath, $prefix)) {
            throw new PortableConfigurationException(
                'PAGE_OUTSIDE_CONTENT_ROOT',
                "Portable page [$pagePath] is outside configured content root [$contentRoot].",
            );
        }

        return substr($pagePath, strlen($prefix));
    }

    /** @return array<string, array{title?: string|null, order?: int|null, order_reset?: bool}> */
    private function sectionMetadata(string $contentPath): array
    {
        $metadata = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentPath, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new PortableConfigurationException('SYMLINK_FORBIDDEN', 'Portable content cannot contain symbolic links.');
            }
            if (! $file->isFile() || $file->getFilename() !== '_section.json') {
                continue;
            }

            $relativeDirectory = ltrim(str_replace(
                '\\',
                '/',
                substr($file->getPath(), strlen($contentPath)),
            ), '/');
            if ($relativeDirectory === '') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents)) {
                throw new PortableConfigurationException(
                    'PORTABLE_FILE_READ_FAILED',
                    "Portable input [$relativeDirectory/_section.json] could not be read.",
                );
            }
            try {
                $descriptor = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'JSON_INVALID',
                    "File [$relativeDirectory/_section.json] is not valid JSON.",
                    $exception,
                );
            }
            $this->schemas->assertValid($descriptor, 'section.schema.json');
            if (! is_array($descriptor)) {
                continue;
            }
            $navigation = is_array($descriptor['navigation'] ?? null)
                ? $descriptor['navigation']
                : [];
            $metadata[$relativeDirectory] = [
                'title' => is_string($descriptor['title'] ?? null) ? $descriptor['title'] : null,
                'order' => is_int($navigation['order'] ?? null)
                    ? $navigation['order']
                    : null,
                'order_reset' => ($navigation['$reset'] ?? false) === true
                    && ! array_key_exists('order', $navigation),
            ];
        }

        return $metadata;
    }

    private function humanize(string $segment): string
    {
        $value = str_replace(['-', '_'], ' ', $segment);

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
