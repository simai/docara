<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Framework\FrameworkThemePalette;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The site's colours from one main colour: docara.json `theme`.
 *
 *     "theme": {"color": "#0a7d4f"}
 *
 * The colour is what a site owner knows (a brand colour). The SIMAI Framework
 * reads it as the hue and chroma of its own theme generator and makes the
 * eight tonal palettes every semantic token reads (see
 * {@see FrameworkThemePalette}). `hue` (0-360) and
 * `chroma` (0-150) are optional: set, they override how the colour is read,
 * exactly as sf-theme-generator's fine-tuning fields do, so a host that keeps
 * the generator's `value`, `hue` and `chroma` gets the same theme back.
 *
 * The theme is site-wide: it is read from docara.json only.
 */
final readonly class SiteTheme
{
    public const SCHEMA = 'docara.site_theme.v1';

    private function __construct(
        public string $color,
        public ?int $hue,
        public ?int $chroma,
    ) {}

    /**
     * The site's theme, or null when docara.json sets none.
     *
     * @param  array<string, mixed>  $site
     */
    public static function fromSite(array $site): ?self
    {
        if (! array_key_exists('theme', $site)) {
            return null;
        }
        $theme = $site['theme'];
        if (! is_array($theme) || ($theme !== [] && array_is_list($theme))) {
            throw new PortableConfigurationException(
                'THEME_INVALID',
                'docara.json "theme" must be an object with a "color", for example {"color": "#0a7d4f"}.',
            );
        }
        $unknown = array_diff(array_keys($theme), ['color', 'hue', 'chroma']);
        if ($unknown !== []) {
            throw new PortableConfigurationException(
                'THEME_INVALID',
                'docara.json "theme" accepts only "color", "hue" and "chroma"; found [' . implode(', ', $unknown) . '].',
            );
        }
        $color = $theme['color'] ?? null;
        if (! is_string($color) || preg_match('/\A#(?:[0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})\z/D', $color) !== 1) {
            throw new PortableConfigurationException(
                'THEME_COLOR_INVALID',
                'docara.json "theme.color" must be a hex colour, #rgb or #rrggbb, for example "#0a7d4f"; found '
                    . self::describe($color) . '.',
            );
        }

        return new self(
            self::normalizeColor($color),
            self::integer($theme, 'hue', 360, 'THEME_HUE_INVALID'),
            self::integer($theme, 'chroma', 150, 'THEME_CHROMA_INVALID'),
        );
    }

    /**
     * What the Framework's palette is asked for.
     *
     * @return array{color: string, hue: int|null, chroma: int|null}
     */
    public function input(): array
    {
        return ['color' => $this->color, 'hue' => $this->hue, 'chroma' => $this->chroma];
    }

    private static function normalizeColor(string $color): string
    {
        $digits = strtolower(substr($color, 1));
        if (strlen($digits) === 3) {
            $digits = $digits[0] . $digits[0] . $digits[1] . $digits[1] . $digits[2] . $digits[2];
        }

        return '#' . $digits;
    }

    /** @param array<string, mixed> $theme */
    private static function integer(array $theme, string $key, int $maximum, string $code): ?int
    {
        if (! array_key_exists($key, $theme)) {
            return null;
        }
        $value = $theme[$key];
        if (! is_int($value) || $value < 0 || $value > $maximum) {
            throw new PortableConfigurationException(
                $code,
                "docara.json \"theme.$key\" must be a whole number from 0 to $maximum; found " . self::describe($value) . '.',
            );
        }

        return $value;
    }

    private static function describe(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? (strlen($encoded) > 80 ? substr($encoded, 0, 77) . '...' : $encoded) : 'an unreadable value';
    }
}
