<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\ComponentCatalog\ParameterDeprecation;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Markdown\ParameterDeprecationScanner;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

/**
 * A page that still uses former parameter names builds as before. `docara
 * validate page` names each use with its line and replacement; the build
 * prints at most one summary line and never fails on it.
 */
final class ParameterDeprecationDiagnosticsTest extends TestCase
{
    private const PAGE = <<<'MD'
---
title: "Former names"
description: "A page that still uses former component parameter names."
---

# Former names

The build is :badge[ready]{type=main scheme=success}.

:::details {view=lines}
## More

Hidden text.
:::

![Docara](https://example.com/mark.png){ratio=16x9}
MD;

    #[Test]
    public function validate_page_reports_each_former_name_with_line_and_replacement_without_failing(): void
    {
        $this->project();
        $result = (new ValidationService)->validate($this->tmp, 'page', '/former-names/')->toArray();

        self::assertSame('success', $result['status']);
        self::assertSame(0, $result['data']['errors']);
        $deprecated = array_values(array_filter(
            $result['data']['checks'],
            static fn (array $check): bool => $check['code'] === 'MARKDOWN_PARAMETER_DEPRECATED',
        ));
        self::assertSame([
            [8, 'badge', 'type', 'variant'],
            [10, 'details', 'view', 'layout'],
            [16, 'image', '16x9', '16/9'],
        ], array_map(
            static fn (array $check): array => [$check['line'], $check['component'], $check['deprecated'], $check['replacement']],
            $deprecated,
        ));
        foreach ($deprecated as $check) {
            self::assertSame('warning', $check['severity']);
            self::assertSame('review_required', $check['status']);
            self::assertStringContainsString('[' . $check['replacement'] . ']', $check['message']);
        }

        $project = array_filter(
            (new ValidationService)->validate($this->tmp, 'project')->toArray()['data']['checks'],
            static fn (array $check): bool => $check['code'] === 'MARKDOWN_PARAMETER_DEPRECATED' && $check['subject'] === '/former-names/',
        );
        self::assertCount(3, $project);
        $summary = (new PageInspectionService)->parameterDeprecations($this->tmp);
        self::assertContains('/former-names/', $summary['pages']);
        self::assertGreaterThanOrEqual(3, $summary['uses']);
    }

    #[Test]
    public function the_build_succeeds_and_prints_one_summary_line(): void
    {
        $this->project();
        $application = ApplicationFactory::create($this->tmp);
        $tester = new CommandTester($application->find('build'));
        $status = $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertSame(0, $status, $output);
        self::assertSame(1, substr_count($output, 'MARKDOWN_PARAMETER_DEPRECATED'), $output);
        $summary = (new PageInspectionService)->parameterDeprecations($this->tmp);
        self::assertStringContainsString(sprintf(
            'Deprecated component parameters: %d use(s) on %d page(s) render as before; run "docara validate page %s"',
            $summary['uses'],
            count($summary['pages']),
            $summary['pages'][0],
        ), $output);
        $html = (string) file_get_contents($this->tmpPath('build_local/former-names/index.html'));
        self::assertStringContainsString('sf-badge--main', $html);
        self::assertStringContainsString('data-view="lines"', $html);
        self::assertStringContainsString('aspect-16x9', $html);

        file_put_contents($this->tmpPath('content/en/former-names.md'), str_replace(
            ['{type=main', '{view=lines}', '{ratio=16x9}'],
            ['{variant=main', '{layout=lines}', '{ratio=16/9}'],
            self::PAGE,
        ));
        $current = new CommandTester(ApplicationFactory::create($this->tmp)->find('build'));
        self::assertSame(0, $current->execute([]));
        self::assertNotContains('/former-names/', (new PageInspectionService)->parameterDeprecations($this->tmp)['pages']);
        self::assertSame($html, (string) file_get_contents($this->tmpPath('build_local/former-names/index.html')));
    }

    #[Test]
    public function the_scanner_reports_each_former_name_with_its_line_and_skips_code(): void
    {
        $page = <<<'MD'
---
title: Страница
---
# Страница

Метка :badge[Новое]{type=main}, а в коде `:badge[Код]{type=main}` не считается.

```markdown
:::details {view=lines}
```

:::example
```markdown
:::steps {view=list}
1. Один
```
:::

![Знак](a.png){ratio=16x9}

> Цитата
{author="Автор" url="https://example.com/"}
MD;
        $found = array_map(
            static fn (array $item): array => [$item['line'], $item['deprecation']->component, $item['deprecation']->deprecated, $item['deprecation']->replacement],
            (new ParameterDeprecationScanner)->scan($page),
        );
        self::assertSame([
            [6, 'badge', 'type', 'variant'],
            [14, 'steps', 'view', 'layout'],
            [19, 'image', '16x9', '16/9'],
            [22, 'quote', 'url', 'href'],
        ], $found);
        $message = (new ParameterDeprecation('steps', 'name', 'layout', 'view', 'layout'))->message();
        self::assertStringContainsString('[view]', $message);
        self::assertStringContainsString('[layout]', $message);
        self::assertSame([], (new ParameterDeprecationScanner)->scan(":badge[Новое]{variant=main}\n:::details {layout=lines}\n## А\n\nБ\n:::\n"));
    }

    private function project(): void
    {
        (new PortableProjectInitializer(new Filesystem))->initialize($this->tmp, 'minimal', 'en');
        file_put_contents($this->tmpPath('content/en/former-names.md'), self::PAGE);
    }
}
