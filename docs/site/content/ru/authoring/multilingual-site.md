# Мультиязычный сайт

Одна команда Docara собирает все объявленные локали. В этом примере русский
публикуется под `/ru/`, английский — под `/en/`, арабский — под `/ar/`, а `/`
стабильно перенаправляет на основную локаль.

## 1. Создайте одинаковую структуру контента

```text
content/
  ru/
    index.md
    guide/
      install.md
  en/
    index.md
    guide/
      install.md
  ar/
    index.md
    guide/
      install.md
```

Минимальная русская страница `content/ru/index.md`:

```markdown
# Документация

Начните с руководства по установке.
```

Для `en` и `ar` создайте переводы с теми же относительными путями. Именно
совпадение `index.md` и `guide/install.md` связывает страницы для `hreflang` и
переключателя языка; Docara не угадывает отсутствующий перевод.

## 2. Объявите локали

Замените `docara.json` полным рабочим реестром:

```json
{
  "schema": "docara.site.v1",
  "title": "Acme Docs",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "content_root": "content/ru",
  "default_locale": "ru",
  "documentation_version": "current",
  "base_url": "/",
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
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  },
  "branding": {
    "title": "Acme",
    "label": "Docs",
    "mode": "text",
    "size": "medium"
  },
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

Все locale из `fallbacks` обязаны присутствовать в этом же реестре. Арабский
`content/ar/lang.json` может дополняться общими английскими UI-подписями, но
Markdown-контент не переводится fallback-механизмом. Для RTL явно задаётся
`"direction": "rtl"`.

Каждая локаль также может иметь собственное верхнее меню. Например,
`content/ru/section.json` содержит русские подписи:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "start", "label": "Быстрый старт", "href": "/ru/start/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

А `content/en/section.json` независимо задаёт другой состав:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "docs", "label": "Documentation", "href": "/en/"},
      {"id": "components", "label": "Components", "href": "/en/components/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

Docara не требует одинакового количества пунктов и не переводит подписи
автоматически.

## 3. Соберите и проверьте все языки

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте redirect `/`, затем `/ru/`, `/en/` и `/ar/`. На связанных страницах должны работать
переключатель языка и `hreflang`; у арабской страницы корневой HTML должен
иметь `lang="ar"` и `dir="rtl"`. Поиск хранит locale каждой записи и не
смешивает одинаковые маршруты разных языков.

## Когда перевода нет

При `missing_page_policy: "skip"`, если `content/ar/guide/install.md`
отсутствует, арабская страница установки не создаётся и переключатель не ведёт
на выдуманный URL. Для строгой симметрии выберите `"error"`: сборка остановится
с точным ожидаемым путём. Не копируйте русский Markdown как скрытый fallback.

Далее: [модель локалей](/authoring/localization/) и
[общие подписи локали](/authoring/language-packs/).
