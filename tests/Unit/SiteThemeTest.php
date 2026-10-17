<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkThemePalette;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\SiteTheme;
use Symfony\Component\Process\ExecutableFinder;

final class SiteThemeTest extends TestCase
{
    #[Test]
    public function a_site_without_the_setting_has_no_theme(): void
    {
        self::assertNull(SiteTheme::fromSite(['schema' => 'docara.site.v1']));
    }

    #[Test]
    public function the_colour_is_kept_as_lower_case_rrggbb_with_optional_hue_and_chroma(): void
    {
        self::assertSame(
            ['color' => '#0a7d4f', 'hue' => null, 'chroma' => null],
            SiteTheme::fromSite(['theme' => ['color' => '#0A7D4F']])?->input(),
        );
        self::assertSame(
            ['color' => '#aabbcc', 'hue' => 200, 'chroma' => 40],
            SiteTheme::fromSite(['theme' => ['color' => '#abc', 'hue' => 200, 'chroma' => 40]])?->input(),
        );
    }

    /** @return array<string, array{mixed, string}> */
    public static function invalidThemes(): array
    {
        return [
            'a colour name' => [['color' => 'green'], 'THEME_COLOR_INVALID'],
            'no hash' => [['color' => '0a7d4f'], 'THEME_COLOR_INVALID'],
            'five digits' => [['color' => '#0a7d4'], 'THEME_COLOR_INVALID'],
            'not hex' => [['color' => '#0g7d4f'], 'THEME_COLOR_INVALID'],
            'with alpha' => [['color' => '#0a7d4fff'], 'THEME_COLOR_INVALID'],
            'no colour' => [['hue' => 120], 'THEME_COLOR_INVALID'],
            'a string' => ['#0a7d4f', 'THEME_INVALID'],
            'an unknown key' => [['color' => '#0a7d4f', 'tone' => 40], 'THEME_INVALID'],
            'hue out of range' => [['color' => '#0a7d4f', 'hue' => 361], 'THEME_HUE_INVALID'],
            'hue not whole' => [['color' => '#0a7d4f', 'hue' => 12.5], 'THEME_HUE_INVALID'],
            'chroma out of range' => [['color' => '#0a7d4f', 'chroma' => 151], 'THEME_CHROMA_INVALID'],
        ];
    }

    #[Test]
    #[DataProvider('invalidThemes')]
    public function an_invalid_theme_is_rejected_with_its_code(mixed $theme, string $code): void
    {
        try {
            SiteTheme::fromSite(['theme' => $theme]);
            self::fail('The theme was accepted.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
            self::assertStringContainsString('theme', $exception->getMessage());
        }
    }

    #[Test]
    public function the_site_schema_accepts_the_setting_and_bounds_hue_and_chroma(): void
    {
        $site = ['schema' => 'docara.site.v1', 'preset' => 'docs', 'framework_lock' => 'simai-framework.lock.json'];
        (new SchemaRepository)->assertValid([...$site, 'theme' => ['color' => '#0a7d4f', 'hue' => 161, 'chroma' => 85]], 'site.schema.json');

        foreach ([
            ['color' => '#0a7d4f', 'hue' => 400],
            ['color' => '#0a7d4f', 'chroma' => -1],
            ['color' => '#0a7d4f', 'extra' => true],
            ['hue' => 120],
            ['color' => 5],
        ] as $theme) {
            try {
                (new SchemaRepository)->assertValid([...$site, 'theme' => $theme], 'site.schema.json');
                self::fail('The schema accepted ' . json_encode($theme));
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function the_framework_palette_gives_the_frameworks_own_css_for_a_colour(): void
    {
        $result = $this->palette()->stylesheet($this->lock(), $this->theme(['color' => '#0a7d4f']));
        [$comment, $css] = explode("\n", $result['content'], 2);

        // SF.ThemePalette.themeToCss(themeFromColor('#0a7d4f')) of ui-source
        // b06420f5f (the release's source), run outside Docara.
        self::assertSame('ad93e86244561be8a41a172bdd0524a37575656476e2b13914ea2de28d5a450e', hash('sha256', $css));
        self::assertStringContainsString('  --sf-primary-40: #006d44;', $css);
        self::assertStringContainsString('  --sf-primary-80: #00e292;', $css);
        self::assertStringContainsString('  --sf-tertiary-40: #00677d;', $css);
        self::assertStringContainsString('  --sf-neutral-98: #f7faf5;', $css);
        self::assertStringContainsString('  --sf-error-40: #bb1919;', $css);
        self::assertSame('/* docara-theme: #0a7d4f (hue 161, chroma 85), SIMAI Framework ui-45384c434d3f SF.ThemePalette.themeToCss */', $comment);

        $receipt = $result['receipt'];
        self::assertSame('docara.site_theme.v1', $receipt['schema']);
        self::assertSame(['hue' => 161, 'chroma' => 85], $receipt['palette']);
        self::assertSame('ui-45384c434d3f', $receipt['framework']['pair_id']);
        self::assertSame(128, $receipt['stylesheet']['variables']);
        self::assertSame('docara-theme.' . hash('sha256', $result['content']) . '.css', $receipt['stylesheet']['filename']);

        self::assertSame($result, $this->palette()->stylesheet($this->lock(), $this->theme(['color' => '#0A7D4F'])), 'The same colour gives the same bytes.');
    }

    #[Test]
    public function hue_and_chroma_override_how_the_colour_is_read(): void
    {
        $result = $this->palette()->stylesheet($this->lock(), $this->theme(['color' => '#0a7d4f', 'hue' => 200, 'chroma' => 40]));

        // themeFromHct({hue: 200, chroma: 40}) of ui-source b06420f5f.
        self::assertStringContainsString('  --sf-primary-40: #00696c;', $result['content']);
        self::assertStringContainsString('  --sf-tertiary-40: #365f97;', $result['content']);
        self::assertSame(['hue' => 200, 'chroma' => 40], $result['receipt']['palette']);
    }

    #[Test]
    public function the_frameworks_own_colour_gives_back_the_shipped_primitives(): void
    {
        $result = $this->palette()->stylesheet($this->lock(), $this->theme(['color' => '#005bbe']));
        $core = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($this->lock()))->bundledRuntimeAsset('core/css/core.css');

        preg_match_all('/^  (--sf-[a-z]+-[0-9]+): (#[0-9a-f]{6});$/m', $result['content'], $theme, PREG_SET_ORDER);
        self::assertCount(128, $theme);
        foreach ($theme as [, $name, $value]) {
            self::assertMatchesRegularExpression(
                '/' . preg_quote($name, '/') . ':\s*' . preg_quote($value, '/') . ';/i',
                $core,
                "$name of #005bbe is not the shipped primitive.",
            );
        }
    }

    /** @param array<string, mixed> $theme */
    private function theme(array $theme): SiteTheme
    {
        $siteTheme = SiteTheme::fromSite(['theme' => $theme]);
        self::assertInstanceOf(SiteTheme::class, $siteTheme);

        return $siteTheme;
    }

    private function palette(): FrameworkThemePalette
    {
        $node = getenv('DOCARA_NODE_BINARY') ?: (new ExecutableFinder)->find('node');
        self::assertIsString($node, 'The theme palette runs on Node.js.');

        return new FrameworkThemePalette($node);
    }

    /** @return array<string, mixed> */
    private function lock(): array
    {
        return FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json')->toArray();
    }
}
