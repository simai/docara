<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Authoring\AuthoringContract;
use Simai\Docara\Authoring\PageTemplateCheck;
use Simai\Docara\Portable\PortableConfigurationException;
use Tests\TestCase;

final class PageTemplateTest extends TestCase
{
    private const COMPONENT = [
        'description' => 'A component reference page.',
        'profile' => 'reference',
        'sections' => [
            ['id' => 'example', 'heading' => ['ru' => 'Пример', 'en' => 'Example'], 'required' => true, 'example' => true],
            ['id' => 'markup', 'heading' => ['ru' => 'Разметка', 'en' => 'Markup'], 'required' => true, 'hint' => 'The minimal correct markup.'],
            ['id' => 'variants', 'heading' => ['ru' => 'Варианты', 'en' => 'Variants'], 'example_per_subsection' => true],
            ['id' => 'classes', 'heading' => ['ru' => 'Классы', 'en' => 'Classes'], 'required' => true, 'generated' => true],
            ['id' => 'see-also', 'heading' => ['ru' => 'См. также', 'en' => 'See also']],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $this->tmp);
        $this->authoring(['templates' => ['component' => self::COMPONENT], 'rules' => [['match' => 'components/**', 'template' => 'component']]]);
    }

    #[Test]
    public function a_page_that_follows_its_template_has_no_template_findings(): void
    {
        $this->filesystem->put($this->tmpPath('content/ru/components/button.md'), $this->page());

        $inspection = (new DiscoveryService)->inspect($this->tmp, 'page', '/ru/components/button/')->toArray()['data'];

        self::assertSame(['id' => 'component', 'source' => 'path_rule'], array_slice($inspection['template'], 0, 2));
        self::assertSame('reference', $inspection['profile']['id']);
        self::assertSame('template', $inspection['profile']['source']);
        self::assertSame([], $this->templateCodes($inspection['diagnostics']));
    }

    #[Test]
    public function every_kind_of_mismatch_is_named_with_its_section(): void
    {
        $markdown = <<<'MD'
# Кнопка

Кнопка запускает действие.

## Разметка

Текст.

## Пример

Без живого примера.

## Варианты

### Вид

Без примера.

## Классы

Написано руками.

## История

Лишний раздел.
MD;
        $findings = (new PageTemplateCheck)->check('component', self::COMPONENT, $markdown, 'ru', [], 'button.md');
        $codes = array_column($findings, 'code');

        self::assertContains('PAGE_TEMPLATE_SECTION_UNKNOWN', $codes);
        self::assertContains('PAGE_TEMPLATE_SECTION_ORDER', $codes);
        self::assertContains('PAGE_TEMPLATE_EXAMPLE_MISSING', $codes);
        self::assertContains('PAGE_TEMPLATE_SUBSECTION_EXAMPLE_MISSING', $codes);
        self::assertContains('PAGE_TEMPLATE_GENERATED_SECTION_EDITED', $codes);
        self::assertSame('История', $findings[array_search('PAGE_TEMPLATE_SECTION_UNKNOWN', $codes, true)]['heading']);
        self::assertSame('Вид', $findings[array_search('PAGE_TEMPLATE_SUBSECTION_EXAMPLE_MISSING', $codes, true)]['heading']);
        self::assertSame('warning', $findings[0]['severity']);
    }

    #[Test]
    public function a_required_section_is_missing_unless_the_page_omits_it(): void
    {
        $markdown = "# Кнопка\n\nКнопка.\n\n## Пример\n\n:::example\n```html\n<b>x</b>\n```\n:::\n\n## Разметка\n\nТекст.\n";
        $check = new PageTemplateCheck;

        $missing = $check->check('component', self::COMPONENT, $markdown, 'ru', [], 'button.md');
        self::assertSame(['PAGE_TEMPLATE_SECTION_MISSING'], array_column($missing, 'code'));
        self::assertSame('classes', $missing[0]['section']);
        self::assertSame([], $check->check('component', self::COMPONENT, $markdown, 'ru', ['classes'], 'button.md'));
    }

    #[Test]
    public function headings_inside_code_and_anchors_do_not_confuse_the_sections(): void
    {
        $markdown = "# Кнопка\n\nКнопка.\n\n## Пример {#example}\n\n:::example\n```markdown\n## Не раздел\n```\n:::\n\n## Разметка\n\nТекст.\n\n## Классы\n\n<!-- docara:generated -->\n| Класс |\n<!-- /docara:generated -->\n";

        self::assertSame([], (new PageTemplateCheck)->check('component', self::COMPONENT, $markdown, 'ru', [], 'button.md'));
    }

    #[Test]
    public function a_locale_without_headings_is_advised_not_checked(): void
    {
        $template = self::COMPONENT;
        $template['sections'][0]['heading'] = ['en' => 'Example'];

        $findings = (new PageTemplateCheck)->check('component', $template, "# A\n\n## Something\n", 'ru', [], 'a.md');

        self::assertSame([['code' => 'PAGE_TEMPLATE_LOCALE_UNDECLARED', 'status' => 'review_required', 'severity' => 'advisory', 'template' => 'component', 'locale' => 'ru']], $findings);
    }

    #[Test]
    public function an_open_template_accepts_other_sections(): void
    {
        $template = self::COMPONENT + ['other_sections' => true];
        $markdown = $this->body() . "\n## Заметки\n\nТекст.\n";

        self::assertSame([], (new PageTemplateCheck)->check('component', $template, $markdown, 'ru', [], 'a.md'));
    }

    #[Test]
    public function front_matter_names_the_template_and_the_quality_report_counts_findings(): void
    {
        $this->filesystem->put($this->tmpPath('content/ru/button.md'), "---\ntemplate: component\n---\n\n# Кнопка\n\nКнопка.\n\n## Разметка\n\nТекст.\n");

        $page = array_values(array_filter((new PageInspectionService)->profileReport($this->tmp), static fn (array $page): bool => $page['relative'] === 'button.md'))[0];

        self::assertSame('component', $page['template']);
        self::assertSame(['PAGE_TEMPLATE_SECTION_MISSING', 'PAGE_TEMPLATE_SECTION_MISSING'], array_column($page['template_findings'], 'code'));
    }

    #[Test]
    public function the_authoring_file_refuses_broken_templates_and_rules(): void
    {
        $cases = [
            'AUTHORING_TEMPLATE_UNKNOWN' => ['rules' => [['match' => 'a/**', 'template' => 'missing']]],
            'AUTHORING_RULE_INVALID' => ['rules' => [['match' => 'a/**']]],
            'AUTHORING_TEMPLATE_INVALID' => ['templates' => ['t' => ['sections' => [['id' => 'a', 'heading' => 'A'], ['id' => 'a', 'heading' => 'B']]]]],
        ];
        foreach ($cases as $code => $data) {
            $this->authoring($data);
            try {
                AuthoringContract::load($this->tmp);
                self::fail("Expected $code.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $code);
            }
        }
        $this->authoring(['templates' => ['t' => ['sections' => [['id' => 'a', 'heading' => 'A', 'generated' => true, 'example' => true]]]]]);
        try {
            AuthoringContract::load($this->tmp);
            self::fail('A generated section cannot ask for an example.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('AUTHORING_TEMPLATE_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function an_unknown_omitted_section_stops_the_inspection(): void
    {
        $this->filesystem->put($this->tmpPath('content/ru/components/button.md'), "---\ntemplate_omit: [nothing]\n---\n" . $this->body());

        try {
            (new DiscoveryService)->inspect($this->tmp, 'page', '/ru/components/button/');
            self::fail('Expected AUTHORING_TEMPLATE_OMIT_INVALID.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('AUTHORING_TEMPLATE_OMIT_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function a_page_scaffolded_from_a_template_has_its_sections_in_order(): void
    {
        $service = new ScaffoldService;
        $plan = $service->plan($this->tmp, 'page', 'components/sample-badge', ['locale' => 'ru', 'title' => 'Значок', 'template' => 'component'])->toArray();
        $service->apply($this->tmp, $plan['data']['plan_id']);

        $page = (string) file_get_contents($this->tmpPath('content/ru/components/sample-badge.md'));
        self::assertStringContainsString("profile: reference\ntemplate: component\n", $page);
        preg_match_all('/^## (.+)$/m', $page, $headings);
        self::assertSame(['Пример', 'Разметка', 'Варианты', 'Классы', 'См. также'], $headings[1]);
        self::assertStringContainsString('<!-- The minimal correct markup. -->', $page);
        self::assertStringContainsString("## Классы\n\n<!-- docara:generated -->\n<!-- /docara:generated -->\n", $page);

        $codes = $this->templateCodes((new DiscoveryService)->inspect($this->tmp, 'page', '/ru/components/sample-badge/')->toArray()['data']['diagnostics']);
        self::assertSame(['PAGE_TEMPLATE_EXAMPLE_MISSING'], $codes, 'Only the live example is left for the author.');
    }

    #[Test]
    public function a_scaffold_refuses_an_unknown_template(): void
    {
        try {
            (new ScaffoldService)->plan($this->tmp, 'page', 'components/x', ['locale' => 'ru', 'title' => 'X', 'template' => 'nope']);
            self::fail('Expected AUTHORING_TEMPLATE_UNKNOWN.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('AUTHORING_TEMPLATE_UNKNOWN', $exception->errorCode);
        }
    }

    /** @param array<string, mixed> $data */
    private function authoring(array $data): void
    {
        $this->filesystem->put($this->tmpPath('docara.authoring.json'), json_encode(['schema' => 'docara.authoring.v1'] + $data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private function page(): string
    {
        return "---\ndescription: Кнопка запускает действие на странице.\n---\n" . $this->body();
    }

    private function body(): string
    {
        return <<<'MD'
# Кнопка

Кнопка запускает действие.

## Пример

:::example
```html
<button class="sf-button">Отправить</button>
```
:::

## Разметка

Достаточно тега `button` с классом.

## Варианты

### Вид

:::example
```html
<button class="sf-button sf-button--outline">Отправить</button>
```
:::

## Классы

<!-- docara:generated -->
| Класс | CSS |
| --- | --- |
| `sf-button` | `display: inline-flex` |
<!-- /docara:generated -->
MD;
    }

    /**
     * @param  list<array<string, mixed>>  $diagnostics
     * @return list<string>
     */
    private function templateCodes(array $diagnostics): array
    {
        return array_values(array_filter(array_column($diagnostics, 'code'), static fn (string $code): bool => str_starts_with($code, 'PAGE_TEMPLATE_')));
    }
}
