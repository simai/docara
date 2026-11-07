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
            'code.added' => 'added',
            'code.removed' => 'removed',
            'navigation.repository' => 'Source repository',
            'effects.pause' => 'Pause background animation',
            'effects.play' => 'Play background animation',
            'examples.example' => 'Example',
            'examples.viewer' => 'Check responsive preview',
            'examples.viewer_exit' => 'Exit responsive preview',
            'examples.viewer_dialog' => 'Responsive example preview',
            'examples.viewport_group' => 'Preview width',
            'examples.viewport_desktop' => 'Desktop',
            'examples.viewport_tablet' => 'Tablet',
            'examples.viewport_mobile' => 'Mobile',
            'examples.log_title' => 'Example log',
            'examples.log_events' => 'Events',
            'examples.log_console' => 'Console',
            'examples.log_clear' => 'Clear the log',
            'examples.log_collapse' => 'Collapse the log',
            'examples.log_expand' => 'Expand the log',
            'examples.log_events_empty' => 'The example\'s events appear here',
            'examples.log_console_empty' => 'The console is empty',
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
            'code.added' => 'добавлено',
            'code.removed' => 'удалено',
            'navigation.repository' => 'Репозиторий',
            'effects.pause' => 'Остановить фоновую анимацию',
            'effects.play' => 'Запустить фоновую анимацию',
            'examples.example' => 'Пример',
            'examples.viewer' => 'Проверить адаптивность',
            'examples.viewer_exit' => 'Выйти из режима просмотра',
            'examples.viewer_dialog' => 'Адаптивный просмотр примера',
            'examples.viewport_group' => 'Ширина примера',
            'examples.viewport_desktop' => 'Десктоп',
            'examples.viewport_tablet' => 'Планшет',
            'examples.viewport_mobile' => 'Телефон',
            'examples.log_title' => 'Журнал примера',
            'examples.log_events' => 'События',
            'examples.log_console' => 'Консоль',
            'examples.log_clear' => 'Очистить журнал',
            'examples.log_collapse' => 'Свернуть журнал',
            'examples.log_expand' => 'Развернуть журнал',
            'examples.log_events_empty' => 'Здесь появятся события примера',
            'examples.log_console_empty' => 'Консоль пуста',
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

    /**
     * Strings Docara writes into the generated HTML at build time (they are not
     * sent to the page script). A language without its own strings uses English.
     *
     * @var array<string, array<string, string>>
     */
    private const BUILD_DEFAULTS = [
        'en' => [
            'examples.preview_title' => 'Example result',
            'blocks.diagram' => 'Diagram',
            'blocks.math' => 'Mathematical formula',
            'blocks.html' => 'HTML example',
            'examples.result_label' => 'Result',
            'atlas.empty' => 'The accepted Atlas has no entries for this filter.',
            'atlas.owner' => 'Owner',
            'atlas.namespace' => 'namespace',
            'atlas.origin' => 'origin',
            'atlas.provider' => 'provider',
            'atlas.authoring' => 'authoring',
            'atlas.capabilities' => 'Capabilities',
            'atlas.container' => 'Container',
            'schema.field' => 'Field',
            'schema.description' => 'What it does',
            'schema.default' => 'Default',
            'schema.required' => 'required',
            'schema.details' => 'Technical details',
            'schema.type' => 'Type',
            'schema.scope' => 'Level',
            'schema.validation' => 'Validation',
            'schema.provenance' => 'Schema',
            'schema.description_missing' => 'No description yet.',
            'schema.inherited' => 'inherited',
            'schema.default_inherited' => 'inherited; otherwise {value}',
            'schema.path' => 'Path',
            'schema.key_region' => 'region',
            'schema.key_locale' => 'locale',
            'schema.key_any' => 'key',
            'language.fallback_notice' => 'This page is being translated; the original is shown ({source}).',
            'branding.credit' => 'Made with {docara}',
            'error.not_found.title' => 'Page not found',
            'error.not_found.message' => 'There is no page at this address. It may have moved, or the address has a typo.',
            'error.not_found.home' => 'Go to the home page',
            'error.not_found.search' => 'Search the documentation',
            'set.version' => '{title} {version}',
            'set.build' => '{title}, build {revision}',
            'set.framework_from' => 'For SIMAI Framework {version} and later',
            'set.framework' => 'For SIMAI Framework {range}',
            'set.licence' => 'Licence: {licence}',
            'set.source' => 'Source code',
            'set.history' => 'Version history',
        ],
        'ru' => [
            'examples.preview_title' => 'Результат примера',
            'blocks.diagram' => 'Схема',
            'blocks.math' => 'Математическая формула',
            'blocks.html' => 'Пример HTML',
            'examples.result_label' => 'Результат',
            'atlas.empty' => 'В принятом Atlas нет элементов для этого фильтра.',
            'atlas.owner' => 'Владелец',
            'atlas.namespace' => 'пространство имён',
            'atlas.origin' => 'источник',
            'atlas.provider' => 'поставщик',
            'atlas.authoring' => 'способ подключения',
            'atlas.capabilities' => 'Возможности',
            'atlas.container' => 'Правила вложения',
            'schema.field' => 'Поле',
            'schema.description' => 'Что задаёт',
            'schema.default' => 'По умолчанию',
            'schema.required' => 'обязательное',
            'schema.details' => 'Технические данные',
            'schema.type' => 'Тип',
            'schema.scope' => 'Уровень',
            'schema.validation' => 'Проверка',
            'schema.provenance' => 'Схема',
            'schema.description_missing' => 'Описания пока нет.',
            'schema.inherited' => 'наследуется',
            'schema.default_inherited' => 'наследуется, иначе {value}',
            'schema.path' => 'Путь',
            'schema.key_region' => 'область',
            'schema.key_locale' => 'язык',
            'schema.key_any' => 'ключ',
            'language.fallback_notice' => 'Эта страница переводится; показан оригинал ({source}).',
            'branding.credit' => 'Сделано на {docara}',
            'error.not_found.title' => 'Страница не найдена',
            'error.not_found.message' => 'По этому адресу страницы нет. Возможно, её перенесли или в адресе опечатка.',
            'error.not_found.home' => 'На главную',
            'error.not_found.search' => 'Искать в документации',
            'set.version' => '{title} {version}',
            'set.build' => '{title}, сборка {revision}',
            'set.framework_from' => 'Для SIMAI Framework {version} и новее',
            'set.framework' => 'Для SIMAI Framework {range}',
            'set.licence' => 'Лицензия: {licence}',
            'set.source' => 'Исходный код',
            'set.history' => 'История версий',
        ],
    ];

    /** Optional keys that replaced a site-owned key of an earlier release. */
    private const RENAMED_FROM = [
        'reader.panel_title' => 'reader.title',
        'reader.reset_all' => 'reader.reset',
    ];

    /**
     * Build-time strings a site may replace in its own lang.json. They are the
     * ones a reader meets on the page, so a site in a language Docara has no
     * built-in text for can still write them itself. `schema.*` stays out: the
     * `schema` key of lang.json holds the file's own schema id, so a group of
     * that name cannot live beside it. `branding.credit` stays out too, since
     * the branding gate owns it.
     *
     * @var list<string>
     */
    public const OVERRIDABLE_BUILD_IDS = [
        'examples.preview_title',
        'blocks.diagram',
        'blocks.math',
        'blocks.html',
        'atlas.empty',
        'atlas.owner',
        'atlas.namespace',
        'atlas.origin',
        'atlas.provider',
        'atlas.authoring',
        'atlas.capabilities',
        'atlas.container',
        'set.version',
        'set.build',
        'set.framework_from',
        'set.framework',
        'set.licence',
        'set.source',
        'set.history',
    ];

    public function __construct(private Translator $translator) {}

    /**
     * Strings every locale's lang.json (or a fallback locale) must provide;
     * Docara has no built-in text for them.
     *
     * @return list<string>
     */
    public static function requiredIds(): array
    {
        return self::IDS;
    }

    /** @return array<string, array<string, string>> language => id => built-in text */
    public static function optionalDefaultsByLanguage(): array
    {
        return self::OPTIONAL_DEFAULTS;
    }

    /** @return array<string, array<string, string>> language => id => text written at build time */
    public static function buildDefaultsByLanguage(): array
    {
        return self::BUILD_DEFAULTS;
    }

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

    /**
     * The build-time strings for a page's language, with the site's own
     * lang.json text where it provides one of OVERRIDABLE_BUILD_IDS.
     *
     * @return array<string, string>
     */
    public function buildCopy(string $locale): array
    {
        $copy = self::buildDefaults($locale);
        foreach (self::OVERRIDABLE_BUILD_IDS as $id) {
            $copy[$id] = $this->translator->messageOr($locale, $id, $copy[$id]);
        }

        return $copy;
    }

    /** @return array<string, string> */
    public static function buildDefaults(string $locale): array
    {
        return array_replace(self::BUILD_DEFAULTS['en'], self::BUILD_DEFAULTS[self::language($locale)] ?? []);
    }

    public static function buildText(string $locale, string $id): string
    {
        return self::buildDefaults($locale)[$id] ?? throw new \InvalidArgumentException("Unknown build string [$id].");
    }

    private static function language(string $locale): string
    {
        return strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);
    }

    /** @return array<string, string> */
    public static function optionalDefaults(string $locale): array
    {
        $language = strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);

        return array_replace(self::OPTIONAL_DEFAULTS['en'], self::OPTIONAL_DEFAULTS[$language] ?? []);
    }
}
