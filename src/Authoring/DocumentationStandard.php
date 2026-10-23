<?php

declare(strict_types=1);

namespace Simai\Docara\Authoring;

/**
 * The documentation standard Docara ships by default: the page types, the
 * parts each type needs, the writing rules and the commands that check a
 * page. One source feeds the AGENTS.md that `docara init` writes, the
 * `documentation_standard` block of `docara capabilities --json`, the MCP
 * guide tool and the page templates of `docara scaffold page`.
 */
final readonly class DocumentationStandard
{
    public const SCHEMA = 'docara.documentation_standard.v1';

    /** Template languages with built-in text; other locales use English. */
    public const LANGUAGES = ['en', 'ru'];

    /** The structural signal each profile checks, in plain words. */
    private const SIGNALS = [
        'one_h1' => 'exactly one `#` title',
        'introduction' => 'an introduction paragraph under the title',
        'multiple_sections' => 'at least two `##` sections',
        'main_material' => 'at least one `##` section',
        'ordered_steps' => 'a numbered list of at least two steps',
        'structured_reference' => 'a table, a list, a code block or a component block',
        'action' => 'a link or a component that leads to the next action',
    ];

    /** Directory each page type uses in the starter skeleton and in scaffold. */
    public const DIRECTORIES = [
        'tutorial' => 'getting-started',
        'how_to' => 'how-to',
        'reference' => 'reference',
        'explanation' => 'explanation',
    ];

    /**
     * The writing rules. With a locale, the "See also" rule names the heading
     * in that locale's language; without one it names both.
     *
     * @return list<string>
     */
    public function writingRules(?string $locale = null): array
    {
        $seeAlso = match ($locale === null ? null : self::language($locale)) {
            null => 'End the page with a "See also" section ("См. также" on Russian pages) that links related pages.',
            'ru' => 'End the page with a "См. также" section that links related pages.',
            default => 'End the page with a "See also" section that links related pages.',
        };

        return [
            'One topic per page. Split a page that answers two questions.',
            'Short sentences in plain words. Explain a term the first time it appears.',
            'Task-oriented titles: name what the reader does or learns ("Publish the site", not "Publishing").',
            'Every page has a `description` in front matter: one sentence about what the reader gets.',
            $seeAlso,
            'Every image has alt text that says what the image shows.',
            'No text inside images: put words, commands and numbers in the Markdown text.',
            'Use the page type that matches the reader\'s goal and keep its required parts.',
        ];
    }

    /** @return array<string, array{purpose:string,when:string,required_parts:list<string>,sections:list<string>}> */
    public function pageTypes(): array
    {
        $when = [
            'landing' => 'An entry page of the site or a section that sends the reader on.',
            'article' => 'A page that fits no other type.',
            'tutorial' => 'A first, guided lesson from start to a working result.',
            'how_to' => 'Steps for one practical task the reader already wants to do.',
            'reference' => 'Exact facts to look up: options, fields, commands, codes.',
            'explanation' => 'Why something works the way it does, its design and limits.',
        ];
        $types = [];
        foreach ((new AuthoringProfileRegistry)->all() as $id => $profile) {
            $types[$id] = [
                'purpose' => $profile['purpose'],
                'when' => $when[$id],
                'required_parts' => array_map(static fn (string $signal): string => self::SIGNALS[$signal], $profile['structural_signals']),
                'sections' => array_values(array_filter(array_map(
                    static fn (string $line): ?string => str_starts_with($line, '## ') ? substr($line, 3) : null,
                    explode("\n", $this->body($id, 'en', 'Title')),
                ))),
            ];
        }

        return $types;
    }

    /** @return list<array{command:string,purpose:string}> */
    public function qualityCommands(string $docara = 'docara'): array
    {
        return [
            ['command' => $docara . ' validate page <route>', 'purpose' => 'Check one page: required parts of its type, links and assets.'],
            ['command' => $docara . ' validate project', 'purpose' => 'Check every page and the project registries.'],
            ['command' => $docara . ' build production', 'purpose' => 'Build the site; it prints one line when pages need attention.'],
            ['command' => $docara . ' verify-static build_production', 'purpose' => 'Check the built site for broken links and assets.'],
        ];
    }

    public function scaffoldCommand(string $docara = 'docara'): string
    {
        return $docara . ' scaffold page <route> --type <type> --title "<Title>"';
    }

    /** @return array<string, mixed> */
    public function toArray(string $docara = 'docara'): array
    {
        return [
            'schema' => self::SCHEMA,
            'summary' => 'Write every page as one page type, keep the required parts of that type and follow the writing rules; check pages with validate before finishing.',
            'page_types' => $this->pageTypes(),
            'writing_rules' => $this->writingRules(),
            'create_page' => $this->scaffoldCommand($docara),
            'quality_commands' => $this->qualityCommands($docara),
            'profile_file' => 'docara.authoring.json',
            'agents_file' => 'AGENTS.md',
        ];
    }

    /**
     * The AGENTS.md text for a project. $docara is how the project runs the
     * tool (`php vendor/bin/docara` or `docara`); $contentRoot is the default
     * locale's content directory.
     */
    public function agentsMarkdown(string $docara, string $contentRoot, string $locale): string
    {
        $lines = [
            '# Instructions for AI assistants',
            '',
            'This project is a documentation site built with Docara, a static site generator.',
            "Pages are Markdown files in `{$contentRoot}/`; site settings are in `docara.json`.",
            "Page language: `{$locale}`. Write new pages in that language.",
            'You may edit this file; Docara never overwrites it.',
            '',
            '## Documentation standard',
            '',
            'Every page has one type. Set it with `profile:` in front matter, or let a rule in',
            '`docara.authoring.json` set it by path. Each type has required parts:',
            '',
            '| Type | Use it for | Required parts |',
            '| --- | --- | --- |',
        ];
        foreach ($this->pageTypes() as $id => $type) {
            $lines[] = sprintf('| `%s` | %s | %s |', $id, $type['when'], ucfirst(implode('; ', $type['required_parts'])) . '.');
        }
        $lines[] = '';
        $lines[] = self::language($locale) === 'en'
            ? 'Sections of each type, in order:'
            : 'Sections of each type, in order (write the headings in the page language):';
        $lines[] = '';
        foreach ($this->pageTypes() as $id => $type) {
            $lines[] = sprintf('- `%s`: %s.', $id, implode(', ', $type['sections']));
        }
        $lines[] = '';
        $lines[] = 'Directories of the skeleton: `getting-started/` (tutorial), `how-to/` (how_to),';
        $lines[] = '`reference/` (reference), `explanation/` (explanation).';
        $lines[] = '';
        $lines[] = '## Writing rules';
        $lines[] = '';
        foreach ($this->writingRules($locale) as $rule) {
            $lines[] = '- ' . $rule;
        }
        $lines[] = '';
        $lines[] = '## Create a page';
        $lines[] = '';
        $lines[] = 'Start every new page from a template of its type:';
        $lines[] = '';
        $lines[] = '```bash';
        $lines[] = $this->scaffoldCommand($docara);
        $lines[] = $docara . ' scaffold page how-to/publish-the-site --type how_to --title "Publish the site"';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = 'The page gets the sections of its type, a `description` placeholder and';
        $lines[] = '`draft: true`. Replace every placeholder, then remove `draft: true` so the';
        $lines[] = 'page is published.';
        $lines[] = '';
        $lines[] = '## Check before you finish';
        $lines[] = '';
        $lines[] = 'Run these commands and fix what they report:';
        $lines[] = '';
        $lines[] = '```bash';
        foreach ($this->qualityCommands($docara) as $command) {
            $lines[] = $command['command'];
        }
        $lines[] = '```';
        $lines[] = '';
        $lines[] = '`validate page` lists missing parts as `PAGE_PROFILE_*_MISSING`. A page without';
        $lines[] = 'a type gets a guessed one (`PAGE_PROFILE_INFERRED`); set `profile:` to confirm';
        $lines[] = 'it. `PAGE_EDITORIAL_REVIEW_REQUIRED` lists questions only a reader can answer:';
        $lines[] = 'reread the page against them.';
        $lines[] = '';
        $lines[] = 'The machine-readable form of this guide: `' . $docara . ' capabilities --json`,';
        $lines[] = 'field `documentation_standard`.';

        return implode("\n", $lines) . "\n";
    }

    /**
     * AGENTS.md text for the project at $root, read from its docara.json:
     * the default locale, its content directory and how the project runs
     * Docara (a project-local Composer install or a global command).
     */
    public function agentsMarkdownForProject(string $root): string
    {
        $root = rtrim($root, '/\\');
        $site = is_file($root . '/docara.json') ? json_decode((string) file_get_contents($root . '/docara.json'), true) : null;
        $site = is_array($site) ? $site : [];
        $locale = is_string($site['default_locale'] ?? null) ? $site['default_locale'] : 'en';
        $contentRoot = $site['locales'][$locale]['content_root'] ?? $site['content_root'] ?? 'content/' . $locale;
        $docara = is_file($root . '/vendor/bin/docara') ? 'php vendor/bin/docara' : 'docara';

        return $this->agentsMarkdown($docara, trim((string) $contentRoot, '/'), $locale);
    }

    public static function language(string $locale): string
    {
        $primary = strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);

        return in_array($primary, self::LANGUAGES, true) ? $primary : 'en';
    }

    /** Full page source: front matter and body of the profile's template. */
    public function page(string $profile, string $locale, string $title, bool $draft = true): string
    {
        $language = self::language($locale);
        $encode = static fn (string $value): string => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $description = $language === 'ru'
            ? 'Одно предложение: что читатель узнает или сделает на этой странице.'
            : 'One sentence: what the reader learns or does on this page.';
        $front = "---\ntitle: {$encode($title)}\ndescription: {$encode($description)}\n" . ($draft ? "draft: true\n" : '') . "profile: {$profile}\n---\n\n";

        return $front . $this->body($profile, $language, $title);
    }

    /** Body of a page template, from the `#` title down. */
    public function body(string $profile, string $language, string $title): string
    {
        $text = self::TEMPLATES[self::language($language)][$profile] ?? null;
        if ($text === null) {
            throw new \InvalidArgumentException('Unknown page profile [' . $profile . '].');
        }

        return '# ' . $title . "\n\n" . $text;
    }

    private const TEMPLATES = [
        'en' => [
            'landing' => <<<'MD'
Say in one sentence who this page is for and what they can do here.

## Start here

- [Home](/) — replace with the first page a new reader should open.

## Next steps

- [Home](/) — replace with the page for the next most common task.

## See also

- [Home](/) — replace with a related section.

MD,
            'article' => <<<'MD'
Introduce the subject in one or two short sentences.

## Main part

Explain one subject. Keep sentences short and use plain words.

## See also

- [Home](/) — replace with a related page.

MD,
            'tutorial' => <<<'MD'
Say what the reader builds in this lesson and what they will have at the end.

## Before you start

- Name what the reader needs installed or prepared.

## Steps

1. Describe the first action. One action per step.
2. Describe the next action and what the reader sees after it.
3. Describe the last action.

## Result

Tell the reader how to check that everything worked.

## See also

- [Home](/) — replace with the next lesson or a how-to guide.

MD,
            'how_to' => <<<'MD'
Say in one sentence which task this page solves and when the reader needs it.

## Before you start

- Name what must already be in place.

## Steps

1. Do the first action. Put the exact command or button name in it.
2. Do the next action.
3. Check the result.

## Result

Describe what the reader sees when the task is done.

## See also

- [Home](/) — replace with a related guide or reference page.

MD,
            'reference' => <<<'MD'
Say in one sentence what this page lists and when the reader looks it up.

## Parameters

| Name | What it sets | Default |
| --- | --- | --- |
| `name` | One short sentence. | — |

## See also

- [Home](/) — replace with the guide that uses these parameters.

MD,
            'explanation' => <<<'MD'
Say in one or two sentences what this page explains and why it matters.

## How it works

Explain the idea in plain words. Keep to one topic.

## Why it works this way

Name the reasons and the trade-offs.

## Limitations

Say what this approach does not cover.

## See also

- [Home](/) — replace with a related guide or reference page.

MD,
        ],
        'ru' => [
            'landing' => <<<'MD'
Одним предложением скажите, для кого эта страница и что здесь можно сделать.

## С чего начать

- [Главная](/) — замените ссылкой на первую страницу для нового читателя.

## Что дальше

- [Главная](/) — замените ссылкой на следующую частую задачу.

## См. также

- [Главная](/) — замените ссылкой на связанный раздел.

MD,
            'article' => <<<'MD'
Одним-двумя короткими предложениями скажите, о чём эта страница.

## Основная часть

Расскажите об одной теме. Пишите короткими предложениями и простыми словами.

## См. также

- [Главная](/) — замените ссылкой на связанную страницу.

MD,
            'tutorial' => <<<'MD'
Скажите, что читатель сделает на этом уроке и что получит в конце.

## Что понадобится

- Перечислите, что нужно установить или подготовить.

## Шаги

1. Опишите первое действие. Одно действие — один шаг.
2. Опишите следующее действие и что читатель увидит после него.
3. Опишите последнее действие.

## Результат

Скажите, как проверить, что всё получилось.

## См. также

- [Главная](/) — замените ссылкой на следующий урок или инструкцию.

MD,
            'how_to' => <<<'MD'
Одним предложением скажите, какую задачу решает страница и когда она нужна.

## Перед началом

- Перечислите, что уже должно быть готово.

## Шаги

1. Выполните первое действие. Укажите точную команду или название кнопки.
2. Выполните следующее действие.
3. Проверьте результат.

## Результат

Опишите, что читатель увидит, когда задача решена.

## См. также

- [Главная](/) — замените ссылкой на связанную инструкцию или справочник.

MD,
            'reference' => <<<'MD'
Одним предложением скажите, что перечислено на странице и когда её открывают.

## Параметры

| Название | Что задаёт | По умолчанию |
| --- | --- | --- |
| `name` | Одно короткое предложение. | — |

## См. также

- [Главная](/) — замените ссылкой на инструкцию, где используются эти параметры.

MD,
            'explanation' => <<<'MD'
Одним-двумя предложениями скажите, что объясняет страница и почему это важно.

## Как это устроено

Объясните идею простыми словами. Держитесь одной темы.

## Почему так

Назовите причины и компромиссы.

## Ограничения

Скажите, чего этот подход не покрывает.

## См. также

- [Главная](/) — замените ссылкой на связанную инструкцию или справочник.

MD,
        ],
    ];
}
