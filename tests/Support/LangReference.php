<?php

declare(strict_types=1);

namespace Tests\Support;

use Simai\Docara\I18n\UiCopy;
use Simai\Docara\PortableSite\NotFoundPage;

/**
 * The table of lang.json strings on authoring/language-packs.md, built from
 * UiCopy (which strings exist, which are required, built-in English and
 * Russian text) and from the init templates (the text `docara init` writes
 * for required strings). Only the meaning column is written by hand, here.
 */
final class LangReference
{
    public const PAGE = 'docs/site/content/ru/authoring/language-packs.md';

    public const START = '<!-- docara:lang-reference:start -->';

    public const END = '<!-- docara:lang-reference:end -->';

    private const TEMPLATES = [
        'en' => 'stubs/minimal/en/content/en/lang.json',
        'ru' => 'stubs/minimal/ru/content/ru/lang.json',
    ];

    /** Strings a site may override that Docara reads outside UiCopy, with their English fallback. */
    private const READ_ELSEWHERE = [
        'navigation.backlinks_heading' => 'Referenced by',
        'navigation.backlinks_empty' => 'No backlinks yet.',
    ];

    /** Keys the init templates write that no Docara 3.x interface shows (UiCopy::forLocale() leaves them out). */
    private const NOT_READ = ['reader.appearance', 'reader.appearance_description', 'reader.help'];

    /** @var array<string,string> */
    public const MEANINGS = [
        'shell.skip_to_content' => 'Ссылка «к содержанию» для клавиатуры в начале страницы',
        'navigation.open' => 'Кнопка, открывающая меню на телефоне',
        'navigation.mobile_title' => 'Заголовок меню на телефоне',
        'navigation.primary' => 'Подпись верхнего меню для программ экранного доступа',
        'navigation.sections' => 'Подпись меню разделов слева',
        'navigation.title' => 'Заголовок списка разделов',
        'navigation.close' => 'Кнопка, закрывающая меню на телефоне',
        'navigation.breadcrumbs' => 'Подпись пути до страницы для программ экранного доступа',
        'navigation.breadcrumbs_expand' => 'Кнопка, показывающая скрытые уровни пути',
        'navigation.outline' => 'Заголовок оглавления страницы',
        'navigation.outline_close' => 'Кнопка, закрывающая оглавление на телефоне',
        'navigation.previous_next' => 'Подпись блока ссылок «назад» и «вперёд»',
        'navigation.previous' => 'Ссылка на предыдущую страницу',
        'navigation.next' => 'Ссылка на следующую страницу',
        'navigation.expand' => 'Начало подписи кнопки, раскрывающей раздел меню',
        'navigation.collapse' => 'Начало подписи кнопки, сворачивающей раздел меню',
        'navigation.contains_current' => 'Добавка к подписи раздела, в котором открыта страница',
        'navigation.backlinks_heading' => 'Заголовок блока обратных ссылок `:::backlinks`',
        'navigation.backlinks_empty' => 'Текст блока обратных ссылок, когда ссылок нет',
        'language.label' => 'Подпись переключателя языков',
        'language.fallback_notice' => 'Уведомление на странице, перевод которой ещё не готов; `{source}` — ссылка на оригинал',
        'search.open' => 'Кнопка, открывающая поиск',
        'search.label' => 'Подпись кнопки поиска',
        'search.title' => 'Заголовок окна поиска',
        'search.close' => 'Кнопка, закрывающая окно поиска',
        'search.query' => 'Подпись поля ввода запроса',
        'search.placeholder' => 'Подсказка в пустом поле поиска',
        'search.idle' => 'Текст до ввода запроса',
        'search.loading' => 'Текст, пока загружается индекс поиска',
        'search.found' => 'Число результатов; `{count}` — количество',
        'search.empty' => 'Текст, когда ничего не найдено',
        'search.error' => 'Текст, когда поиск не загрузился',
        'search.navigate' => 'Подсказка клавиш: перемещение по результатам',
        'search.open_result' => 'Подсказка клавиш: открыть результат',
        'search.dismiss' => 'Подсказка клавиш: закрыть поиск',
        'reader.open' => 'Кнопка, открывающая панель настроек читателя',
        'reader.close' => 'Кнопка, закрывающая панель настроек',
        'reader.panel_title' => 'Заголовок панели настроек',
        'reader.reset_all' => 'Кнопка сброса всех настроек читателя',
        'reader.theme_title' => 'Название настройки темы',
        'reader.theme_description' => 'Пояснение к настройке темы',
        'reader.theme_system' => 'Вариант темы «как в системе»',
        'reader.theme_light' => 'Вариант светлой темы',
        'reader.theme_dark' => 'Вариант тёмной темы',
        'reader.font_size_title' => 'Название настройки размера шрифта',
        'reader.font_size_description' => 'Пояснение к настройке размера шрифта',
        'reader.font_size_small' => 'Вариант мелкого шрифта',
        'reader.font_size_normal' => 'Вариант обычного шрифта',
        'reader.font_size_large' => 'Вариант крупного шрифта',
        'reader.content_width_title' => 'Название настройки ширины страницы',
        'reader.content_width_description' => 'Пояснение к настройке ширины страницы',
        'reader.content_width_normal' => 'Вариант обычной ширины',
        'reader.content_width_wide' => 'Вариант широкой страницы',
        'reader.outline_hide' => 'Кнопка, скрывающая оглавление страницы',
        'reader.focus_mode' => 'Кнопка режима чтения',
        'reader.focus_mode_exit' => 'Кнопка выхода из режима чтения',
        'reader.saved' => 'Сообщение о сохранённой настройке; `{setting}` — настройка, `{value}` — значение',
        'reader.applied_not_saved' => 'Сообщение, когда браузер не дал сохранить настройку',
        'reader.restored' => 'Сообщение после сброса настроек',
        'reader.appearance' => 'Название группы «Оформление» в панели настроек',
        'reader.appearance_description' => 'Пояснение к группе «Оформление»',
        'reader.help' => 'Общее пояснение в панели настроек',
        'redirect.title' => 'Заголовок страницы перенаправления',
        'redirect.message' => 'Текст страницы перенаправления',
        'redirect.link' => 'Ссылка на новый адрес на странице перенаправления',
        'code.copy' => 'Кнопка копирования кода',
        'code.copied' => 'Подтверждение после копирования',
        'code.wrap' => 'Кнопка, включающая перенос длинных строк кода',
        'code.unwrap' => 'Кнопка, выключающая перенос длинных строк кода',
        'effects.pause' => 'Кнопка, останавливающая фоновую анимацию hero',
        'effects.play' => 'Кнопка, запускающая фоновую анимацию hero',
        'examples.example' => 'Подпись живого примера',
        'examples.viewer' => 'Кнопка проверки примера на разной ширине',
        'examples.viewer_exit' => 'Кнопка выхода из проверки ширины',
        'examples.viewer_dialog' => 'Подпись окна проверки ширины',
        'examples.viewport_group' => 'Подпись переключателя ширины',
        'examples.viewport_desktop' => 'Вариант ширины «компьютер»',
        'examples.viewport_tablet' => 'Вариант ширины «планшет»',
        'examples.viewport_mobile' => 'Вариант ширины «телефон»',
        'error.not_found.title' => 'Заголовок страницы 404',
        'error.not_found.message' => 'Текст страницы 404',
        'error.not_found.home' => 'Ссылка на главную на странице 404',
        'error.not_found.search' => 'Кнопка поиска на странице 404',
        'examples.preview_title' => 'Подпись рамки с результатом примера',
        'examples.result_label' => 'Подпись результата в примере, созданном `docara scaffold`',
        'atlas.empty' => 'Текст пустого списка Design Atlas',
        'atlas.owner' => 'Подпись владельца в Design Atlas',
        'branding.credit' => 'Подпись «Сделано на Docara»; `{docara}` — ссылка',
        'schema.field' => 'Заголовок столбца «Поле» в справочнике настроек',
        'schema.description' => 'Заголовок столбца описания в справочнике настроек',
        'schema.default' => 'Подпись значения по умолчанию под описанием поля',
        'schema.required' => 'Отметка обязательного поля',
        'schema.details' => 'Подпись раскрывающихся технических данных поля',
        'schema.type' => 'Подпись типа поля',
        'schema.scope' => 'Подпись уровня настроек',
        'schema.validation' => 'Подпись правила проверки',
        'schema.provenance' => 'Подпись места в схеме',
        'schema.description_missing' => 'Текст, когда у поля нет описания',
        'schema.inherited' => 'Значение наследуется',
        'schema.default_inherited' => 'Значение наследуется, иначе встроенное; `{value}` — встроенное',
        'schema.path' => 'Подпись точного пути поля',
        'schema.key_region' => 'Имя области в пути поля',
        'schema.key_locale' => 'Код языка в пути поля',
        'schema.key_any' => 'Произвольный ключ в пути поля',
    ];

    public function __construct(private readonly string $root) {}

    /** The generated Markdown between START and END. */
    public function markdown(): string
    {
        $templates = [];
        foreach (self::TEMPLATES as $language => $path) {
            $templates[$language] = $this->flatten(json_decode((string) file_get_contents($this->root . '/' . $path), true, 512, JSON_THROW_ON_ERROR));
        }
        $optional = UiCopy::optionalDefaultsByLanguage();
        $build = UiCopy::buildDefaultsByLanguage();
        $overridableBuild = [...NotFoundPage::MESSAGE_IDS, 'language.fallback_notice'];

        $out = "### Обязательные строки\n\n"
            . "Их нужно задать в `lang.json` каждого языка или в его запасном языке из `fallbacks`; иначе сборка остановится с `MESSAGE_NOT_FOUND`. Встроенного текста у них нет: `docara init` записывает английский или русский текст из столбцов справа.\n\n"
            . $this->table(UiCopy::requiredIds(), fn (string $id, string $language): string => $templates[$language][$id] ?? '');

        $out .= "\n### Необязательные строки\n\n"
            . "Если строки нет в `lang.json`, Docara берёт встроенный текст: русский для русского языка, английский для остальных.\n\n"
            . $this->table(
                [...array_keys($optional['en']), ...array_keys(self::READ_ELSEWHERE)],
                fn (string $id, string $language): string => $optional[$language][$id]
                    ?? ($language === 'en' ? (self::READ_ELSEWHERE[$id] ?? '') : (isset(self::READ_ELSEWHERE[$id]) ? self::READ_ELSEWHERE[$id] . ' (английский)' : '')),
            );

        $out .= "\n### Страница 404 и уведомление о переводе\n\n"
            . "Эти строки тоже можно задать в `lang.json`. Без них Docara пишет встроенный текст при сборке.\n\n"
            . $this->table($overridableBuild, fn (string $id, string $language): string => $build[$language][$id] ?? '');

        $fixed = array_values(array_diff(array_keys($build['en']), $overridableBuild));
        $out .= "\n### Встроенные строки, которые не меняются\n\n"
            . "Docara пишет эти строки при сборке на английском или русском. Через `lang.json` их не изменить.\n\n"
            . $this->table($fixed, fn (string $id, string $language): string => $build[$language][$id] ?? '');

        $out .= "\n### Ключи, которые не используются\n\n"
            . 'Шаблоны `docara init` записывают ' . implode(', ', array_map(static fn (string $id): string => '`' . $id . '`', self::NOT_READ))
            . ", но интерфейс Docara 3.x их сейчас не показывает.\n";

        return $out;
    }

    /** @return list<string> every string id the table lists */
    public function ids(): array
    {
        $build = UiCopy::buildDefaultsByLanguage();

        return [
            ...UiCopy::requiredIds(),
            ...array_keys(UiCopy::optionalDefaultsByLanguage()['en']),
            ...array_keys(self::READ_ELSEWHERE),
            ...array_keys($build['en']),
        ];
    }

    /** @return list<string> */
    public function notRead(): array
    {
        return self::NOT_READ;
    }

    public function page(): string
    {
        return (string) file_get_contents($this->root . '/' . self::PAGE);
    }

    public function withTable(string $page): string
    {
        $start = strpos($page, self::START);
        $end = strpos($page, self::END);
        if ($start === false || $end === false || $end < $start) {
            throw new \RuntimeException(self::PAGE . ' has no lang reference markers.');
        }

        return substr($page, 0, $start + strlen(self::START)) . "\n\n" . $this->markdown() . "\n" . substr($page, $end);
    }

    /**
     * @param  list<string>  $ids
     * @param  callable(string,string):string  $text
     */
    private function table(array $ids, callable $text): string
    {
        $rows = "| Ключ и где видно | English | Русский |\n| --- | --- | --- |\n";
        foreach ($ids as $id) {
            $meaning = self::MEANINGS[$id] ?? throw new \RuntimeException("No meaning for lang string [$id].");
            $rows .= '| `' . $id . '` — ' . $meaning . ' | ' . $this->cell($text($id, 'en')) . ' | ' . $this->cell($text($id, 'ru')) . " |\n";
        }

        return $rows;
    }

    private function cell(string $value): string
    {
        if ($value === '') {
            return '—';
        }

        // Keep leading/trailing spaces visible ("Expand: ") and table syntax intact.
        return str_replace(['|', "\n"], ['\\|', ' '], $value !== trim($value) ? '«' . $value . '»' : $value);
    }

    /**
     * @param  array<string,mixed>  $values
     * @return array<string,string>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            if ($prefix === '' && in_array($key, ['schema', 'version'], true)) {
                continue;
            }
            if (is_array($value)) {
                $flat += $this->flatten($value, $prefix . $key . '.');

                continue;
            }
            $flat[$prefix . $key] = (string) $value;
        }

        return $flat;
    }
}
