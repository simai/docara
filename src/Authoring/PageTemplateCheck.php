<?php

declare(strict_types=1);

namespace Simai\Docara\Authoring;

use Simai\Docara\Markdown\GeneratedRegions;

/**
 * Holds one page to its template from docara.authoring.json: the H2 sections
 * come from the template's list, in its order, the required ones present; a
 * section that asks for a live example has one, and so does each of its H3
 * when the section asks for that; a generated section holds nothing written by
 * hand outside its generated region. Each finding is a warning, like a
 * missing profile part.
 */
final readonly class PageTemplateCheck
{
    /**
     * @param  array<string, mixed>  $template
     * @param  list<string>  $omit  section ids the page leaves out on purpose
     * @return list<array<string, mixed>>
     */
    public function check(string $id, array $template, string $markdown, string $locale, array $omit, string $path): array
    {
        $headings = [];
        foreach ($template['sections'] as $section) {
            $heading = is_array($section['heading']) ? ($section['heading'][$locale] ?? null) : $section['heading'];
            if ($heading === null) {
                return [['code' => 'PAGE_TEMPLATE_LOCALE_UNDECLARED', 'status' => 'review_required', 'severity' => 'advisory', 'template' => $id, 'locale' => $locale]];
            }
            $headings[$heading] = $section;
        }
        $order = array_keys($headings);
        $sections = $this->sections($markdown, $path);
        $diagnostics = [];
        $finding = static fn (string $code, array $detail = []): array => ['code' => $code, 'status' => 'not_declared', 'severity' => 'warning', 'template' => $id] + $detail;

        $positions = [];
        foreach ($sections as $section) {
            if (! isset($headings[$section['heading']])) {
                if (($template['other_sections'] ?? false) !== true) {
                    $diagnostics[] = $finding('PAGE_TEMPLATE_SECTION_UNKNOWN', ['heading' => $section['heading']]);
                }

                continue;
            }
            $positions[] = array_search($section['heading'], $order, true);
        }
        foreach ($positions as $index => $position) {
            if ($index > 0 && $position <= $positions[$index - 1]) {
                $diagnostics[] = $finding('PAGE_TEMPLATE_SECTION_ORDER', ['expected' => array_values(array_filter($order, fn (string $heading): bool => in_array($heading, array_column($sections, 'heading'), true)))]);

                break;
            }
        }

        $present = [];
        foreach ($sections as $section) {
            $present[$section['heading']] ??= $section;
        }
        foreach ($headings as $heading => $definition) {
            $section = $present[$heading] ?? null;
            if ($section === null) {
                if (($definition['required'] ?? false) === true && ! in_array($definition['id'], $omit, true)) {
                    $diagnostics[] = $finding('PAGE_TEMPLATE_SECTION_MISSING', ['section' => $definition['id'], 'heading' => $heading]);
                }

                continue;
            }
            if (($definition['example'] ?? false) === true && ! $section['example']) {
                $diagnostics[] = $finding('PAGE_TEMPLATE_EXAMPLE_MISSING', ['section' => $definition['id'], 'heading' => $heading]);
            }
            if (($definition['example_per_subsection'] ?? false) === true) {
                foreach ($section['subsections'] as $subsection) {
                    if (! $subsection['example']) {
                        $diagnostics[] = $finding('PAGE_TEMPLATE_SUBSECTION_EXAMPLE_MISSING', ['section' => $definition['id'], 'heading' => $subsection['heading']]);
                    }
                }
            }
            if (($definition['generated'] ?? false) === true && $section['hand_written']) {
                $diagnostics[] = $finding('PAGE_TEMPLATE_GENERATED_SECTION_EDITED', ['section' => $definition['id'], 'heading' => $heading]);
            }
        }

        return $diagnostics;
    }

    /**
     * The H2 sections of a page with what the check needs: whether the body
     * holds a live example, its H3 subsections, and whether any line of it is
     * written outside a generated region.
     *
     * @return list<array{heading:string,example:bool,hand_written:bool,subsections:list<array{heading:string,example:bool}>}>
     */
    private function sections(string $markdown, string $path): array
    {
        $sections = [];
        $current = null;
        $fence = null;
        foreach ((new GeneratedRegions)->lines($markdown, $path, 'PAGE_TEMPLATE_GENERATED_REGION_INVALID') as ['line' => $line, 'generated' => $generated]) {
            $fenceLine = preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $match) === 1;
            if ($fenceLine) {
                if ($fence === null) {
                    $fence = $match[1];
                } elseif ($match[1][0] === $fence[0] && strlen($match[1]) >= strlen($fence)) {
                    $fence = null;
                }
            }
            if (! $fenceLine && $fence === null && preg_match('/^##\s+(.+?)\s*$/', $line, $heading) === 1) {
                if ($current !== null) {
                    $sections[] = $current;
                }
                $current = ['heading' => $this->heading($heading[1]), 'example' => false, 'hand_written' => false, 'subsections' => []];

                continue;
            }
            if ($current === null) {
                continue;
            }
            if (! $fenceLine && $fence === null && preg_match('/^###\s+(.+?)\s*$/', $line, $heading) === 1) {
                $current['subsections'][] = ['heading' => $this->heading($heading[1]), 'example' => false];
            } elseif (! $fenceLine && $fence === null && preg_match('/^:{3,}example\b/', $line) === 1) {
                $current['example'] = true;
                if ($current['subsections'] !== []) {
                    $current['subsections'][array_key_last($current['subsections'])]['example'] = true;
                }
            }
            if (! $generated && trim($line) !== '') {
                $current['hand_written'] = true;
            }
        }
        if ($current !== null) {
            $sections[] = $current;
        }

        return $sections;
    }

    /** A heading without its trailing `{#anchor}` and closing hashes. */
    private function heading(string $text): string
    {
        $text = preg_replace('/\s*\{#[^}]*}\s*$/', '', $text) ?? $text;

        return trim(preg_replace('/\s+#+\s*$/', '', $text) ?? $text);
    }
}
