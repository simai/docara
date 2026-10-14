<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableAtlasIndexHydrator;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Tests\TestCase;

final class PortableAtlasIndexHydratorTest extends TestCase
{
    #[Test]
    public function it_filters_and_renders_only_admitted_atlas_entries(): void
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::atlas_index {kind=smart support=supported namespace=ui}\n:::\n");
        $pages = [['content_html' => $placeholder]];
        $atlas = [
            'fingerprint' => str_repeat('a', 64),
            'entries' => [
                $this->entry('ui.input', 'smart', 'ui', 'simai/bx-simai.main', 'framework', 'supported'),
                $this->entry('project.notice', 'smart', 'project', 'project/project', 'project', 'project'),
                $this->entry('docara.docs', 'layout', 'docara', 'simai/docara', 'docara', 'supported'),
            ],
        ];

        $html = (new PortableAtlasIndexHydrator)->hydrate($pages, $atlas)[0]['content_html'];

        self::assertStringContainsString('data-atlas-fingerprint="' . str_repeat('a', 64) . '"', $html);
        self::assertStringContainsString('<code>ui.input</code>', $html);
        self::assertStringNotContainsString('project.notice', $html);
        self::assertStringNotContainsString('docara.docs', $html);
    }

    #[Test]
    public function the_atlas_index_speaks_the_page_language(): void
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::atlas_index {kind=smart}\n:::\n");
        $empty = (new PortableMarkdownRenderer)->render(":::atlas_index {kind=layout}\n:::\n");
        $atlas = ['fingerprint' => str_repeat('b', 64), 'entries' => [
            $this->entry('ui.input', 'smart', 'ui', 'simai/ui', 'framework', 'supported'),
        ]];
        $pages = (new PortableAtlasIndexHydrator)->hydrate([
            ['locale' => 'en', 'content_html' => $placeholder . $empty],
            ['locale' => 'ru', 'content_html' => $placeholder . $empty],
        ], $atlas);

        self::assertStringContainsString('>Owner: simai/ui;', $pages[0]['content_html']);
        self::assertStringContainsString('The accepted Atlas has no entries for this filter.', $pages[0]['content_html']);
        self::assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', $pages[0]['content_html']);
        self::assertStringContainsString('>Владелец: simai/ui;', $pages[1]['content_html']);
        self::assertStringContainsString('В принятом Atlas нет элементов для этого фильтра.', $pages[1]['content_html']);
    }

    #[Test]
    public function unsafe_filter_syntax_fails_before_projection(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('MARKDOWN_ATLAS_FILTER_INVALID');
        (new PortableMarkdownRenderer)->render(":::atlas_index {ids=ui.input,../secret}\n:::\n");
    }

    /** @return array<string,mixed> */
    private function entry(string $id, string $kind, string $namespace, string $owner, string $origin, string $support): array
    {
        return [
            'id' => $id,
            'kind' => $kind,
            'namespace' => $namespace,
            'owner' => $owner,
            'owner_package' => $owner,
            'origin' => $origin,
            'authoring_kind' => 'block',
            'support' => $support,
            'status' => $support,
            'provider' => $owner . '.provider',
            'capabilities' => ['content'],
        ];
    }
}
