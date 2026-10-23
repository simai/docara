<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\File\Filesystem;
use Simai\Docara\I18n\UiCopy;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\EffectsCatalogue;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSiteBuilder;

/**
 * The hero's background effect: the SIMAI Framework declarative mount
 * (data-sf-effect, -theme, -settings) and the site's effects catalogue.
 */
final class HeroEffectTest extends TestCase
{
    private const CATALOGUE = 'https://cdn.jsdelivr.net/gh/simai/ui-effects@0123456789abcdef0123456789abcdef01234567/';

    #[Test]
    public function a_hero_without_an_effect_is_unchanged(): void
    {
        $html = $this->render(":::hero {media=none}\n# Hero\n\nDescription.\n:::");
        self::assertStringNotContainsString('data-sf-effect', $html);
        self::assertStringStartsWith('<section data-docara-block="hero" data-variant="split" data-docara-width="full"', $html);
    }

    #[Test]
    public function the_effect_attributes_land_on_the_hero_section_escaped(): void
    {
        $html = $this->render(":::hero {media=none effect=\"aurora-curtain\" effect_theme=\"dark\" effect_settings='{\"speed\": 80, \"label\": \"<b>&\"}'}\n# Hero\n\nDescription.\n:::");
        self::assertStringStartsWith(
            '<section data-docara-block="hero" data-sf-effect="aurora-curtain" data-sf-effect-theme="dark"'
            . ' data-sf-effect-settings="{&quot;speed&quot;:80,&quot;label&quot;:&quot;&lt;b&gt;&amp;&quot;}" data-variant="split"',
            $html,
        );
        self::assertSame(1, substr_count($html, 'data-sf-effect='));

        $address = self::CATALOGUE . 'effects/aurora-curtain/effect.json';
        $byAddress = $this->render(":::hero {effect=\"$address\"}\n# Hero\n\nDescription.\n:::");
        self::assertStringStartsWith('<section data-docara-block="hero" data-sf-effect="' . $address . '" data-variant', $byAddress);
        self::assertStringNotContainsString('data-sf-effect-theme', $byAddress);
        self::assertStringNotContainsString('data-sf-effect-settings', $byAddress);
        self::assertStringContainsString(
            'data-sf-effect-settings="{}"',
            $this->render(":::hero {effect=aurora-curtain effect_settings='{}'}\n# Hero\n\nDescription.\n:::"),
        );
    }

    #[Test]
    public function a_running_hero_effect_can_be_paused_with_a_localized_control(): void
    {
        self::assertSame('Pause background animation', UiCopy::optionalDefaults('en')['effects.pause']);
        self::assertSame('Play background animation', UiCopy::optionalDefaults('en')['effects.play']);
        self::assertSame('Остановить фоновую анимацию', UiCopy::optionalDefaults('ru')['effects.pause']);
        self::assertSame('Запустить фоновую анимацию', UiCopy::optionalDefaults('ru')['effects.play']);

        // The shell places the hero's effect through the Framework to hold its
        // handle, drives pause() and resume(), keeps the choice and hides the
        // control with reduced motion. The behaviour is checked in a browser.
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        foreach ([
            "'[data-docara-block=\"hero\"][data-sf-effect]'",
            'api.placeEffect(entry.hero,options(entry.hero))',
            'entry.handle.pause()',
            'entry.handle.resume()',
            "'docara.effects.paused'",
            "'(prefers-reduced-motion: reduce)'",
            "message(paused?'effects.play':'effects.pause')",
        ] as $needle) {
            self::assertStringContainsString($needle, $shell);
        }
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertStringContainsString(
            '[data-docara-block="hero"][data-sf-effect].sf-effect-area{background:var(--sf-surface-0);color:var(--sf-on-surface)}',
            $css,
        );
    }

    #[Test]
    public function invalid_effect_inputs_are_refused(): void
    {
        foreach ([
            'effect="Aurora"' => 'MARKDOWN_HERO_EFFECT_INVALID',
            'effect="a"' => 'MARKDOWN_HERO_EFFECT_INVALID',
            'effect="http://cdn.example/effects/a/effect.json"' => 'MARKDOWN_HERO_EFFECT_INVALID',
            'effect="https://cdn.example/effects/a/manifest.json"' => 'MARKDOWN_HERO_EFFECT_INVALID',
            'effect="https://cdn.example/effect.json?x=1"' => 'MARKDOWN_HERO_EFFECT_INVALID',
            'effect="javascript:alert(1)"' => 'MARKDOWN_HERO_EFFECT_INVALID',
            'effect=aurora-curtain effect_theme=dim' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            "effect=aurora-curtain effect_settings='[1,2]'" => 'MARKDOWN_HERO_EFFECT_SETTINGS_INVALID',
            "effect=aurora-curtain effect_settings='{speed: 80}'" => 'MARKDOWN_HERO_EFFECT_SETTINGS_INVALID',
            "effect=aurora-curtain effect_settings='\"text\"'" => 'MARKDOWN_HERO_EFFECT_SETTINGS_INVALID',
            'effect_theme=dark' => 'MARKDOWN_HERO_EFFECT_REQUIRED',
            'effect=aurora-curtain media=background' => 'MARKDOWN_HERO_EFFECT_MEDIA_CONFLICT',
        ] as $attributes => $code) {
            try {
                $this->render(":::hero {{$attributes}}\n# Hero\n\nDescription.\n\n![](/assets/docara-screen.png)\n:::");
                self::fail("[$attributes] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertStringContainsString($code, $exception->getMessage() . ' ' . $exception->errorCode, $attributes);
            }
        }
    }

    #[Test]
    public function the_site_catalogue_is_validated_and_written_to_the_boot_config(): void
    {
        self::assertNull(EffectsCatalogue::fromSite([]));
        $catalogue = EffectsCatalogue::fromSite(['effects' => [
            'catalogue' => self::CATALOGUE,
            'index' => 'catalogue/index.json',
            'index_sha256' => str_repeat('ab', 32),
        ]]);
        self::assertInstanceOf(EffectsCatalogue::class, $catalogue);
        self::assertSame(
            '<script data-docara-effects>window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.effects='
            . '{"catalogue":"' . self::CATALOGUE . '","index":"catalogue/index.json","indexSha256":"' . str_repeat('ab', 32) . '"};</script>',
            $catalogue->bootScript(),
        );
        foreach ([
            [['catalogue' => 'http://cdn.example/'], 'EFFECTS_CATALOGUE_INVALID'],
            [['catalogue' => 'https://cdn.example/effects'], 'EFFECTS_CATALOGUE_INVALID'],
            [['catalogue' => self::CATALOGUE, 'index' => '../index.json'], 'EFFECTS_INDEX_INVALID'],
            [['catalogue' => self::CATALOGUE, 'index_sha256' => 'abc'], 'EFFECTS_INDEX_SHA256_INVALID'],
        ] as [$effects, $code]) {
            try {
                EffectsCatalogue::fromSite(['effects' => $effects]);
                self::fail("[$code] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }

        $hero = '<section data-docara-block="hero" data-sf-effect="aurora-curtain">';
        EffectsCatalogue::assertPage(null, '<section data-docara-block="hero">', 'Page');
        EffectsCatalogue::assertPage($catalogue, $hero, 'Page');
        foreach ([
            [null, $hero, 'EFFECTS_CATALOGUE_REQUIRED'],
            [$catalogue, '<section data-docara-block="hero" data-sf-effect="https://elsewhere.example/a/effect.json">', 'EFFECTS_OUTSIDE_CATALOGUE'],
        ] as [$site, $html, $code]) {
            try {
                EffectsCatalogue::assertPage($site, $html, 'Page');
                self::fail("[$code] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function a_site_build_mounts_the_effect_and_configures_the_catalogue(): void
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir() . '/docara-hero-effect-' . bin2hex(random_bytes(8));
        self::assertTrue($files->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $root));
        $root = (string) realpath($root);
        file_put_contents(
            $root . '/content/ru/components/hero.md',
            "# Hero contract\n\n:::hero {effect=aurora-curtain effect_theme=auto effect_settings='{\"speed\":80}'}\n# Effect\n\nDescription.\n:::\n",
        );

        try {
            try {
                (new PortableSiteBuilder($files, new PortableMarkdownRenderer))->build($root, $root . '/build_test');
                self::fail('A hero effect without a catalogue was built.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('EFFECTS_CATALOGUE_REQUIRED', $exception->errorCode);
            }

            $site = json_decode((string) file_get_contents($root . '/docara.json'), true, 512, JSON_THROW_ON_ERROR);
            $site['effects'] = ['catalogue' => self::CATALOGUE];
            file_put_contents($root . '/docara.json', json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            (new PortableSiteBuilder($files, new PortableMarkdownRenderer))->build($root, $root . '/build_test');

            $html = (string) file_get_contents($root . '/build_test/ru/components/hero/index.html');
            self::assertStringContainsString(
                // Attribute values are double-quoted on every libxml version.
                '<section data-docara-block="hero" data-sf-effect="aurora-curtain" data-sf-effect-theme="auto" data-sf-effect-settings="{&quot;speed&quot;:80}"',
                $html,
            );
            $boot = strpos($html, '<script data-docara-effects>window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.effects={"catalogue":"' . self::CATALOGUE . '"};</script>');
            self::assertIsInt($boot);
            // After the Framework boot configuration, which assigns over it.
            self::assertGreaterThan((int) strpos($html, 'data-docara-framework-asset="simai.framework.boot"'), $boot);
            self::assertStringContainsString(
                '<script data-docara-effects>',
                (string) file_get_contents($root . '/build_test/ru/index.html'),
            );
        } finally {
            $files->deleteDirectory($root);
        }
    }

    private function render(string $source): string
    {
        $site = dirname(__DIR__, 2) . '/docs/site';

        return (new PortableMarkdownRenderer)->render($source, $site, $site . '/content/ru/components/hero.md');
    }
}
