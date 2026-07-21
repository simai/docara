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
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "language_pack": "@docara/ru",
      "public_prefix": "ru",
      "fallbacks": []
    },
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "language_pack": "@docara/en",
      "public_prefix": "en",
      "fallbacks": []
    },
    "ar": {
      "label": "العربية",
      "direction": "rtl",
      "content_root": "content/ar",
      "language_pack": "@docara/ar",
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
    "label": "Docs"
  },
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

Все locale из `fallbacks` обязаны присутствовать в этом же реестре. Арабский
встроенный pack дополняется английским, но Markdown-контент не переводится
fallback-механизмом. Для RTL явно задаётся `"direction": "rtl"`.

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

Если `content/ar/guide/install.md` отсутствует, арабская страница установки не
создаётся и переключатель не ведёт на выдуманный URL. Добавьте файл или
оставьте маршрут недоступным. Не копируйте русский Markdown как скрытый
fallback.

Далее: [модель локалей](/authoring/localization/) и
[собственный language pack](/authoring/language-packs/).
