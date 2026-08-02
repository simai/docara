# Языки и локали

Docara не ограничивает проект русским и английским языками. Сайт объявляет любое
количество локалей по тегам BCP 47, например `ar`, `zh-Hans`, `fr-CA` или
`sr-Latn-RS`. Для каждой локали задаются собственное дерево Markdown, публичный
префикс, направление письма и явная цепочка fallback для общих UI-подписей.

## Разделение данных

- `docara.json`, `section.json`, `*.page.json` и каталог компонентов хранят
  структуру, ID, параметры и связи;
- Markdown хранит переводимый контент каждой локали;
- `content/<locale>/lang.json` хранит только повторяющиеся подписи интерфейса;
- PHP, JavaScript и шаблоны обращаются к семантическим message ID и не проверяют
  конкретные значения `ru` или `en`.

Поэтому добавление языка не требует новой ветки генератора или копирования PHP-кода.

## Реестр локалей

```json
{
  "default_locale": "ru",
  "locales": {
    "missing_page_policy": "skip",
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "ru",
      "fallbacks": []
    },
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "public_prefix": "en",
      "fallbacks": []
    },
    "ar": {
      "label": "العربية",
      "direction": "rtl",
      "content_root": "content/ar",
      "public_prefix": "ar",
      "fallbacks": ["en"]
    },
    "fr-CA": {
      "label": "Français (Canada)",
      "direction": "ltr",
      "content_root": "content/fr-CA",
      "public_prefix": "fr-ca",
      "fallbacks": ["en"]
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  }
}
```

Ключ локали — канонический BCP 47 tag. `public_prefix` отвечает только за URL и
не обязан повторять tag. В симметричном режиме префикс обязателен у каждой
локали, включая основную: русский публикуется в `/ru/`, английский в `/en/`.
`content_root` разных локалей не должны пересекаться.

`locale_routing.strategy: "prefixed"` включает симметричные URL, а
`root: "redirect"` создаёт статическую страницу `/`, ведущую на
`default_locale`. Автоматический выбор языка браузера намеренно выключен:
один URL имеет одну стабильную языковую идентичность. При
`legacy_unprefixed_redirects: true` builder создаёт redirect для каждого
прежнего URL без префикса и фиксирует их в `.docara/locale-routes.json`.

Существующие проекты можно не мигрировать сразу. Режим
`strategy: "default_unprefixed"` оставляет пустой префикс основной локали и
публикует её в корне, как прежняя Docara. Оба режима проходят один validator,
но смешивать их правила в одной конфигурации нельзя.

`locales.missing_page_policy: "skip"` публикует только существующие
Markdown-owner. Значение `"error"` требует одинаковый набор routes у всех
локалей и сообщает `LOCALE_PAGE_MISSING` с ожидаемым путём файла.

## `lang.json` и fallback

Общие подписи каждой локали находятся в `content/<locale>/lang.json`. Файл
может быть неполным: Docara ищет отсутствующую UI-строку по явно объявленной
цепочке `fallbacks`. Fallback никогда не выбирается по догадке из названия
языка и никогда не подменяет Markdown-страницу.

## Результат сборки

Одна команда собирает все объявленные локали. Каждая получает изолированное
дерево URL, свой `lang`, `dir`, навигацию и локализованный каталог компонентов.
Связанные страницы получают `hreflang` и переключатель языка. Поисковый индекс
хранит locale каждой записи и не смешивает одинаковые URL разных языков.
Каждая содержательная страница получает self-canonical, а `hreflang="x-default"`
указывает на стабильный корневой redirect.

Для RTL-языка нужно явно указать `"direction": "rtl"`; Docara добавит
`dir="rtl"` в корневой HTML-элемент. Определять направление по списку известных
языков генератор намеренно не пытается.

## Как добавить язык

1. Добавьте locale в `locales`.
2. Создайте её `content_root` и Markdown-дерево.
3. Создайте `content/<locale>/lang.json` с общими UI-подписями.
4. Настройте явные `fallbacks`, если файл неполный.
5. Запустите build и `verify-static`.

Рабочий проект из трёх языков собран пошагово в руководстве
[«Мультиязычный сайт»](/authoring/multilingual-site/). Формат общих подписей
описан в разделе [`lang.json`](/authoring/language-packs/).

Если для страницы нет соответствующей страницы в другом дереве, переключатель
не придумывает перевод. Связь строится только между одинаковыми относительными
путями контента.
