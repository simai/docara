<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Component pages follow one reader-facing standard: the same sections in the
 * same order, so a reader finds the markup, the parameters and the
 * accessibility notes in the same place on every page. A page joins the
 * standard by carrying the generated parameter table markers.
 */
final class ComponentPageStandardTest extends TestCase
{
    private const REQUIRED = ['Пример', 'Разметка', 'Доступность', 'Параметры', 'См. также'];

    private const OPTIONAL = ['Варианты', 'Состояния'];

    #[Test]
    public function every_standard_component_page_keeps_the_sections_in_order(): void
    {
        $pages = glob(dirname(__DIR__, 2) . '/docs/site/content/ru/components/*.md') ?: [];
        $checked = 0;
        foreach ($pages as $page) {
            $markdown = (string) file_get_contents($page);
            if (! str_contains($markdown, '<!-- docara:component-parameters:start -->')) {
                continue;
            }
            // Headings inside fenced examples belong to the example, not the page.
            $prose = (string) preg_replace('/^(`{3,}|~{3,}).*?^\1\s*$/msu', '', $markdown);
            preg_match_all('/^## (.+)$/mu', $prose, $headings);
            $sections = array_values(array_filter(
                array_map('trim', $headings[1]),
                static fn (string $heading): bool => in_array($heading, [...self::REQUIRED, ...self::OPTIONAL], true),
            ));
            $name = basename($page);

            self::assertSame(
                self::REQUIRED,
                array_values(array_intersect($sections, self::REQUIRED)),
                "$name: the standard sections are missing or out of order",
            );
            self::assertSame(
                array_values(array_intersect([...['Пример', 'Разметка'], ...self::OPTIONAL, ...array_slice(self::REQUIRED, 2)], $sections)),
                $sections,
                "$name: Варианты and Состояния stand between Разметка and Доступность",
            );
            $checked++;
        }

        self::assertGreaterThanOrEqual(25, $checked);
    }
}
