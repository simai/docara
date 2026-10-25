<?php

declare(strict_types=1);

namespace Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Authoring\DocumentationStandard;
use Simai\Docara\Quality\HtmlPageReader;
use Simai\Docara\Quality\QualityChecks;
use Simai\Docara\Quality\QualityPageAnalyzer;
use Simai\Docara\Quality\QualitySite;
use Simai\Docara\Quality\StaticLinkResolver;
use Simai\Docara\Quality\TextStatistics;

final class QualityChecksTest extends TestCase
{
    private const PROSE = 'The page explains how a reader builds the site and checks the result before it goes online. '
        . 'Each step names the command, the folder it writes and what the reader sees after it runs. '
        . 'A short example shows the output of a successful build, and a second one shows a failed build with its message. '
        . 'The last part lists what to do when the build stops and where to look for the cause of the problem. '
        . 'Every section is short, so the reader can come back to one step without reading the whole page again.';

    #[Test]
    public function a_clean_page_has_no_findings(): void
    {
        self::assertSame([], (new QualityPageAnalyzer)->analyze(self::page(), self::site()));
    }

    /** @return array<string, array{string, Closure}> */
    public static function failures(): array
    {
        $facts = static fn (array $page, array $facts): array => ['facts' => $facts + $page['facts']] + $page;

        return [
            'profile part' => ['QUALITY_PROFILE_PART_MISSING', static fn (array $p): array => ['profile_missing' => ['ordered_steps']] + $p],
            'description missing' => ['QUALITY_DESCRIPTION_MISSING', static fn (array $p): array => ['description' => null] + $p],
            'description short' => ['QUALITY_DESCRIPTION_LENGTH', static fn (array $p): array => ['description' => 'Build it.'] + $p],
            'description long' => ['QUALITY_DESCRIPTION_LENGTH', static fn (array $p): array => ['description' => str_repeat('Build the site. ', 15)] + $p],
            'stub' => ['QUALITY_PAGE_STUB', static fn (array $p): array => $facts($p, ['text' => 'Build the site. ' . str_repeat('Word ', 40), 'blocks' => ['Build the site.']])],
            'placeholder' => ['QUALITY_PLACEHOLDER_LEFT', static fn (array $p): array => ['markdown' => "# Title\n\nExplain one subject. Keep sentences short and use plain words.\n"] + $p],
            'draft linked' => ['QUALITY_DRAFT_LINKED', static fn (array $p): array => $facts($p, ['links' => [['href' => '/other/', 'text' => 'Other page'], ['href' => '/draft/', 'text' => 'Coming soon']]])],
            'source changed' => ['QUALITY_SOURCE_CHANGED', static fn (array $p): array => ['freshness' => [['source' => 'api', 'key' => 'cli.build', 'status' => 'changed']]] + $p],
            'not in navigation' => ['QUALITY_NOT_IN_NAVIGATION', static fn (array $p): array => ['url' => '/hidden/', 'file' => 'hidden/index.html'] + $p],
            'orphan' => ['QUALITY_ORPHAN_PAGE', static fn (array $p): array => ['url' => '/lonely/', 'file' => 'lonely/index.html'] + $p],
            'no related links' => ['QUALITY_NO_RELATED_LINKS', static fn (array $p): array => $facts($p, ['links' => [['href' => 'https://example.com/', 'text' => 'Example site']]])],
            'topic unclear' => ['QUALITY_TOPIC_UNCLEAR', static fn (array $p): array => ['title' => 'Zebra', 'tags' => []] + $p],
            'sentences long' => ['QUALITY_SENTENCES_LONG', static fn (array $p): array => $facts($p, ['blocks' => [str_repeat('word ', 25) . 'end.', str_repeat('word ', 24) . 'end.']])],
            'sentence too long' => ['QUALITY_SENTENCE_TOO_LONG', static fn (array $p): array => $facts($p, ['blocks' => ['Short one.', 'Short two.', 'Short three.', str_repeat('word ', 45) . 'end.']])],
            'page too long' => ['QUALITY_PAGE_TOO_LONG', static fn (array $p): array => $facts($p, ['text' => str_repeat('word ', 2600)])],
            'no subheadings' => ['QUALITY_NO_SUBHEADINGS', static fn (array $p): array => $facts($p, ['text' => str_repeat('word ', 700), 'headings' => [['level' => 1, 'text' => 'Build the site']]])],
            'heading too deep' => ['QUALITY_HEADING_TOO_DEEP', static fn (array $p): array => $facts($p, ['headings' => [['level' => 1, 'text' => 'Build'], ['level' => 2, 'text' => 'A'], ['level' => 3, 'text' => 'B'], ['level' => 4, 'text' => 'C'], ['level' => 5, 'text' => 'D']]])],
            'latin heavy' => ['QUALITY_LATIN_HEAVY', static fn (array $p): array => ['locale' => 'ru'] + $facts($p, ['lang' => 'ru', 'text' => str_repeat('Сборка сайта ', 30) . str_repeat('runtime manifest ', 20)])],
            'term not in glossary' => ['QUALITY_TERM_NOT_IN_GLOSSARY', static fn (array $p): array => ['locale' => 'ru'] + $facts($p, ['lang' => 'ru', 'text' => str_repeat('Сборка сайта читает файлы ', 40), 'blocks' => ['Сборка читает manifest и lock, а runtime ищет provenance.', 'Ещё раз: manifest, lock, runtime, provenance.']])],
            'image alt' => ['QUALITY_IMAGE_ALT_MISSING', static fn (array $p): array => $facts($p, ['images' => [['src' => 'a.png', 'alt' => ''], ['src' => 'b.png', 'alt' => null]]])],
            'generic link text' => ['QUALITY_LINK_TEXT_GENERIC', static fn (array $p): array => $facts($p, ['links' => [['href' => '/other/', 'text' => 'здесь']]])],
            'empty link text' => ['QUALITY_LINK_TEXT_GENERIC', static fn (array $p): array => $facts($p, ['links' => [['href' => '/other/', 'text' => 'Other page'], ['href' => '/other/', 'text' => '']]])],
            'heading order' => ['QUALITY_HEADING_ORDER', static fn (array $p): array => $facts($p, ['headings' => [['level' => 2, 'text' => 'Build'], ['level' => 1, 'text' => 'Title']]])],
            'two h1' => ['QUALITY_HEADING_ORDER', static fn (array $p): array => $facts($p, ['headings' => [['level' => 1, 'text' => 'Build'], ['level' => 1, 'text' => 'Again']]])],
            'heading skipped' => ['QUALITY_HEADING_LEVEL_SKIPPED', static fn (array $p): array => $facts($p, ['headings' => [['level' => 1, 'text' => 'Build'], ['level' => 3, 'text' => 'Deep']]])],
            'table header' => ['QUALITY_TABLE_HEADER_MISSING', static fn (array $p): array => $facts($p, ['tables' => [true, false]])],
            'lang missing' => ['QUALITY_LANG_MISSING', static fn (array $p): array => $facts($p, ['lang' => null])],
            'lang mismatch' => ['QUALITY_LANG_MISSING', static fn (array $p): array => $facts($p, ['lang' => 'de'])],
            'broken link' => ['QUALITY_LINK_BROKEN', static fn (array $p): array => $facts($p, ['links' => [['href' => '/other/', 'text' => 'Other page'], ['href' => '/missing/', 'text' => 'Missing page']]])],
            'link above the root' => ['QUALITY_LINK_BROKEN', static fn (array $p): array => $facts($p, ['links' => [['href' => '/other/', 'text' => 'Other page'], ['href' => '../../../x.html', 'text' => 'Far away']]])],
            'anchor missing' => ['QUALITY_ANCHOR_MISSING', static fn (array $p): array => $facts($p, ['links' => [['href' => '/other/#nowhere', 'text' => 'Other page']]])],
        ];
    }

    #[Test]
    #[DataProvider('failures')]
    public function each_check_reports_its_code(string $code, Closure $mutate): void
    {
        $findings = (new QualityPageAnalyzer)->analyze($mutate(self::page()), self::site());

        self::assertContains($code, array_column($findings, 'code'), json_encode($findings, JSON_UNESCAPED_UNICODE) ?: '');
        $finding = array_values(array_filter($findings, static fn (array $finding): bool => $finding['code'] === $code))[0];
        self::assertSame(QualityChecks::get($code)['group'], $finding['group']);
        self::assertNotSame('', $finding['detail']);
    }

    #[Test]
    public function every_check_has_a_test_case_a_group_a_severity_and_a_hint(): void
    {
        $tested = array_unique(array_column(self::failures(), 0));
        // QUALITY_NOT_IN_SEARCH is covered by a dedicated test below.
        $tested[] = 'QUALITY_NOT_IN_SEARCH';
        self::assertEqualsCanonicalizing(array_keys(QualityChecks::ALL), array_values(array_unique($tested)));
        foreach (QualityChecks::ALL as $code => $check) {
            self::assertMatchesRegularExpression('/^QUALITY_[A-Z_]+$/', $code);
            self::assertContains($check['group'], QualityChecks::GROUPS, $code);
            self::assertContains($check['severity'], [QualityChecks::WARNING, QualityChecks::ADVICE], $code);
            self::assertNotSame('', $check['hint'], $code);
        }
    }

    #[Test]
    public function search_and_home_page_rules(): void
    {
        $analyzer = new QualityPageAnalyzer;
        $page = ['url' => '/unindexed/', 'file' => 'unindexed/index.html'] + self::page();
        $codes = array_column($analyzer->analyze($page, self::site(navigation: ['/unindexed/' => true], inbound: ['/unindexed/' => 1])), 'code');
        self::assertSame(['QUALITY_NOT_IN_SEARCH'], $codes);
        // Without a search index the check does not apply.
        self::assertSame([], $analyzer->analyze($page, self::site(navigation: ['/unindexed/' => true], inbound: ['/unindexed/' => 1], search: false)));
        // The home page needs no inbound link.
        self::assertSame([], $analyzer->analyze(['url' => '/', 'file' => 'index.html', 'home' => true] + self::page(), self::site(navigation: ['/' => true], search: false)));
    }

    #[Test]
    public function an_inferred_profile_part_is_advice_and_see_also_counts_as_related(): void
    {
        $analyzer = new QualityPageAnalyzer;
        $findings = $analyzer->analyze(['profile_missing' => ['ordered_steps'], 'profile_inferred' => true] + self::page(), self::site());
        self::assertSame('advice', $findings[0]['severity']);

        $page = self::page();
        $page['facts']['links'] = [];
        $page['facts']['headings'][] = ['level' => 2, 'text' => 'См. также'];
        self::assertNotContains('QUALITY_NO_RELATED_LINKS', array_column($analyzer->analyze($page, self::site()), 'code'));
    }

    #[Test]
    public function template_text_in_code_is_not_a_leftover(): void
    {
        $markdown = "# Templates\n\n```markdown\nExplain one subject. Keep sentences short and use plain words.\n```\n";
        self::assertSame([], (new QualityPageAnalyzer)->analyze(['markdown' => $markdown] + self::page(), self::site()));
    }

    #[Test]
    public function the_reader_takes_the_article_without_examples_code_and_controls(): void
    {
        $html = '<!doctype html><html lang="ru"><head><meta name="robots" content="noindex"></head><body>'
            . '<nav data-docara-smart="docara.navigation"><a href="/ru/a/">A</a></nav>'
            . '<article class="docara-prose"><h1 id="t">Заголовок</h1><p>Текст с <code>docara build</code> и <a href="/ru/b/">ссылкой</a>.</p>'
            . '<section data-docara-example="x"><h2>Пример</h2><img src="demo.png"></section>'
            . '<pre><code>runtime manifest lock</code></pre><button>Copy</button>'
            . '<table><tr><td>a</td></tr></table><img src="x.png" alt="Схема"><p><strong>Lock</strong> — файл.</p></article></body></html>';
        $facts = (new HtmlPageReader)->read($html);

        self::assertSame('ru', $facts['lang']);
        self::assertTrue($facts['noindex']);
        self::assertSame(['/ru/a/'], $facts['navigation']);
        self::assertSame([['level' => 1, 'text' => 'Заголовок']], $facts['headings']);
        self::assertSame([['href' => '/ru/b/', 'text' => 'ссылкой']], $facts['links']);
        self::assertSame([['src' => 'x.png', 'alt' => 'Схема']], $facts['images']);
        self::assertSame([false], $facts['tables']);
        self::assertSame('Текст с ' . TextStatistics::CODE_TOKEN . ' и ссылкой.', $facts['blocks'][0]);
        self::assertStringNotContainsString('runtime', $facts['text']);
        self::assertStringNotContainsString('Copy', $facts['text']);
        self::assertContains('Lock', $facts['terms']);
        self::assertArrayHasKey('t', $facts['ids']);
    }

    #[Test]
    public function a_list_of_code_names_or_a_table_cell_is_not_a_sentence(): void
    {
        $names = '<p><strong>A.</strong> ' . implode(' · ', array_map(static fn (int $i): string => '<code>icon_' . $i . '</code>', range(1, 120))) . '</p>';
        $cell = '<table><tr><th>Поле</th></tr><tr><td><p>' . str_repeat('слово ', 50) . 'конец.</p></td></tr></table>';
        $prose = '<p>Atlas показывает <code>slots</code>, <code>order</code> и <code>max_depth</code> для каждого контейнера.</p>';
        $facts = (new HtmlPageReader)->read('<html lang="en"><body><article class="docara-prose"><h1>Icons</h1>' . $names . $cell . $prose . '</article></body></html>');

        self::assertCount(2, $facts['blocks'], 'The paragraph inside the table cell is not a prose block.');
        $text = new TextStatistics;
        self::assertTrue($text->isCodeList($facts['blocks'][0]));
        self::assertFalse($text->isCodeList($facts['blocks'][1]), 'Prose with a few code names is still prose.');

        $page = ['facts' => ['blocks' => [$facts['blocks'][0], ...self::page()['facts']['blocks']]] + self::page()['facts']] + self::page();
        $analyzer = new QualityPageAnalyzer;
        $codes = array_column($analyzer->analyze($page, self::site()), 'code');
        self::assertNotContains('QUALITY_SENTENCE_TOO_LONG', $codes);
        self::assertNotContains('QUALITY_SENTENCES_LONG', $codes);
        self::assertLessThanOrEqual(QualityPageAnalyzer::MAX_SENTENCE_WORDS, $analyzer->metrics()['longest_sentence_words']);
    }

    #[Test]
    public function text_statistics_are_plain_and_deterministic(): void
    {
        $text = new TextStatistics;
        self::assertSame([3, 4], $text->sentenceLengths('One two three. Four five six seven.'));
        self::assertSame([4], $text->sentenceLengths('Run ' . TextStatistics::CODE_TOKEN . ' now please.'));
        self::assertSame(0.5, $text->latinShare('сборка build сайт site'));
        self::assertTrue(TextStatistics::usesNonLatinScript('ru-RU'));
        self::assertFalse(TextStatistics::usesNonLatinScript('de'));
        self::assertSame(['публикуйте', 'сайт'], $text->topicWords('Публикуйте этот сайт'));
        self::assertSame(['smart', 'компоненты'], $text->topicWords('Smart-компоненты'));
        self::assertSame([], $text->topicWords('Код'));
        self::assertTrue(TextStatistics::sameWordForm('публикация', 'публикуйте'));
        self::assertTrue(TextStatistics::sameWordForm('ошибки', 'ошибок'));
        self::assertTrue(TextStatistics::sameWordForm('начало', 'начать'));
        self::assertFalse(TextStatistics::sameWordForm('просмотр', 'простой'));
        self::assertFalse(TextStatistics::sameWordForm('тестирование', 'тесты'));
        self::assertSame('registry', TextStatistics::englishSingular('registries'));
        self::assertSame('hash', TextStatistics::englishSingular('hashes'));
        self::assertSame('asset', TextStatistics::englishSingular('assets'));
        self::assertSame('status', TextStatistics::englishSingular('status'));
        self::assertSame('class', TextStatistics::englishSingular('class'));
    }

    #[Test]
    public function the_topic_check_reads_word_forms_compounds_and_the_first_paragraph(): void
    {
        $analyzer = new QualityPageAnalyzer;
        $topic = static fn (array $page): bool => in_array('QUALITY_TOPIC_UNCLEAR', array_column($analyzer->analyze($page, self::site()), 'code'), true);
        $page = ['locale' => 'ru', 'tags' => []] + self::page();
        $page['facts']['lang'] = 'ru';

        // Another form of a title word counts.
        self::assertFalse($topic(['title' => 'Ошибки обновления', 'description' => 'Коды ошибок команды upgrade с причиной и способом исправления.'] + $page));
        // A part of a hyphenated word counts.
        self::assertFalse($topic(['title' => 'Компоненты', 'description' => 'Smart-компоненты и блоки Markdown, доступные на сайте.'] + $page));
        // The first paragraph counts even when there is a description.
        $withParagraph = ['title' => 'Справочник', 'description' => 'Точные команды, поля настроек, схемы и коды ошибок.'] + $page;
        $withParagraph['facts']['blocks'] = ['Справочник нужен для точной проверки команд и полей.'];
        self::assertFalse($topic($withParagraph));
        // A title with no word of four letters gives nothing to compare.
        self::assertFalse($topic(['title' => 'Код', 'description' => 'Блок с подсветкой синтаксиса и кнопкой копирования.'] + $page));
        // A title the introduction never names is still reported.
        self::assertTrue($topic(['title' => 'Миграция', 'description' => 'Явный и проверяемый перенос существующего сайта.'] + $page));
    }

    #[Test]
    public function everyday_english_words_and_plural_forms_are_not_glossary_terms(): void
    {
        $analyzer = new QualityPageAnalyzer;
        $terms = static function (array $blocks, array $glossary = []) use ($analyzer): ?string {
            $page = ['locale' => 'ru'] + self::page();
            $page['facts'] = ['lang' => 'ru', 'text' => str_repeat('Сборка сайта читает файлы ', 40), 'blocks' => $blocks] + $page['facts'];
            $site = self::site(glossary: $glossary);
            foreach ($analyzer->analyze($page, $site) as $finding) {
                if ($finding['code'] === 'QUALITY_TERM_NOT_IN_GLOSSARY') {
                    return $finding['detail'];
                }
            }

            return null;
        };

        // source, target and output are everyday English words, not terms.
        self::assertNull($terms(['Поле source задаёт старый адрес, target — новый, output — результат.', 'Снова source, target и output.']));
        // A plural and its singular are one term: "assets" twice and "asset" once count as one.
        self::assertSame('Not in the glossary: asset, provider, runtime.', $terms(['Сборка копирует assets, runtime и provider.', 'Ещё раз: assets, asset, runtime, provider.']));
        // A glossary entry in one form defines the other; a hyphenated entry defines its parts.
        self::assertNull($terms(['Сборка копирует assets, runtime и provider.', 'Ещё раз: assets, runtime, provider.'], ['asset' => true, 'runtime' => true, 'provider' => true]));
        self::assertSame(['hash-bound' => true, 'hash' => true, 'bound' => true, 'apply' => true], $analyzer->glossaryWords(['Hash-bound apply']));
    }

    #[Test]
    public function the_link_resolver_follows_verify_static_rules(): void
    {
        $root = new StaticLinkResolver('/');
        self::assertSame(['kind' => 'local', 'target' => 'ru/start/index.html', 'fragment' => 'шаг'], $root->resolve('ru/index.html', '/ru/start/#%D1%88%D0%B0%D0%B3'));
        self::assertSame(['kind' => 'local', 'target' => 'ru/next/index.html', 'fragment' => null], $root->resolve('ru/start/index.html', '../next/'));
        self::assertSame('external', $root->resolve('index.html', 'https://example.com/')['kind']);
        self::assertSame('outside', $root->resolve('index.html', '../../x')['kind']);
        $base = new StaticLinkResolver('/docs/');
        self::assertSame('ru/index.html', $base->resolve('index.html', '/docs/ru/')['target']);
        self::assertSame('outside', $base->resolve('index.html', '/elsewhere/')['kind']);
        self::assertSame('/docs/ru/start/', $base->url('ru/start/index.html'));
        self::assertSame('/docs/', $base->url('index.html'));
    }

    #[Test]
    public function placeholder_lines_come_from_every_template(): void
    {
        $lines = (new DocumentationStandard)->placeholderLines();
        self::assertContains('Explain one subject. Keep sentences short and use plain words.', $lines);
        self::assertContains('Одно предложение: что читатель узнает или сделает на этой странице.', $lines);
        foreach ($lines as $line) {
            self::assertFalse(str_starts_with($line, '#') || str_starts_with($line, '|') || str_starts_with($line, '- '), $line);
        }
    }

    /** @return array<string, mixed> */
    private static function page(): array
    {
        return [
            'url' => '/page/',
            'file' => 'page/index.html',
            'locale' => 'en',
            'home' => false,
            'description' => 'Build the site, check the result and fix a failed build.',
            'title' => 'Build the site',
            'tags' => [],
            'markdown' => "# Build the site\n\nText.\n",
            'profile_missing' => [],
            'profile_inferred' => false,
            'freshness' => null,
            'facts' => [
                'lang' => 'en',
                'noindex' => false,
                'headings' => [['level' => 1, 'text' => 'Build the site'], ['level' => 2, 'text' => 'Steps'], ['level' => 3, 'text' => 'Check']],
                'links' => [['href' => '/other/#part', 'text' => 'Other page']],
                'images' => [['src' => 'scheme.png', 'alt' => 'Build scheme']],
                'tables' => [true],
                'blocks' => [self::PROSE],
                'text' => self::PROSE,
                'terms' => [],
                'ids' => [],
                'navigation' => [],
            ],
        ];
    }

    /** @param array<string, true> $navigation @param array<string, int> $inbound */
    /** @param array<string, true>|null $glossary */
    private static function site(array $navigation = ['/page/' => true], array $inbound = ['/page/' => 1], bool $search = true, ?array $glossary = null): QualitySite
    {
        $files = ['index.html' => true, 'page/index.html' => true, 'other/index.html' => true, 'hidden/index.html' => true, 'lonely/index.html' => true, 'unindexed/index.html' => true];

        return new QualitySite(
            new StaticLinkResolver('/'),
            static fn (string $file): bool => isset($files[$file]),
            static fn (string $file): array => $file === 'other/index.html' ? ['part' => true] : [],
            $navigation + ['/lonely/' => true],
            $search ? ['/page/' => true, '/hidden/' => true, '/lonely/' => true, '/' => true] : null,
            ['/draft/' => true],
            $inbound + ['/hidden/' => 1],
            ['ru' => $glossary ?? ['сборка' => true, 'lock' => true]],
            (new DocumentationStandard)->placeholderLines(),
        );
    }
}
