<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableNavigationBuilder;

final class PortableNavigationBuilderTest extends TestCase
{
    #[Test]
    public function visible_navigation_hides_leaf_pages_but_keeps_an_unlinked_branch_with_visible_children(): void
    {
        $visible = (new PortableNavigationBuilder)->visible($this->topology());

        self::assertSame(['@home', 'guide', 'landing', 'english', 'reference'], array_column($visible, 'key'));
        self::assertNull($visible[1]['url']);
        self::assertSame(['intro', 'advanced'], array_column($visible[1]['children'], 'key'));
        self::assertNotContains('hidden', array_column($visible[1]['children'], 'key'));
    }

    #[Test]
    public function visible_navigation_keeps_a_null_url_section_with_a_visible_document_child(): void
    {
        $topology = [
            $this->node('group', 'Group', null, false, null, null, [
                $this->node('document', 'Document', '/document/', false, 'ru', 'docs'),
            ]),
        ];

        $visible = (new PortableNavigationBuilder)->visible($topology);

        self::assertCount(1, $visible);
        self::assertNull($visible[0]['url']);
        self::assertSame(['document'], array_column($visible[0]['children'], 'key'));
    }

    #[Test]
    public function breadcrumbs_and_adjacency_come_from_the_same_topology(): void
    {
        $context = (new PortableNavigationBuilder)->readingContextForUrl(
            $this->topology(),
            '/guide/hidden/',
        );

        self::assertSame([
            ['title' => 'Home', 'url' => '/'],
            ['title' => 'Guide', 'url' => '/guide/'],
            ['title' => 'Hidden', 'url' => '/guide/hidden/'],
        ], $context['breadcrumbs']);
        self::assertSame(['title' => 'Intro', 'url' => '/guide/intro/'], $context['previous']);
        self::assertSame(['title' => 'Advanced', 'url' => '/guide/advanced/'], $context['next']);
    }

    #[Test]
    public function adjacency_skips_other_locales_hidden_and_landing_targets(): void
    {
        $builder = new PortableNavigationBuilder;

        $home = $builder->readingContextForUrl($this->topology(), '/');
        self::assertSame([], $home['breadcrumbs']);
        self::assertNull($home['previous']);
        self::assertSame(['title' => 'Intro', 'url' => '/guide/intro/'], $home['next']);

        $lastRussianDoc = $builder->readingContextForUrl($this->topology(), '/reference/');
        self::assertSame(['title' => 'Advanced', 'url' => '/guide/advanced/'], $lastRussianDoc['previous']);
        self::assertNull($lastRussianDoc['next']);
    }

    #[Test]
    public function breadcrumbs_find_home_even_when_navigation_order_places_it_after_another_root_page(): void
    {
        $topology = $this->topology();
        $home = array_shift($topology);
        array_splice($topology, 1, 0, [$home]);

        $context = (new PortableNavigationBuilder)->readingContextForUrl($topology, '/guide/intro/');

        self::assertSame([
            ['title' => 'Home', 'url' => '/'],
            ['title' => 'Guide', 'url' => '/guide/'],
            ['title' => 'Intro', 'url' => '/guide/intro/'],
        ], $context['breadcrumbs']);
    }

    /** @return list<array<string, mixed>> */
    private function topology(): array
    {
        return [
            $this->node('@home', 'Home', '/', false, 'ru', 'docs'),
            $this->node('guide', 'Guide', '/guide/', true, 'ru', 'docs', [
                $this->node('intro', 'Intro', '/guide/intro/', false, 'ru', 'docs'),
                $this->node('hidden', 'Hidden', '/guide/hidden/', true, 'ru', 'docs'),
                $this->node('advanced', 'Advanced', '/guide/advanced/', false, 'ru', 'docs'),
            ]),
            $this->node('landing', 'Landing', '/landing/', false, 'ru', 'landing'),
            $this->node('english', 'English', '/en/', false, 'en', 'docs'),
            $this->node('reference', 'Reference', '/reference/', false, 'ru', 'docs'),
            $this->node('private', 'Private', null, false, null, null, [
                $this->node('secret', 'Secret', '/private/secret/', true, 'ru', 'docs'),
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function node(
        string $key,
        string $title,
        ?string $url,
        bool $hidden,
        ?string $locale,
        ?string $preset,
        array $children = [],
    ): array {
        return compact('key', 'title', 'url', 'hidden', 'locale', 'preset', 'children');
    }
}
