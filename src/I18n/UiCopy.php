<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class UiCopy
{
    private const IDS = [
        'shell.skip_to_content',
        'navigation.open',
        'navigation.mobile_title',
        'navigation.primary',
        'navigation.sections',
        'navigation.title',
        'navigation.close',
        'navigation.breadcrumbs',
        'navigation.breadcrumbs_expand',
        'navigation.outline',
        'navigation.outline_close',
        'navigation.previous_next',
        'navigation.previous',
        'navigation.next',
        'navigation.expand',
        'navigation.collapse',
        'navigation.contains_current',
        'language.label',
        'search.open',
        'search.label',
        'search.title',
        'search.close',
        'search.query',
        'search.placeholder',
        'search.idle',
        'search.loading',
        'search.found',
        'search.empty',
        'search.error',
        'search.navigate',
        'search.open_result',
        'search.dismiss',
        'reader.open',
        'reader.close',
        'reader.theme_title',
        'reader.theme_description',
        'reader.theme_system',
        'reader.theme_light',
        'reader.theme_dark',
        'reader.saved',
        'reader.applied_not_saved',
        'reader.restored',
        'redirect.title',
        'redirect.message',
        'redirect.link',
    ];

    /**
     * Docara's optional interface strings with built-in defaults per language.
     * A site's content/<locale>/lang.json overrides any key; a language
     * without its own defaults uses English.
     *
     * @var array<string, array<string, string>>
     */
    private const OPTIONAL_DEFAULTS = [
        'en' => [
            'code.copy' => 'Copy',
            'code.copied' => 'Copied',
            'code.wrap' => 'Wrap source lines',
            'code.unwrap' => 'Keep original source lines',
            'examples.example' => 'Example',
            'examples.viewer' => 'Check responsive preview',
            'examples.viewer_exit' => 'Exit responsive preview',
            'examples.viewer_dialog' => 'Responsive example preview',
            'examples.viewport_group' => 'Preview width',
            'examples.viewport_desktop' => 'Desktop',
            'examples.viewport_tablet' => 'Tablet',
            'examples.viewport_mobile' => 'Mobile',
            'reader.font_size_title' => 'Font size',
            'reader.font_size_description' => 'Changes the size of the whole documentation interface.',
            'reader.font_size_small' => 'Small',
            'reader.font_size_normal' => 'Normal',
            'reader.font_size_large' => 'Large',
            'reader.content_width_title' => 'Page width',
            'reader.content_width_description' => 'Wide uses the full window and does not limit line length.',
            'reader.content_width_normal' => 'Normal',
            'reader.content_width_wide' => 'Wide',
            'reader.outline_hide' => 'Hide page contents',
            'reader.focus_mode' => 'Reading mode',
            'reader.focus_mode_exit' => 'Exit reading mode (Esc)',
            'reader.reset_all' => 'Reset settings',
            'reader.panel_title' => 'Settings',
        ],
        'ru' => [
            'code.copy' => 'Скопировать',
            'code.copied' => 'Скопировано',
            'code.wrap' => 'Переносить длинные строки',
            'code.unwrap' => 'Не переносить длинные строки',
            'examples.example' => 'Пример',
            'examples.viewer' => 'Проверить адаптивность',
            'examples.viewer_exit' => 'Выйти из режима просмотра',
            'examples.viewer_dialog' => 'Адаптивный просмотр примера',
            'examples.viewport_group' => 'Ширина примера',
            'examples.viewport_desktop' => 'Десктоп',
            'examples.viewport_tablet' => 'Планшет',
            'examples.viewport_mobile' => 'Телефон',
            'reader.font_size_title' => 'Размер шрифта',
            'reader.font_size_description' => 'Меняет размер всего интерфейса документации.',
            'reader.font_size_small' => 'Мелкий',
            'reader.font_size_normal' => 'Обычный',
            'reader.font_size_large' => 'Крупный',
            'reader.content_width_title' => 'Ширина страницы',
            'reader.content_width_description' => 'Широкий формат занимает всё окно и не ограничивает длину строк.',
            'reader.content_width_normal' => 'Обычная',
            'reader.content_width_wide' => 'Широкая',
            'reader.outline_hide' => 'Скрыть содержание страницы',
            'reader.focus_mode' => 'Режим чтения',
            'reader.focus_mode_exit' => 'Выйти из режима чтения (Esc)',
            'reader.reset_all' => 'Сбросить настройки',
            'reader.panel_title' => 'Настройки',
        ],
    ];

    /** Optional keys that replaced a site-owned key of an earlier release. */
    private const RENAMED_FROM = [
        'reader.panel_title' => 'reader.title',
        'reader.reset_all' => 'reader.reset',
    ];

    public function __construct(private Translator $translator) {}

    /** @return array<string, string> */
    public function forLocale(string $locale): array
    {
        $copy = [];
        foreach (self::IDS as $id) {
            $copy[$id] = $this->translator->message($locale, $id);
        }
        $defaults = self::optionalDefaults($locale);
        $ownLanguage = self::hasOwnDefaults($locale);
        foreach ($defaults as $id => $default) {
            // A language without built-in strings keeps the site's own text for
            // a renamed key rather than showing the English default.
            if (! $ownLanguage && isset(self::RENAMED_FROM[$id])) {
                $default = $this->translator->messageOr($locale, self::RENAMED_FROM[$id], $default);
            }
            $copy[$id] = $this->translator->messageOr($locale, $id, $default);
        }

        return $copy;
    }

    private static function hasOwnDefaults(string $locale): bool
    {
        return isset(self::OPTIONAL_DEFAULTS[strtolower(explode('-', str_replace('_', '-', $locale), 2)[0])]);
    }

    /** @return array<string, string> */
    public static function optionalDefaults(string $locale): array
    {
        $language = strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);

        return array_replace(self::OPTIONAL_DEFAULTS['en'], self::OPTIONAL_DEFAULTS[$language] ?? []);
    }
}
