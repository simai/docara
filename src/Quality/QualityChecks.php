<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

/**
 * The checks of the documentation quality report: a stable code, the group it
 * scores in, its severity and a short hint. One table feeds the analyzer, the
 * score, the JSON report and the HTML page, so a code never drifts from its
 * group or severity.
 *
 * Severity is "warning" (a reader is likely to stumble) or "advice" (worth a
 * look). Neither fails a build; only `docara qa report --min-score` or
 * `quality.min_score` in docara.authoring.json turns the score into a gate.
 */
final class QualityChecks
{
    public const GROUPS = ['completeness', 'freshness', 'findability', 'readability', 'accessibility', 'links'];

    public const WARNING = 'warning';

    public const ADVICE = 'advice';

    /** @var array<string, array{group:string,severity:string,checks:string,hint:string}> */
    public const ALL = [
        'QUALITY_PROFILE_PART_MISSING' => [
            'group' => 'completeness', 'severity' => self::WARNING,
            'checks' => 'The page lacks a part its profile requires (the PAGE_PROFILE_*_MISSING checks of validate page).',
            'hint' => 'Add the missing part or set the profile that matches the page; run docara validate page <route>.',
        ],
        'QUALITY_DESCRIPTION_MISSING' => [
            'group' => 'completeness', 'severity' => self::WARNING,
            'checks' => 'The page has no description in front matter or in its <page>.page.json.',
            'hint' => 'Add description: one sentence about what the reader gets from the page.',
        ],
        'QUALITY_DESCRIPTION_LENGTH' => [
            'group' => 'completeness', 'severity' => self::ADVICE,
            'checks' => 'The description is shorter than 40 or longer than 200 characters.',
            'hint' => 'Write one sentence of 40 to 200 characters.',
        ],
        'QUALITY_PAGE_STUB' => [
            'group' => 'completeness', 'severity' => self::WARNING,
            'checks' => 'The page has fewer than 80 words of text outside code.',
            'hint' => 'Finish the page, merge it into a related page or mark it draft: true.',
        ],
        'QUALITY_PLACEHOLDER_LEFT' => [
            'group' => 'completeness', 'severity' => self::WARNING,
            'checks' => 'A line of a page template from docara scaffold page is still in the page.',
            'hint' => 'Replace every template line with real text.',
        ],
        'QUALITY_DRAFT_LINKED' => [
            'group' => 'completeness', 'severity' => self::WARNING,
            'checks' => 'The page links to a draft page, which the build does not publish.',
            'hint' => 'Publish the draft or remove the link.',
        ],
        'QUALITY_SOURCE_CHANGED' => [
            'group' => 'freshness', 'severity' => self::WARNING,
            'checks' => 'A tracked source of the page changed since the last review (documentation tracking).',
            'hint' => 'Update the page, then accept the review with docara documentation plan and apply.',
        ],
        'QUALITY_NOT_IN_NAVIGATION' => [
            'group' => 'findability', 'severity' => self::ADVICE,
            'checks' => 'No menu of the site links to the page.',
            'hint' => 'Show the page in the menu, or link it from its section page.',
        ],
        'QUALITY_NOT_IN_SEARCH' => [
            'group' => 'findability', 'severity' => self::ADVICE,
            'checks' => 'Site search is on, but the page is not in the search index.',
            'hint' => 'Remove search.indexed: false for the page, or exclude it from the report if it is a service page.',
        ],
        'QUALITY_ORPHAN_PAGE' => [
            'group' => 'findability', 'severity' => self::WARNING,
            'checks' => 'No other page links to the page from its text.',
            'hint' => 'Link the page from the pages a reader opens before it.',
        ],
        'QUALITY_NO_RELATED_LINKS' => [
            'group' => 'findability', 'severity' => self::ADVICE,
            'checks' => 'The text links to no other page of the site and there is no "See also" section.',
            'hint' => 'Link related pages in the text or add a "See also" section.',
        ],
        'QUALITY_TOPIC_UNCLEAR' => [
            'group' => 'findability', 'severity' => self::ADVICE,
            'checks' => 'The page has no tags, and no word of four letters or more of its title appears, in any form, in its description or first paragraph.',
            'hint' => 'Add tags, or name the topic of the title in the description.',
        ],
        'QUALITY_SENTENCES_LONG' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'Sentences of the prose average more than 20 words; tables and lists of code names are not prose.',
            'hint' => 'Split long sentences; one idea per sentence.',
        ],
        'QUALITY_SENTENCE_TOO_LONG' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'A sentence of the prose has more than 40 words; tables and lists of code names are not prose.',
            'hint' => 'Split the longest sentence into two or three.',
        ],
        'QUALITY_PAGE_TOO_LONG' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'The page has more than 2500 words of text outside code.',
            'hint' => 'Split the page by task or topic.',
        ],
        'QUALITY_NO_SUBHEADINGS' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'The page has more than 600 words and no ## section.',
            'hint' => 'Add ## sections so the reader can scan the page.',
        ],
        'QUALITY_HEADING_TOO_DEEP' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'The page uses heading level 5 or 6.',
            'hint' => 'Keep headings to four levels; split the page if it needs more.',
        ],
        'QUALITY_LATIN_HEAVY' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'In a language not written in Latin script, more than 25% of the words outside code are Latin.',
            'hint' => 'Translate the terms or explain them; put names of files, fields and commands in code.',
        ],
        'QUALITY_TERM_NOT_IN_GLOSSARY' => [
            'group' => 'readability', 'severity' => self::ADVICE,
            'checks' => 'Three or more Latin terms used twice or more are not defined in the site glossary; everyday English words (source, output, title) are not terms.',
            'hint' => 'Define the terms in the glossary page or replace them with plain words.',
        ],
        'QUALITY_IMAGE_ALT_MISSING' => [
            'group' => 'accessibility', 'severity' => self::WARNING,
            'checks' => 'An image has no alt text.',
            'hint' => 'Write alt text that says what the image shows: ![What it shows](image.png).',
        ],
        'QUALITY_LINK_TEXT_GENERIC' => [
            'group' => 'accessibility', 'severity' => self::WARNING,
            'checks' => 'A link says only "here", "click here", "здесь", "тут" or similar, or has no text.',
            'hint' => 'Name the target in the link text: [Publish the site](/publish/).',
        ],
        'QUALITY_HEADING_ORDER' => [
            'group' => 'accessibility', 'severity' => self::WARNING,
            'checks' => 'The page does not start with exactly one level-1 heading.',
            'hint' => 'Give the page one # title before any other heading.',
        ],
        'QUALITY_HEADING_LEVEL_SKIPPED' => [
            'group' => 'accessibility', 'severity' => self::WARNING,
            'checks' => 'A heading skips a level, for example ## followed by ####.',
            'hint' => 'Go down one level at a time.',
        ],
        'QUALITY_TABLE_HEADER_MISSING' => [
            'group' => 'accessibility', 'severity' => self::WARNING,
            'checks' => 'A table has no header row.',
            'hint' => 'Give every table a header row.',
        ],
        'QUALITY_LANG_MISSING' => [
            'group' => 'accessibility', 'severity' => self::WARNING,
            'checks' => 'The built page has no lang attribute, or it differs from the page locale.',
            'hint' => 'Check the locale of the page in docara.json; the build writes lang from it.',
        ],
        'QUALITY_LINK_BROKEN' => [
            'group' => 'links', 'severity' => self::WARNING,
            'checks' => 'A link in the text points to a page or file the build does not contain.',
            'hint' => 'Fix the address or restore the target page.',
        ],
        'QUALITY_ANCHOR_MISSING' => [
            'group' => 'links', 'severity' => self::WARNING,
            'checks' => 'A link points to a #fragment that does not exist on the target page.',
            'hint' => 'Link an existing heading; a renamed heading changes its anchor.',
        ],
    ];

    /** @return array{group:string,severity:string,checks:string,hint:string} */
    public static function get(string $code): array
    {
        return self::ALL[$code] ?? throw new \InvalidArgumentException('Unknown quality check [' . $code . '].');
    }
}
