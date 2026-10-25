<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

/**
 * Runs the checks of QualityChecks on one page. The input is plain data (the
 * facts HtmlPageReader read from the built page plus what the sources say),
 * so every check can be tested without a build.
 */
final class QualityPageAnalyzer
{
    public const DESCRIPTION_MIN = 40;

    public const DESCRIPTION_MAX = 200;

    public const STUB_WORDS = 80;

    public const LONG_PAGE_WORDS = 2500;

    public const SUBHEADING_WORDS = 600;

    public const AVERAGE_SENTENCE_WORDS = 20;

    public const MAX_SENTENCE_WORDS = 40;

    public const LATIN_SHARE = 0.25;

    public const GLOSSARY_TERMS = 3;

    public const GLOSSARY_TERM_USES = 2;

    /** Link texts that do not name their target, compared in lowercase without punctuation. */
    public const GENERIC_LINK_TEXTS = [
        'here', 'click here', 'this link', 'link', 'this page', 'read more', 'more', 'learn more', 'click', 'this', 'go',
        'здесь', 'тут', 'сюда', 'там', 'ссылка', 'по ссылке', 'эта ссылка', 'этой ссылке', 'подробнее', 'далее',
        'читать далее', 'читать', 'перейти', 'нажмите здесь', 'нажмите', 'жмите', 'вот', 'это', 'эта страница',
    ];

    /** English function words: an English phrase quoted in the text is not a term. */
    public const FUNCTION_WORDS = [
        'the', 'and', 'for', 'with', 'from', 'into', 'this', 'that', 'these', 'are', 'was', 'not', 'but', 'you', 'your',
        'its', 'has', 'have', 'can', 'will', 'all', 'any', 'one', 'our', 'out', 'per', 'via', 'off', 'yes', 'too',
    ];

    /**
     * Everyday English words, in their singular form. They have their usual
     * dictionary meaning in technical text (source, output, title), so they
     * are not terms a glossary must define; QUALITY_LATIN_HEAVY still counts
     * them as Latin. Words with a software meaning of their own (runtime,
     * namespace, asset, slot, hash) are not on this list.
     */
    public const COMMON_ENGLISH_WORDS = [
        'about', 'absolute', 'acceptance', 'access', 'action', 'add', 'address', 'advice', 'after', 'all', 'allowed',
        'also', 'alt', 'another', 'auto', 'base', 'before', 'between', 'block', 'body', 'brand', 'browser', 'build',
        'button', 'card', 'change', 'check', 'child', 'children', 'class', 'close', 'code', 'collision', 'color',
        'column', 'common', 'compare', 'configuration', 'consumer', 'content', 'contract', 'control', 'copy', 'create',
        'current', 'data', 'date', 'declared', 'default', 'definition', 'delete', 'dependency', 'deployment', 'depth',
        'description', 'disabled', 'document', 'download', 'each', 'edit', 'element', 'empty', 'enabled', 'engine',
        'error', 'escape', 'evidence', 'exact', 'example', 'exit', 'external', 'false', 'feature', 'field', 'file',
        'first', 'folder', 'footer', 'form', 'format', 'fragment', 'full', 'group', 'header', 'help', 'here', 'hidden',
        'home', 'host', 'identity', 'image', 'include', 'index', 'input', 'internal', 'isolated', 'item', 'key',
        'label', 'language', 'last', 'level', 'line', 'link', 'list', 'live', 'local', 'location', 'logo', 'main',
        'major', 'marker', 'max', 'member', 'menu', 'message', 'min', 'minor', 'missing', 'mode', 'more', 'name', 'new',
        'next', 'none', 'note', 'null', 'number', 'off', 'old', 'only', 'open', 'operation', 'option', 'optional',
        'order', 'origin', 'other', 'output', 'owner', 'package', 'page', 'pair', 'part', 'path', 'pointer', 'policy',
        'present', 'presentation', 'previous', 'primary', 'private', 'product', 'project', 'public', 'query', 'quote',
        'read', 'reference', 'region', 'relative', 'release', 'remove', 'report', 'required', 'requirement', 'result',
        'root', 'row', 'run', 'same', 'search', 'secondary', 'section', 'security', 'select', 'semantic', 'set', 'show',
        'signal', 'simple', 'size', 'small', 'source', 'start', 'state', 'status', 'step', 'stop', 'storage',
        'structure', 'structured', 'style', 'suggestion', 'switch', 'table', 'tag', 'target', 'test', 'text', 'theme',
        'title', 'top', 'true', 'trust', 'trusted', 'type', 'unknown', 'update', 'validation', 'value', 'version',
        'view', 'warning', 'width', 'word', 'write',
    ];

    /** Headings that open a "See also" section, in lowercase. */
    public const SEE_ALSO = ['see also', 'related', 'related pages', 'см. также', 'смотрите также', 'см также', 'по теме', 'связанные страницы'];

    /** @var array<string, int|float|null> */
    private array $metrics = [];

    public function __construct(private readonly TextStatistics $text = new TextStatistics) {}

    /**
     * Measurements of the page analyzed last: words, sentences, Latin share
     * and links. The report keeps them so a reader can see why a check fired.
     *
     * @return array<string, int|float|null>
     */
    public function metrics(): array
    {
        return $this->metrics;
    }

    /**
     * @param  array{
     *   url:string, file:string, locale:string, home:bool,
     *   description:?string, title:string, tags:list<string>, markdown:string,
     *   profile_missing:list<string>, profile_inferred:bool,
     *   freshness:?list<array{source:string,key:string,status:string}>,
     *   facts:array<string,mixed>
     * }  $page
     * @return list<array{code:string,group:string,severity:string,count:int,detail:string}>
     */
    public function analyze(array $page, QualitySite $site): array
    {
        $facts = $page['facts'];
        $findings = [];
        $add = static function (string $code, string $detail, int $count = 1, ?string $severity = null) use (&$findings): void {
            $check = QualityChecks::get($code);
            $findings[] = ['code' => $code, 'group' => $check['group'], 'severity' => $severity ?? $check['severity'], 'count' => $count, 'detail' => $detail];
        };
        $words = $this->text->wordCount($facts['text']);

        // Completeness.
        if ($page['profile_missing'] !== []) {
            $add('QUALITY_PROFILE_PART_MISSING', 'Missing: ' . implode(', ', $page['profile_missing']) . ($page['profile_inferred'] ? ' (profile inferred)' : '') . '.', count($page['profile_missing']), $page['profile_inferred'] ? QualityChecks::ADVICE : null);
        }
        $description = trim((string) $page['description']);
        if ($description === '') {
            $add('QUALITY_DESCRIPTION_MISSING', 'No description in front matter or in the page file.');
        } elseif (mb_strlen($description) < self::DESCRIPTION_MIN || mb_strlen($description) > self::DESCRIPTION_MAX) {
            $add('QUALITY_DESCRIPTION_LENGTH', sprintf('The description has %d characters.', mb_strlen($description)));
        }
        if ($words < self::STUB_WORDS) {
            $add('QUALITY_PAGE_STUB', sprintf('%d words outside code.', $words));
        }
        $placeholders = $this->placeholders($page['markdown'] . "\n" . $description, $site->placeholders);
        if ($placeholders !== []) {
            $add('QUALITY_PLACEHOLDER_LEFT', 'Template text: "' . $placeholders[0] . '".', count($placeholders));
        }

        // Links, drafts and the link graph.
        $broken = $anchors = $drafts = [];
        $related = 0;
        foreach ($facts['links'] as $link) {
            $resolved = $site->links->resolve($page['file'], $link['href']);
            if ($resolved['kind'] === 'external') {
                continue;
            }
            $url = $this->normalizedUrl($link['href']);
            if ($resolved['kind'] === 'outside') {
                $broken[] = $link['href'];

                continue;
            }
            $file = $site->file((string) $resolved['target']);
            if ($file === null) {
                if ($url !== null && isset($site->drafts[$url])) {
                    $drafts[] = $link['href'];
                } else {
                    $broken[] = $link['href'];
                }

                continue;
            }
            if ($resolved['fragment'] !== null && str_ends_with($file, '.html') && ! isset($site->ids($file)[$resolved['fragment']])) {
                $anchors[] = $link['href'];
            }
            if ($file !== $page['file'] && str_ends_with($file, '.html')) {
                $related++;
            }
        }
        if ($drafts !== []) {
            $add('QUALITY_DRAFT_LINKED', 'Links to drafts: ' . $this->sample($drafts) . '.', count($drafts));
        }
        if ($broken !== []) {
            $add('QUALITY_LINK_BROKEN', 'Missing targets: ' . $this->sample($broken) . '.', count($broken));
        }
        if ($anchors !== []) {
            $add('QUALITY_ANCHOR_MISSING', 'Missing anchors: ' . $this->sample($anchors) . '.', count($anchors));
        }

        // Freshness.
        if ($page['freshness'] !== null && $page['freshness'] !== []) {
            $items = array_map(static fn (array $item): string => $item['source'] . ':' . $item['key'] . ' (' . $item['status'] . ')', $page['freshness']);
            $add('QUALITY_SOURCE_CHANGED', 'Sources to review: ' . $this->sample($items) . '.', count($items));
        }

        // Findability.
        if (! isset($site->navigation[$page['url']])) {
            $add('QUALITY_NOT_IN_NAVIGATION', 'No menu links to ' . $page['url'] . '.');
        }
        if ($site->search !== null && ! isset($site->search[$page['url']])) {
            $add('QUALITY_NOT_IN_SEARCH', 'The search index has no entry for ' . $page['url'] . '.');
        }
        if (! $page['home'] && ($site->inbound[$page['url']] ?? 0) === 0) {
            $add('QUALITY_ORPHAN_PAGE', 'No other page links here from its text.');
        }
        $headings = array_map(static fn (array $heading): string => mb_strtolower(trim($heading['text'], " \t:")), $facts['headings']);
        if ($related === 0 && array_intersect($headings, self::SEE_ALSO) === []) {
            $add('QUALITY_NO_RELATED_LINKS', 'No link to another page and no "See also" section.');
        }
        // The description and the first paragraph together introduce the
        // topic. A title without a word of four letters or more ("Код")
        // gives nothing to compare, so the check does not apply.
        $titleWords = $this->text->topicWords($page['title']);
        $introduction = $this->text->topicWords($description . ' ' . ($facts['blocks'][0] ?? ''));
        if ($page['tags'] === [] && $titleWords !== [] && ! TextStatistics::sharesTopicWord($titleWords, $introduction)) {
            $add('QUALITY_TOPIC_UNCLEAR', 'No tags, and neither the description nor the first paragraph repeats a word of the title "' . $page['title'] . '".');
        }

        // Readability. Only prose has sentences: a block that is a list of
        // code names, such as an index of icon names, is left out.
        $sentences = [];
        foreach ($facts['blocks'] as $block) {
            if (! $this->text->isCodeList($block)) {
                array_push($sentences, ...$this->text->sentenceLengths($block));
            }
        }
        if ($sentences !== []) {
            $average = array_sum($sentences) / count($sentences);
            if ($average > self::AVERAGE_SENTENCE_WORDS) {
                $add('QUALITY_SENTENCES_LONG', sprintf('Sentences average %.1f words.', $average));
            }
            $longest = max($sentences);
            if ($longest > self::MAX_SENTENCE_WORDS) {
                $add('QUALITY_SENTENCE_TOO_LONG', sprintf('The longest sentence has %d words.', $longest), count(array_filter($sentences, static fn (int $length): bool => $length > self::MAX_SENTENCE_WORDS)));
            }
        }
        if ($words > self::LONG_PAGE_WORDS) {
            $add('QUALITY_PAGE_TOO_LONG', sprintf('%d words outside code.', $words));
        }
        $levels = array_column($facts['headings'], 'level');
        if ($words > self::SUBHEADING_WORDS && ! in_array(2, $levels, true)) {
            $add('QUALITY_NO_SUBHEADINGS', sprintf('%d words and no ## section.', $words));
        }
        $deep = count(array_filter($levels, static fn (int $level): bool => $level >= 5));
        if ($deep > 0) {
            $add('QUALITY_HEADING_TOO_DEEP', sprintf('%d heading(s) at level 5 or 6.', $deep), $deep);
        }
        if (TextStatistics::usesNonLatinScript($page['locale'])) {
            $share = $this->text->latinShare($facts['text']);
            if ($share > self::LATIN_SHARE) {
                $add('QUALITY_LATIN_HEAVY', sprintf('%d%% of the words outside code are Latin.', (int) floor($share * 100 + 0.5)));
            }
            $glossary = $site->glossaries[$page['locale']] ?? null;
            if ($glossary !== null) {
                $undefined = $this->undefinedTerms(implode(' ', $facts['blocks']), $glossary);
                if (count($undefined) >= self::GLOSSARY_TERMS) {
                    $add('QUALITY_TERM_NOT_IN_GLOSSARY', 'Not in the glossary: ' . $this->sample($undefined) . '.', count($undefined));
                }
            }
        }

        // Accessibility.
        $missingAlt = array_filter($facts['images'], static fn (array $image): bool => $image['alt'] === null || trim($image['alt']) === '');
        if ($missingAlt !== []) {
            $add('QUALITY_IMAGE_ALT_MISSING', 'Images without alt: ' . $this->sample(array_column($missingAlt, 'src')) . '.', count($missingAlt));
        }
        $generic = [];
        foreach ($facts['links'] as $link) {
            $text = trim((string) preg_replace('/[\p{P}\s]+/u', ' ', mb_strtolower($link['text'])));
            if ($text === '' || in_array($text, self::GENERIC_LINK_TEXTS, true) || in_array(mb_strtolower(trim($link['text'])), self::GENERIC_LINK_TEXTS, true)) {
                $generic[] = $link['text'] === '' ? '(no text) ' . $link['href'] : '"' . $link['text'] . '"';
            }
        }
        if ($generic !== []) {
            $add('QUALITY_LINK_TEXT_GENERIC', 'Link texts: ' . $this->sample($generic) . '.', count($generic));
        }
        $h1 = count(array_filter($levels, static fn (int $level): bool => $level === 1));
        if ($h1 !== 1 || ($levels !== [] && $levels[0] !== 1)) {
            $add('QUALITY_HEADING_ORDER', sprintf('%d level-1 heading(s)%s.', $h1, $levels !== [] && $levels[0] !== 1 ? ', the first heading is level ' . $levels[0] : ''));
        }
        $skipped = [];
        $previous = null;
        foreach ($facts['headings'] as $heading) {
            if ($previous !== null && $heading['level'] > $previous + 1) {
                $skipped[] = 'h' . $previous . ' → h' . $heading['level'] . ' "' . $heading['text'] . '"';
            }
            $previous = $heading['level'];
        }
        if ($skipped !== []) {
            $add('QUALITY_HEADING_LEVEL_SKIPPED', 'Skipped: ' . $this->sample($skipped) . '.', count($skipped));
        }
        $headerless = count(array_filter($facts['tables'], static fn (bool $header): bool => ! $header));
        if ($headerless > 0) {
            $add('QUALITY_TABLE_HEADER_MISSING', sprintf('%d table(s) without a header row.', $headerless), $headerless);
        }
        $lang = $facts['lang'];
        if ($lang === null || strtolower(explode('-', $lang)[0]) !== strtolower(explode('-', str_replace('_', '-', $page['locale']))[0])) {
            $add('QUALITY_LANG_MISSING', $lang === null ? 'No lang attribute.' : 'lang="' . $lang . '" for locale ' . $page['locale'] . '.');
        }

        $this->metrics = [
            'words' => $words,
            'sentences' => count($sentences),
            'average_sentence_words' => $sentences === [] ? null : round(array_sum($sentences) / count($sentences), 1),
            'longest_sentence_words' => $sentences === [] ? null : max($sentences),
            'latin_share' => TextStatistics::usesNonLatinScript($page['locale']) ? round($this->text->latinShare($facts['text']), 3) : null,
            'links_to_pages' => $related,
            'inbound_links' => $site->inbound[$page['url']] ?? 0,
        ];

        usort($findings, static fn (array $a, array $b): int => [array_search($a['group'], QualityChecks::GROUPS, true), $a['code']] <=> [array_search($b['group'], QualityChecks::GROUPS, true), $b['code']]);

        return $findings;
    }

    /**
     * URLs of other built HTML files the page links to from its text, for
     * the inbound link count of each page.
     *
     * @param  list<array{href:string,text:string}>  $links
     * @return list<string> build-relative HTML files
     */
    public function linkedFiles(string $file, array $links, QualitySite $site): array
    {
        $targets = [];
        foreach ($links as $link) {
            $resolved = $site->links->resolve($file, $link['href']);
            if ($resolved['kind'] !== 'local') {
                continue;
            }
            $target = $site->file((string) $resolved['target']);
            if ($target !== null && $target !== $file && str_ends_with($target, '.html')) {
                $targets[$target] = true;
            }
        }

        return array_keys($targets);
    }

    /**
     * Lowercase words of a glossary page's terms: the words of its bold
     * text, definition terms, table headers and first table column, and the
     * parts of hyphenated words.
     *
     * @param  list<string>  $terms
     * @return array<string, true>
     */
    public function glossaryWords(array $terms): array
    {
        $words = [];
        foreach ($terms as $term) {
            foreach ($this->text->words(mb_strtolower($term)) as $word) {
                $words[$word] = true;
                // "hash-bound" also defines "hash", "dry-run" defines "dry" and "run".
                foreach (preg_split('/[\'’-]/u', $word) ?: [] as $part) {
                    if ($part !== '') {
                        $words[$part] = true;
                    }
                }
            }
        }

        return $words;
    }

    /**
     * Latin words of the prose (paragraphs and list items, not tables or
     * headings) that start with a lowercase letter, are at least three
     * letters long, appear at least twice, are not English function words
     * or everyday English words, and are not in the glossary.
     * Capitalised words (names such as Docara or GitHub) and acronyms are not
     * terms for this check. A plural and its singular ("assets", "asset")
     * are one term, and a glossary entry in either form defines both.
     *
     * @param  array<string, true>  $glossary
     * @return list<string>
     */
    private function undefinedTerms(string $text, array $glossary): array
    {
        $counts = [];
        foreach ($this->text->words($text) as $word) {
            if (mb_strlen($word) < 3 || ! TextStatistics::isLatinWord($word) || preg_match('/^\p{Ll}/u', $word) !== 1 || in_array($word, self::FUNCTION_WORDS, true)) {
                continue;
            }
            $singular = TextStatistics::englishSingular($word);
            if (in_array($singular, self::COMMON_ENGLISH_WORDS, true) || in_array($word, self::COMMON_ENGLISH_WORDS, true)) {
                continue;
            }
            $counts[$singular] = ($counts[$singular] ?? 0) + 1;
        }
        $defined = [];
        foreach (array_keys($glossary) as $entry) {
            $defined[(string) $entry] = true;
            $defined[TextStatistics::englishSingular((string) $entry)] = true;
        }
        $terms = [];
        foreach ($counts as $word => $count) {
            if ($count >= self::GLOSSARY_TERM_USES && ! isset($defined[(string) $word])) {
                $terms[(string) $word] = $count;
            }
        }
        uksort($terms, static fn (string $a, string $b): int => [$terms[$b], $a] <=> [$terms[$a], $b]);

        return array_keys($terms);
    }

    /** @param list<string> $placeholders @return list<string> */
    private function placeholders(string $markdown, array $placeholders): array
    {
        $found = [];
        // Code shows a template on purpose (a page about templates); only
        // prose can be a leftover.
        $markdown = (string) preg_replace('/^(`{3,}|~{3,})[^\n]*\n.*?^\1\h*$/msu', '', str_replace(["\r\n", "\r"], "\n", $markdown));
        $haystack = (string) preg_replace('/\s+/u', ' ', $markdown);
        foreach ($placeholders as $line) {
            if (str_contains($haystack, $line)) {
                $found[] = $line;
            }
        }

        return $found;
    }

    private function normalizedUrl(string $href): ?string
    {
        $path = preg_split('/[?#]/', $href, 2)[0] ?? '';
        if ($path === '' || ! str_starts_with($path, '/')) {
            return null;
        }

        return rtrim(rawurldecode($path), '/') . '/';
    }

    /** @param list<string> $items */
    private function sample(array $items): string
    {
        $items = array_values(array_unique($items));
        $shown = array_slice($items, 0, 3);

        return implode(', ', $shown) . (count($items) > 3 ? sprintf(' and %d more', count($items) - 3) : '');
    }
}
