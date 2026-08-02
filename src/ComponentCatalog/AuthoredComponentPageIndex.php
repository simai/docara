<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

use Simai\Docara\Portable\PortableConfigurationException;

final class AuthoredComponentPageIndex
{
    /**
     * @param  array<string, mixed>  $catalog
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, array{
     *     id: string,
     *     slug: string,
     *     output: string,
     *     url: string,
     *     page_path: string,
     *     title: string,
     *     description: string
     * }>
     */
    public static function build(array $catalog, array $pages, string $outputPrefix): array
    {
        $pagesByOutput = [];
        foreach ($pages as $page) {
            if (($page['page_source_kind'] ?? null) !== 'authored_markdown') {
                continue;
            }
            $output = $page['output'] ?? null;
            if (! is_string($output) || $output === '') {
                throw new PortableConfigurationException(
                    'AUTHORED_COMPONENT_PAGE_OUTPUT_INVALID',
                    'An authored Markdown page has no output path.',
                );
            }
            $pagesByOutput[$output] = $page;
        }

        $index = [];
        $entries = is_array($catalog['entries'] ?? null) ? $catalog['entries'] : [];
        foreach ($entries as $entry) {
            if (! is_array($entry)
                || ($entry['lifecycle'] ?? null) !== 'supported'
                || ($entry['family'] ?? null) === 'framework_smart'
            ) {
                continue;
            }
            $id = PublicComponentPage::id($entry);
            if (! (new PublicComponentPolicy)->exposes($id)) {
                continue;
            }
            $output = PublicComponentPage::output($outputPrefix, $entry);
            $page = $pagesByOutput[$output] ?? null;
            if (! is_array($page)) {
                continue;
            }

            $title = trim((string) ($page['title'] ?? ''));
            $description = trim((string) ($page['description'] ?? ''));
            if ($title === '' || $description === '') {
                throw new PortableConfigurationException(
                    'AUTHORED_COMPONENT_PAGE_PRESENTATION_REQUIRED',
                    "Authored component page [$id] requires a title and a short Markdown description.",
                );
            }

            $index[$id] = [
                'id' => $id,
                'slug' => PublicComponentPage::slug($entry),
                'output' => $output,
                'url' => (string) ($page['url'] ?? ''),
                'page_path' => (string) ($page['page_path'] ?? ''),
                'title' => $title,
                'description' => $description,
            ];
        }
        ksort($index, SORT_STRING);

        return $index;
    }
}
