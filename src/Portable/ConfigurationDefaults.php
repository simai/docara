<?php

declare(strict_types=1);

namespace Simai\Docara\Portable;

use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\I18n\LocaleMissingPagePolicy;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\Preferences\ReaderPreferenceCompiler;

/**
 * The values Docara uses when a configuration field is absent.
 *
 * INHERITED is the bottom layer of every page's configuration: the loader
 * merges docara.json, section.json and page.json over it, so these values
 * show up in the resolved page plan with provenance "@defaults". APPLIED
 * lists the fallbacks that the build applies where it reads a field; the code
 * that reads the field takes its fallback from here. The settings reference
 * shows both, so the docs cannot drift from the build.
 */
final class ConfigurationDefaults
{
    /** @var array<string, mixed> */
    public const INHERITED = [
        'content_root' => 'content',
        'search' => [
            'enabled' => false,
            'indexed' => true,
        ],
        'reading' => [
            'breadcrumbs' => true,
            'toc' => true,
            'mobile_toc' => 'auto',
            'toc_depth' => 3,
            'previous_next' => true,
        ],
        'examples' => [
            'fullscreen' => true,
            'wrap' => true,
        ],
        'code' => [
            'wrap' => true,
        ],
    ];

    /** @var array<string, mixed> field path => fallback where the build reads the field */
    public const APPLIED = [
        'base_url' => '/',
        'title' => 'Docara',
        'documentation_version' => 'current',
        'branding.mode' => 'full',
        'branding.size' => 'medium',
        'branding.credit' => true,
        'settings.theme' => 'system',
        'settings.modal_blur' => 'none',
        'settings.ui_radius' => 'default',
        'navigation.hidden' => false,
        'navigation.scope' => 'site',
        'header_navigation.enabled' => false,
        'not_found.enabled' => true,
        'locales.missing_page_policy' => LocaleMissingPagePolicy::SKIP,
        'locales.*.fallbacks' => [],
        'reader_preferences.hidden_fields' => [],
        'discovery.llms_links' => [],
        'page_transforms' => [],
    ];

    /** @var array<string, mixed>|null */
    private static ?array $flattened = null;

    public static function value(string $path): mixed
    {
        $all = self::all();
        if (! array_key_exists($path, $all)) {
            throw new \InvalidArgumentException("No configuration default for [$path].");
        }

        return $all[$path];
    }

    /**
     * Every default by dotted field path. Objects appear through their
     * fields, except locale_routing and reader_preferences, which Docara
     * fills in whole only when docara.json leaves the object out.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$flattened !== null) {
            return self::$flattened;
        }
        $layout = RegionCompositionResolver::defaults();
        $values = [];
        self::flatten(self::INHERITED, '', $values);
        self::flatten([
            'layout' => [
                'key' => $layout['key'],
                'container' => $layout['container'],
                'scrollbar' => $layout['scrollbar'],
                'content' => $layout['content'],
            ],
        ], '', $values);
        foreach (self::APPLIED as $path => $value) {
            $values[$path] = $value;
        }
        // Objects Docara fills in whole when docara.json leaves them out.
        $values['locale_routing'] = [
            'strategy' => LocaleRoutingPolicy::STRATEGY_DEFAULT_UNPREFIXED,
            'root' => LocaleRoutingPolicy::ROOT_DEFAULT_LOCALE,
            'detect_browser_language' => false,
            'legacy_unprefixed_redirects' => false,
        ];
        $values['reader_preferences'] = ReaderPreferenceCompiler::defaultConfiguration();
        ksort($values, SORT_STRING);

        return self::$flattened = $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $into
     */
    private static function flatten(array $values, string $prefix, array &$into): void
    {
        foreach ($values as $key => $value) {
            $path = $prefix . $key;
            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                self::flatten($value, $path . '.', $into);

                continue;
            }
            $into[$path] = $value;
        }
    }
}
