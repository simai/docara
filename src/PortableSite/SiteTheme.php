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
 * four tonal palettes every semantic token reads (see
 * {@see FrameworkThemePalette}). `hue` (0-360) and
 * `chroma` (0-150) are optional: set, they override how the colour is read,
 * exactly as sf-theme-generator's fine-tuning fields do, so a host that keeps
 * the generator's `value`, `hue` and `chroma` gets the same theme back.
 *
 * The theme is site-wide: it is read from docara.json only.
 *
 * `choices` offers readers more colours in the settings menu («Цвет»):
 *
 *     "theme": {"color": "#005bbe", "choices": [
 *         {"id": "forest", "color": "#2e7d32", "title": {"ru": "Лес", "en": "Forest"}}
 *     ]}
 *
 * Each choice is made by the same Framework palette at build time and lands in
 * the same stylesheet under `:root[data-docara-color="<id>"]`. `color` may be
 * left out when the site keeps the Framework's own colours and only offers
 * choices.
 */
final readonly class SiteTheme
{
    public const SCHEMA = 'docara.site_theme.v1';

    public const CHOICES_MAXIMUM = 8;

    /**
     * @param  list<array{id: string, color: string, hue: int|null, chroma: int|null, title: string|array<string, string>}>  $choices
     */
    private function __construct(
        public ?string $color,
        public ?int $hue,
        public ?int $chroma,
        public array $choices = [],
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
        $unknown = array_diff(array_keys($theme), ['color', 'hue', 'chroma', 'choices']);
        if ($unknown !== []) {
            throw new PortableConfigurationException(
                'THEME_INVALID',
                'docara.json "theme" accepts only "color", "hue", "chroma" and "choices"; found [' . implode(', ', $unknown) . '].',
            );
        }
        $choices = self::choices($theme['choices'] ?? null, array_key_exists('choices', $theme));
        if (! array_key_exists('color', $theme) && $choices !== []) {
            if (array_key_exists('hue', $theme) || array_key_exists('chroma', $theme)) {
                throw new PortableConfigurationException(
                    'THEME_COLOR_INVALID',
                    'docara.json "theme.hue" and "theme.chroma" tune "theme.color"; set the colour or remove them.',
                );
            }

            return new self(null, null, null, $choices);
        }

        return new self(
            self::color($theme['color'] ?? null, 'theme.color'),
            self::integer($theme, 'hue', 360, 'THEME_HUE_INVALID', 'theme.hue'),
            self::integer($theme, 'chroma', 150, 'THEME_CHROMA_INVALID', 'theme.chroma'),
            $choices,
        );
    }

    /**
     * What the Framework's palette is asked for. A theme with choices adds
     * them; one without keeps the input earlier releases recorded.
     *
     * @return array<string, mixed>
     */
    public function input(): array
    {
        $input = ['color' => $this->color, 'hue' => $this->hue, 'chroma' => $this->chroma];
        if ($this->choices !== []) {
            $input['choices'] = array_map(static fn (array $choice): array => [
                'id' => $choice['id'],
                'color' => $choice['color'],
                'hue' => $choice['hue'],
                'chroma' => $choice['chroma'],
            ], $this->choices);
        }

        return $input;
    }

    /**
     * The reader-facing title of a choice in a locale: its own text for the
     * locale, else its first text.
     *
     * @param  array{title: string|array<string, string>}  $choice
     */
    public static function choiceTitle(array $choice, string $locale): string
    {
        $title = $choice['title'];
        if (is_string($title)) {
            return $title;
        }

        return $title[$locale] ?? (string) reset($title);
    }

    /**
     * @return list<array{id: string, color: string, hue: int|null, chroma: int|null, title: string|array<string, string>}>
     */
    private static function choices(mixed $choices, bool $present): array
    {
        if (! $present) {
            return [];
        }
        if (! is_array($choices) || ! array_is_list($choices) || $choices === [] || count($choices) > self::CHOICES_MAXIMUM) {
            throw new PortableConfigurationException(
                'THEME_CHOICES_INVALID',
                'docara.json "theme.choices" must be a list of 1 to ' . self::CHOICES_MAXIMUM
                    . ' colours, for example [{"id": "forest", "color": "#2e7d32", "title": "Forest"}].',
            );
        }
        $result = [];
        $seen = [];
        foreach ($choices as $index => $choice) {
            $at = "theme.choices[$index]";
            if (! is_array($choice) || array_is_list($choice)) {
                throw new PortableConfigurationException('THEME_CHOICES_INVALID', "docara.json \"$at\" must be an object with an id, a colour and a title.");
            }
            $unknown = array_diff(array_keys($choice), ['id', 'color', 'hue', 'chroma', 'title']);
            if ($unknown !== []) {
                throw new PortableConfigurationException(
                    'THEME_CHOICES_INVALID',
                    "docara.json \"$at\" accepts only \"id\", \"color\", \"hue\", \"chroma\" and \"title\"; found [" . implode(', ', $unknown) . '].',
                );
            }
            $id = $choice['id'] ?? null;
            if (! is_string($id) || preg_match('/\A[a-z][a-z0-9-]{0,31}\z/D', $id) !== 1 || $id === 'site') {
                throw new PortableConfigurationException(
                    'THEME_CHOICES_INVALID',
                    "docara.json \"$at.id\" must be lower-case latin letters, digits and hyphens, starting with a letter, and not \"site\"; found " . self::describe($id) . '.',
                );
            }
            if (isset($seen[$id])) {
                throw new PortableConfigurationException('THEME_CHOICES_INVALID', "docara.json \"theme.choices\" lists the id [$id] twice.");
            }
            $seen[$id] = true;
            $title = $choice['title'] ?? null;
            $validText = static fn (mixed $text): bool => is_string($text) && trim($text) !== '' && mb_strlen($text) <= 40;
            $validTitle = $validText($title) || (is_array($title) && $title !== [] && ! array_is_list($title)
                && array_filter($title, static fn (mixed $text, mixed $locale): bool => ! is_string($locale) || ! $validText($text), ARRAY_FILTER_USE_BOTH) === []);
            if (! $validTitle) {
                throw new PortableConfigurationException(
                    'THEME_CHOICES_INVALID',
                    "docara.json \"$at.title\" must be text of up to 40 characters, or an object of such texts by locale, for example {\"ru\": \"Лес\", \"en\": \"Forest\"}.",
                );
            }
            $result[] = [
                'id' => $id,
                'color' => self::color($choice['color'] ?? null, "$at.color"),
                'hue' => self::integer($choice, 'hue', 360, 'THEME_HUE_INVALID', "$at.hue"),
                'chroma' => self::integer($choice, 'chroma', 150, 'THEME_CHROMA_INVALID', "$at.chroma"),
                'title' => $title,
            ];
        }

        return $result;
    }

    private static function color(mixed $color, string $path): string
    {
        if (! is_string($color) || preg_match('/\A#(?:[0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})\z/D', $color) !== 1) {
            throw new PortableConfigurationException(
                'THEME_COLOR_INVALID',
                "docara.json \"$path\" must be a hex colour, #rgb or #rrggbb, for example \"#0a7d4f\"; found "
                    . self::describe($color) . '.',
            );
        }

        return self::normalizeColor($color);
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
    private static function integer(array $theme, string $key, int $maximum, string $code, string $path): ?int
    {
        if (! array_key_exists($key, $theme)) {
            return null;
        }
        $value = $theme[$key];
        if (! is_int($value) || $value < 0 || $value > $maximum) {
            throw new PortableConfigurationException(
                $code,
                "docara.json \"$path\" must be a whole number from 0 to $maximum; found " . self::describe($value) . '.',
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
