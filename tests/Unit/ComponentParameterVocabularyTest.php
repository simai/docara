<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\ComponentCatalog\ComponentParameterModel;
use Simai\Docara\Document\ContentComponentRegistry;
use Simai\Docara\Markdown\AuthoringAttributeParser;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

/**
 * One name means one thing: the catalogue's parameters follow one
 * vocabulary (resources/component-catalog/parameter-vocabulary.json), the
 * renderers accept exactly the catalogue's parameters, and former names keep
 * rendering the same while they report MARKDOWN_PARAMETER_DEPRECATED.
 */
final class ComponentParameterVocabularyTest extends TestCase
{
    #[Test]
    public function every_parameter_name_is_in_the_vocabulary_or_a_justified_exception(): void
    {
        $vocabulary = $this->vocabulary();
        $model = ComponentParameterModel::bundled();
        $used = [];
        foreach ($model->components() as $component) {
            foreach ($model->names($component) as $name) {
                if (isset($vocabulary['vocabulary'][$name])) {
                    continue;
                }
                $exception = $vocabulary['exceptions'][$name] ?? null;
                self::assertIsArray($exception, "Parameter [$component.$name] is outside the vocabulary and has no justified exception.");
                self::assertContains($component, $exception['components'], "Parameter [$component.$name] is an exception only for: " . implode(', ', $exception['components']) . '.');
                self::assertNotSame('', trim((string) $exception['reason']));
                $used[$name][] = $component;
            }
        }
        foreach ($vocabulary['exceptions'] as $name => $exception) {
            $actual = $used[$name] ?? [];
            sort($actual);
            $expected = $exception['components'];
            sort($expected);
            self::assertSame($expected, $actual, "Exception [$name] lists components that do not use it.");
        }
    }

    #[Test]
    public function no_component_names_a_vocabulary_concept_by_a_synonym(): void
    {
        $vocabulary = $this->vocabulary();
        $synonyms = [];
        foreach ($vocabulary['synonyms'] as $canonical => $names) {
            self::assertArrayHasKey($canonical, $vocabulary['vocabulary']);
            foreach ($names as $name) {
                self::assertArrayNotHasKey($name, $vocabulary['vocabulary'], "Synonym [$name] is itself a vocabulary name.");
                $synonyms[$name] = $canonical;
            }
        }
        $model = ComponentParameterModel::bundled();
        foreach ($model->components() as $component) {
            foreach ($model->names($component) as $name) {
                if (! isset($synonyms[$name])) {
                    continue;
                }
                self::assertContains(
                    $component,
                    $vocabulary['exceptions'][$name]['components'] ?? [],
                    "Parameter [$component.$name] means [{$synonyms[$name]}]; use that name.",
                );
            }
        }
    }

    #[Test]
    public function vocabulary_names_keep_their_meaning_in_every_component(): void
    {
        $vocabulary = $this->vocabulary();
        $model = ComponentParameterModel::bundled();
        $exempt = $vocabulary['meaning_exceptions'];
        $ratioSets = [];
        foreach ($model->components() as $component) {
            foreach ($model->names($component) as $name) {
                if (isset($exempt["$component.$name"])) {
                    continue;
                }
                $values = $model->values($component, $name);
                $label = "$component.$name";
                switch ($name) {
                    case 'type':
                        self::assertNotNull($values, "$label must list its statuses.");
                        self::assertSame([], array_values(array_diff($values, $vocabulary['status_values'])), "$label must be a semantic status; a style belongs in variant.");
                        break;
                    case 'variant':
                    case 'layout':
                        self::assertNotNull($values, "$label must list its values.");
                        self::assertSame([], array_values(array_intersect($values, ['info', 'success', 'warning', 'danger'])), "$label must not carry a status; that is type.");
                        break;
                    case 'size':
                        self::assertNotNull($values, "$label must list its scale steps.");
                        self::assertSame([], array_values(array_diff($values, $vocabulary['size_scale'])), "$label must use the Framework size scale.");
                        break;
                    case 'scheme':
                        self::assertNotNull($values);
                        self::assertSame([], array_values(array_diff($values, $vocabulary['scheme_values'])), "$label must name Framework palette colours.");
                        break;
                    case 'ratio':
                        self::assertNotNull($values);
                        self::assertSame([], array_values(array_diff($values, $vocabulary['ratio_values'])), "$label must be written a/b.");
                        $ratios = array_values(array_diff($values, ['auto']));
                        sort($ratios);
                        $ratioSets[$component] = $ratios;
                        break;
                    case 'tone':
                        self::assertNotNull($values);
                        self::assertContains('default', $values, "$label must call its neutral tone default.");
                        break;
                    case 'open':
                        self::assertSame(['true', 'false'], $this->booleanValues($component, $name), "$label must be a boolean.");
                        break;
                }
            }
        }
        self::assertGreaterThanOrEqual(4, count($ratioSets));
        self::assertCount(1, array_unique(array_map('json_encode', $ratioSets)), 'Every ratio parameter must accept the same a/b values: ' . json_encode($ratioSets));
    }

    #[Test]
    public function former_names_and_values_point_at_current_ones(): void
    {
        $model = ComponentParameterModel::bundled();
        $renamed = 0;
        foreach ($model->components() as $component) {
            $names = $model->names($component);
            foreach ($model->deprecatedNames($component) as $former => $current) {
                self::assertNotContains($former, $names, "Former name [$component.$former] is still a current name.");
                self::assertContains($current, $names);
                $renamed++;
            }
            foreach ($names as $name) {
                $values = $model->values($component, $name) ?? [];
                foreach ($model->deprecatedValues($component, $name) as $former => $current) {
                    self::assertNotContains((string) $former, $values, "Former value [$component.$name=$former] is still accepted as current.");
                    self::assertContains($current, $values);
                    $renamed++;
                }
            }
        }
        self::assertSame([
            'badge' => ['type' => 'variant'],
            'button' => ['type' => 'variant'],
            'details' => ['view' => 'layout'],
            'download' => ['name' => 'title', 'size' => 'file_size'],
            'embed' => ['title' => 'label'],
            'hero' => ['variant' => 'layout'],
            'quote' => ['url' => 'href'],
            'steps' => ['view' => 'layout'],
        ], array_filter(array_combine(
            $model->components(),
            array_map(static fn (string $component): array => $model->deprecatedNames($component), $model->components()),
        )));
        self::assertSame(['normal' => 'default'], $model->deprecatedValues('logos', 'tone'));
        foreach (['image', 'figure', 'media', 'embed'] as $component) {
            self::assertSame('16/9', $model->deprecatedValues($component, 'ratio')['16x9'] ?? null, $component);
        }
        self::assertGreaterThan(10, $renamed);
    }

    #[Test]
    public function inline_component_definitions_match_the_catalogue_exactly(): void
    {
        $registry = new ContentComponentRegistry;
        $model = ComponentParameterModel::bundled();
        foreach ($registry->inlineAliases() + ['alert' => 'docara.alert'] as $alias => $id) {
            $props = $registry->definition($id)['props'];
            $catalogueNames = $model->names($alias);
            $definitionNames = array_keys($props);
            sort($catalogueNames);
            sort($definitionNames);
            self::assertSame($catalogueNames, $definitionNames, "Content component [$alias] and its catalogue entry list different parameters.");
            foreach ($props as $name => $contract) {
                if (isset($contract['enum'])) {
                    $enum = $contract['enum'];
                    $values = $model->values($alias, $name) ?? ($this->booleanValues($alias, $name) ?: null);
                    sort($enum);
                    sort($values);
                    self::assertSame($values, $enum, "[$alias.$name] values differ between the content component and the catalogue.");
                }
                $default = $model->default($alias, $name);
                if ($default !== null) {
                    self::assertSame($default, $contract['default'], "[$alias.$name] default differs between the content component and the catalogue.");
                }
            }
        }
    }

    #[Test]
    public function the_renderer_keeps_no_parameter_lists_of_its_own(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/PortableMarkdownRenderer.php');
        self::assertSame(0, preg_match_all('/assertAttributes\(\$[a-z]+,\s*\[/', $source), 'assertAttributes must read the catalogue, not a literal list.');
        // The one remaining call is inside catalogueChoice, which passes the catalogue values.
        self::assertSame(1, preg_match_all('/->attributeOneOf\(/', $source), 'Enum checks must go through catalogueChoice, which reads the catalogue values.');
        self::assertGreaterThan(20, preg_match_all("/assertAttributes\\(\\\$[a-z]+, '[a-z_]+'\\)/", $source));
    }

    #[Test]
    public function the_renderer_accepts_exactly_the_catalogue_parameters_of_every_public_block(): void
    {
        $root = dirname(__DIR__, 2);
        $model = ComponentParameterModel::bundled();
        $renderer = new PortableMarkdownRenderer;
        $probed = 0;
        $withoutParameters = [];
        foreach ($model->components() as $component) {
            if ($model->names($component) === []) {
                // These declare no parameters. kbd and component_index reject
                // any attribute; columns, cta, features, html, promo, showcase
                // and tabs have always ignored an attribute block, and
                // rejecting one now would stop sites that build today. Listed
                // exactly, so a parameter added to the catalogue for one of
                // them must also reach its renderer.
                $withoutParameters[] = $component;

                continue;
            }
            $example = $root . '/resources/component-catalog/examples/' . $model->componentId($component) . '.md';
            if (! is_file($example) || in_array($component, ['example', 'code'], true)) {
                continue;
            }
            $block = $this->firstBlock((string) file_get_contents($example), $component);
            if ($block === null) {
                continue;
            }
            foreach ($model->names($component) as $name) {
                $value = $model->default($component, $name) ?? ($model->values($component, $name)[0] ?? 'x');
                self::assertNotSame(
                    'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN',
                    $this->renderError($renderer, $this->withAttribute($block, $name, $value)),
                    "The renderer rejects catalogue parameter [$component.$name].",
                );
            }
            self::assertSame(
                'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN',
                $this->renderError($renderer, $this->withAttribute($block, 'zz_unknown', 'x')),
                "The renderer accepts [$component.zz_unknown], which the catalogue does not list.",
            );
            $probed++;
        }
        self::assertGreaterThanOrEqual(15, $probed);
        self::assertSame(['columns', 'component_index', 'cta', 'features', 'html', 'kbd', 'promo', 'showcase', 'tabs'], $withoutParameters);

        foreach (['image' => "![Знак](https://example.com/a.png){%s}\n", 'quote' => "> Цитата\n{author=\"Автор\" %s}\n"] as $component => $template) {
            foreach ($model->names($component) as $name) {
                $value = $model->default($component, $name) ?? ($model->values($component, $name)[0] ?? 'https://example.com/');
                self::assertNotSame('MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN', $this->renderError($renderer, sprintf($template, $name === 'author' ? 'source="S"' : $name . '="' . $value . '"')), "[$component.$name]");
            }
            self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN', $this->renderError($renderer, sprintf($template, 'zz_unknown="x"')));
        }
    }

    #[Test]
    public function former_names_and_values_render_identically_to_the_current_ones(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $link = '[Скачать](https://example.com/a.zip)';
        $pairs = [
            [":badge[Новое]{type=main scheme=success}\n", ":badge[Новое]{variant=main scheme=success}\n"],
            [":button[Начать]{href=/ru/ type=outline size=2}\n", ":button[Начать]{href=/ru/ variant=outline size=2}\n"],
            [":::details {view=lines open=true}\n## Заголовок\n\nТекст.\n:::\n", ":::details {layout=lines open=true}\n## Заголовок\n\nТекст.\n:::\n"],
            [":::steps {view=list}\n1. Один\n2. Два\n:::\n", ":::steps {layout=list}\n1. Один\n2. Два\n:::\n"],
            [":::hero {variant=centered}\n# Заголовок\n\nОписание.\n:::\n", ":::hero {layout=centered}\n# Заголовок\n\nОписание.\n:::\n"],
            [":::download {name=\"a.zip\" size=\"2 МБ\"}\n$link\n:::\n", ":::download {title=\"a.zip\" file_size=\"2 МБ\"}\n$link\n:::\n"],
            [":::embed {provider=external title=\"Карта\" ratio=4x3}\n[Показать](https://example.com/map)\n:::\n", ":::embed {provider=external label=\"Карта\" ratio=4/3}\n[Показать](https://example.com/map)\n:::\n"],
            [":::embed {provider=external ratio=21X9}\n[Показать](https://example.com/map)\n:::\n", ":::embed {provider=external ratio=21/9}\n[Показать](https://example.com/map)\n:::\n"],
            ["![Знак](https://example.com/a.png){ratio=1x1 fit=contain}\n", "![Знак](https://example.com/a.png){ratio=1/1 fit=contain}\n"],
            ["> Цитата\n{author=\"Автор\" url=\"https://example.com/\"}\n", "> Цитата\n{author=\"Автор\" href=\"https://example.com/\"}\n"],
            [":::logos {tone=normal}\n- Один\n- Два\n:::\n", ":::logos {tone=default}\n- Один\n- Два\n:::\n"],
            [":::logos {tone=normal}\n- Один\n- Два\n:::\n", ":::logos\n- Один\n- Два\n:::\n"],
        ];
        foreach ($pairs as [$former, $current]) {
            self::assertSame($renderer->render($current), $renderer->render($former), $former);
        }
        self::assertStringContainsString('data-view="lines"', $renderer->render($pairs[2][1]));
        self::assertStringContainsString('data-tone="normal"', $renderer->render($pairs[10][1]));
        self::assertStringContainsString('aspect-21x9', $renderer->render(":::media {ratio=21/9}\n## Медиа\n\nТекст.\n\n![Знак](https://example.com/a.png)\n:::\n"));
    }

    #[Test]
    public function a_former_name_next_to_its_current_name_is_a_duplicate(): void
    {
        try {
            (new AuthoringAttributeParser)->parse('type=main variant=tonal', 'badge');
            self::fail('A former and a current name together must be rejected.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_DUPLICATE', $exception->errorCode);
            self::assertStringContainsString('[variant]', $exception->getMessage());
        }
        // Alert keeps type: it is its semantic status.
        self::assertSame(['type' => 'warning'], (new AuthoringAttributeParser)->parse('type=warning', 'alert'));
    }

    /** @return array<string, mixed> */
    private function vocabulary(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/resources/component-catalog/parameter-vocabulary.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /** @return list<string> */
    private function booleanValues(string $component, string $name): array
    {
        $paths = glob(dirname(__DIR__, 2) . '/resources/component-catalog/{typed,inline,native}/*.json', GLOB_BRACE) ?: [];
        foreach ($paths as $path) {
            $entry = json_decode((string) file_get_contents($path), true);
            $scope = $entry['name'] ?? ltrim((string) ($entry['authoring']['call'] ?? ''), ':');
            if ($scope !== $component && (ComponentParameterModel::NATIVE_SCOPES[$entry['id']] ?? null) !== $component) {
                continue;
            }
            foreach ($entry['authoring']['parameters'] as $parameter) {
                if ($parameter['name'] === $name && $parameter['type'] === 'boolean') {
                    return ['true', 'false'];
                }
            }
        }

        return [];
    }

    private function firstBlock(string $markdown, string $component): ?string
    {
        if (preg_match('/^(?<fence>:{3,})' . preg_quote($component, '/') . '(?:\s+\{[^\n]*\})?\s*\n(?<body>.*?)\n\k<fence>\s*$/ms', $markdown, $match) !== 1) {
            return null;
        }

        return $match['fence'] . $component . "\n" . $match['body'] . "\n" . $match['fence'] . "\n";
    }

    private function withAttribute(string $block, string $name, string $value): string
    {
        return preg_replace('/^(:{3,}[a-z_]+)\n/', '$1 {' . $name . '="' . addcslashes($value, '"\\') . '"}' . "\n", $block, 1) ?? $block;
    }

    private function renderError(PortableMarkdownRenderer $renderer, string $markdown): ?string
    {
        try {
            $renderer->render($markdown);

            return null;
        } catch (PortableConfigurationException $exception) {
            return $exception->errorCode;
        }
    }
}
