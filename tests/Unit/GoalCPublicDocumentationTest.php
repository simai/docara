<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableMarkdownProfile;

final class GoalCPublicDocumentationTest extends TestCase
{
    #[Test]
    public function components_root_has_exactly_six_physical_entry_owners(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru/components';
        $entries = [
            'native-markdown', 'inline-docara', 'block-docara',
            'containers', 'framework', 'project',
        ];
        $index = (string) file_get_contents(dirname($root) . '/components.md');
        foreach ($entries as $entry) {
            self::assertFileExists($root . '/' . $entry . '.md');
            self::assertStringContainsString('/ru/components/' . $entry . '/', $index);
        }
        self::assertSame(6, preg_match_all('#/ru/components/(?:native-markdown|inline-docara|block-docara|containers|framework|project)/#', $index));
    }

    #[Test]
    public function native_markdown_guide_covers_every_enabled_profile_capability(): void
    {
        $guide = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/components/native-markdown.md');
        $refs = [
            'native.code' => '/ru/components/code/',
            'native.footnotes_and_sources' => '/ru/components/footnotes-and-sources/',
            'native.headings_and_text' => '/ru/components/headings-and-text/',
            'native.links_and_images' => '/ru/components/links-and-images/',
            'native.lists_and_quotes' => '/ru/components/lists-and-quotes/',
            'native.table' => '/ru/components/table/',
        ];
        self::assertSame(array_keys($refs), array_column(PortableMarkdownProfile::bundled()->entries(), 'id'));
        foreach ($refs as $route) {
            self::assertStringContainsString($route, $guide);
        }
        self::assertStringContainsString('MARKDOWN_RAW_HTML_FORBIDDEN', $guide);
    }

    #[Test]
    public function container_and_framework_guides_expose_registry_contract_and_nonclaims(): void
    {
        $containers = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/components/containers.md');
        foreach (['allowed_children', 'slots', 'min_children', 'max_children', 'order', 'max_depth'] as $field) {
            self::assertStringContainsString('`' . $field . '`', $containers);
        }
        foreach (['MARKDOWN_GRID_CARD_REQUIRED', 'MARKDOWN_COLUMNS_REGION_COUNT_INVALID', 'MARKDOWN_STEPS_ORDERED_LIST_REQUIRED', 'MARKDOWN_BLOCK_UNCLOSED'] as $code) {
            self::assertStringContainsString('`' . $code . '`', $containers);
        }
        $framework = (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/components/framework.md');
        self::assertStringContainsString('ui.dropdown', $framework);
        self::assertStringContainsString('ui.list-item', $framework);
        self::assertStringContainsString('icons, avatars, tags', $framework);
        self::assertStringContainsString(':::atlas_index {kind=smart owner=ui support=supported}', $framework);
    }
}
