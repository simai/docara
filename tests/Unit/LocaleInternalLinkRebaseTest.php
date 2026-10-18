<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\I18n\LocaleInternalLinkProjector;
use Tests\TestCase;

final class LocaleInternalLinkRebaseTest extends TestCase
{
    #[Test]
    public function root_relative_links_are_rebased_under_a_subdirectory_base_url(): void
    {
        self::assertSame('/start/', LocaleInternalLinkProjector::rebase('/start/', '/'));
        self::assertSame('/docara/start/', LocaleInternalLinkProjector::rebase('/start/', '/docara/'));
        self::assertSame('/docara/', LocaleInternalLinkProjector::rebase('/', '/docara/'));
        self::assertSame('/docara/ru/x/#a', LocaleInternalLinkProjector::rebase('/docara/ru/x/#a', '/docara/'));
        self::assertSame('/docara', LocaleInternalLinkProjector::rebase('/docara', '/docara/'));
        self::assertSame('/docara/docarax/', LocaleInternalLinkProjector::rebase('/docarax/', '/docara/'));
        foreach (['//cdn.example/x.js', 'https://example.com/', 'relative/', '#top', '?q=1', 'mailto:a@b.c'] as $unchanged) {
            self::assertSame($unchanged, LocaleInternalLinkProjector::rebase($unchanged, '/docara/'));
        }

        $projector = new LocaleInternalLinkProjector(['/docara/start/' => '/docara/ru/start/'], '/docara/');
        self::assertSame(
            '<a class="x" href="/docara/ru/start/#install">a</a>'
                . '<a href="/docara/ru/components/">b</a>'
                . '<a href="https://github.com/simai/docara">c</a>'
                . '<iframe src="/docara/ru/project-demos/" title="d"></iframe>'
                . '<iframe title="e" srcdoc="&lt;a href=&quot;/x/&quot;&gt;"></iframe>'
                . '<img src="/logo.svg">',
            $projector->project(
                '<a class="x" href="/start/#install">a</a>'
                . '<a href="/ru/components/">b</a>'
                . '<a href="https://github.com/simai/docara">c</a>'
                . '<iframe src="/ru/project-demos/" title="d"></iframe>'
                . '<iframe title="e" srcdoc="&lt;a href=&quot;/x/&quot;&gt;"></iframe>'
                . '<img src="/logo.svg">',
            ),
        );
        $html = '<a href="/start/">a</a>';
        self::assertSame($html, (new LocaleInternalLinkProjector([], '/'))->project($html));
    }
}
