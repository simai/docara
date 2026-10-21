<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

/**
 * The hero's padding steps: lg and xl are Framework spacing steps 3 and 4;
 * 2xl is a fluid vertical padding drawn by the shell stylesheet between two
 * Framework spacing tokens.
 */
final class HeroPaddingTest extends TestCase
{
    private const FLUID_RULE = '.docara-padding-2xl{padding-block:clamp(var(--sf-d2),var(--sf-c2) + 6vw,var(--sf-e4))}';

    #[Test]
    public function lg_and_xl_keep_their_framework_steps(): void
    {
        $lg = $this->render(":::hero {variant=compact}\n# Hero\n\nDescription.\n:::");
        self::assertStringContainsString(' p-top-3 p-bottom-3">', $lg);
        $xl = $this->render(":::hero {variant=centered}\n# Hero\n\nDescription.\n:::");
        self::assertStringContainsString(' p-top-4 p-bottom-4">', $xl);
        self::assertSame($xl, $this->render(":::hero {variant=centered padding=xl}\n# Hero\n\nDescription.\n:::"));
        self::assertStringNotContainsString('docara-padding-2xl', $lg . $xl);
    }

    #[Test]
    public function two_xl_puts_the_fluid_padding_on_the_hero_container_for_every_variant(): void
    {
        foreach (['split', 'centered', 'compact'] as $variant) {
            $html = $this->render(":::hero {variant=$variant padding=2xl}\n# Hero\n\nDescription.\n:::");
            self::assertStringStartsWith('<section data-docara-block="hero" data-variant="' . $variant . '"', $html);
            self::assertMatchesRegularExpression(
                '/<div data-docara-container class="container m-inline-auto grid [^"]* gap-4 items-cross-center docara-padding-2xl">/',
                $html,
                $variant,
            );
            self::assertDoesNotMatchRegularExpression('/\bp-(top|bottom)-\d\b/', $html, $variant);
        }
    }

    #[Test]
    public function two_xl_works_with_a_background_image(): void
    {
        $html = $this->render(":::hero {media=background padding=2xl}\n# Hero\n\nDescription.\n\n![](/assets/docara-screen.png)\n:::");
        self::assertSame(1, substr_count($html, 'data-docara-surface-background'));
        self::assertStringContainsString(
            'class="docara-surface__content relative container m-inline-auto grid grid-col-1 gap-4 items-cross-center docara-padding-2xl"',
            $html,
        );
    }

    #[Test]
    public function two_xl_works_with_a_background_effect(): void
    {
        $html = $this->render(":::hero {variant=centered padding=2xl effect=aurora-curtain}\n# Hero\n\nDescription.\n:::");
        self::assertStringStartsWith('<section data-docara-block="hero" data-sf-effect="aurora-curtain" data-variant="centered"', $html);
        // The padding sits on the container inside the effect area; the
        // section stays the positioned box the effect draws in.
        self::assertStringContainsString('items-cross-center docara-padding-2xl">', $html);

        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertStringContainsString('[data-docara-block="hero"][data-sf-effect]{position:relative}', $css);
        // The pause button takes its own row under the hero content instead of
        // floating over the corner, where it covered the hero media on phones.
        self::assertStringContainsString('[data-docara-block="hero"]>.docara-effect-toggle,[data-docara-block="hero"]>.docara-effect-toggle[hidden]{position:relative;z-index:1;display:grid;inline-size:max-content;margin-block:0 var(--sf-space-1);margin-inline:auto var(--sf-space-1);', $css);
        self::assertStringContainsString('[data-docara-block="hero"]>.docara-effect-toggle[hidden]{visibility:hidden}', $css);
        self::assertStringContainsString('@media (prefers-reduced-motion:reduce){[data-docara-block="hero"]>.docara-effect-toggle[hidden]{display:none}}', $css);
        self::assertStringNotContainsString('position:absolute;inset-block-end:var(--sf-space-1)', $css);
    }

    #[Test]
    public function the_shell_stylesheet_grows_the_padding_between_framework_tokens(): void
    {
        $root = dirname(__DIR__, 2);
        $css = (string) file_get_contents($root . '/resources/portable/declarative-shell.css');
        self::assertSame(1, substr_count($css, self::FLUID_RULE));

        $core = (string) file_get_contents(
            $root . '/resources/portable/vendor/simai-framework/runtime/32c7a64e457583eaf5a1860590001217a86b5af9/distr/core/css/core.css',
        );
        foreach (['--sf-d2: 3rem', '--sf-c2: 1.5rem', '--sf-e4: 7rem'] as $token) {
            self::assertStringContainsString($token, $core);
        }
    }

    #[Test]
    public function a_landing_page_keeps_the_two_xl_clamp_on_a_narrow_screen(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');

        // The narrow-screen landing step tightens section and lg/xl hero
        // padding, but never a 2xl hero: its clamp already starts at 3rem on
        // a phone, and the step used to cut it to --sf-space-2.
        $narrow = $this->mediaBlock($css, '@media(max-width:50rem){');
        self::assertStringContainsString(
            '.docara-landing [data-docara-container]:not(.docara-padding-2xl){padding-block:var(--sf-space-2)}',
            $narrow,
        );

        // No rule in the shell sets the vertical padding of a container that
        // could carry docara-padding-2xl, except the clamp itself.
        $rulesOnly = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $rulesOnly, $rules, PREG_SET_ORDER);
        $checked = 0;
        foreach ($rules as [, $selectors, $declarations]) {
            if (preg_match('/(^|;)\s*padding(-block|-top|-bottom)?\s*:/', $declarations) !== 1) {
                continue;
            }
            foreach (explode(',', $selectors) as $selector) {
                $selector = trim($selector);
                if (! str_contains($selector, '[data-docara-container]') && ! str_contains($selector, 'docara-padding-2xl')) {
                    continue;
                }
                $checked++;
                if ($selector === '.docara-padding-2xl') {
                    continue;
                }
                self::assertStringContainsString(':not(.docara-padding-2xl)', $selector, $selector . '{' . $declarations . '}');
            }
        }
        self::assertGreaterThanOrEqual(2, $checked);

        // The landing hero still renders the clamp class on its container.
        $html = $this->render(":::hero {variant=centered padding=2xl effect=aurora-curtain}\n# Hero\n\nDescription.\n:::");
        self::assertMatchesRegularExpression('/<div data-docara-container [^>]*class="[^"]*\bdocara-padding-2xl"/', $html);
    }

    #[Test]
    public function an_unknown_padding_and_a_surface_two_xl_are_refused(): void
    {
        foreach ([":::hero {padding=3xl}\n# Hero\n\nDescription.\n:::", ":::surface {padding=2xl}\nText.\n:::"] as $source) {
            try {
                $this->render($source);
                self::fail('Expected a refusal for ' . $source);
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function the_catalogue_definition_lists_the_same_values_as_the_renderer(): void
    {
        $definition = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/resources/component-catalog/typed/docara.hero.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $padding = array_values(array_filter(
            $definition['authoring']['parameters'],
            static fn (array $parameter): bool => $parameter['name'] === 'padding',
        ));
        self::assertSame(['lg', 'xl', '2xl'], $padding[0]['values']);
    }

    private function mediaBlock(string $css, string $opening): string
    {
        $start = strpos($css, $opening);
        self::assertNotFalse($start, $opening);
        $depth = 0;
        for ($index = $start; $index < strlen($css); $index++) {
            if ($css[$index] === '{') {
                $depth++;
            } elseif ($css[$index] === '}' && --$depth === 0) {
                return substr($css, $start, $index - $start + 1);
            }
        }
        self::fail('Unclosed ' . $opening);
    }

    private function render(string $source): string
    {
        $site = dirname(__DIR__, 2) . '/docs/site';

        return (new PortableMarkdownRenderer)->render($source, $site, $site . '/content/ru/components/hero.md');
    }
}
