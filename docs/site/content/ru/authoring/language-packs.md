# Общие подписи локали

Каждая локаль хранит повторяющиеся подписи интерфейса в одном файле:
`content/<locale>/lang.json`. Здесь находятся поиск, содержание, кнопка
копирования, переходы и accessibility labels. Текст страниц и описания
компонентов остаются в Markdown.

## Создайте `lang.json`

Например, `content/fr-CA/lang.json`:

```json
{
  "schema": "docara.lang.v1",
  "version": 1,
  "common": {
    "continue": "Continuer"
  },
  "code": {
    "copy": "Copier",
    "copied": "Copié"
  }
}
```

Корневые группы ограничены общей UI-лексикой из
`resources/schemas/lang.schema.json`. Группы `pages`, `components`, `catalog`
и `examples` запрещены: пользовательский текст для них принадлежит Markdown.

## Объявите локаль

В `docara.json` укажите дерево контента, URL-префикс, направление и явные
fallback-локали:

```json
{
  "label": "Français (Canada)",
  "direction": "ltr",
  "content_root": "content/fr-CA",
  "public_prefix": "fr-ca",
  "fallbacks": ["en"]
}
```

Это содержимое записи `locales.fr-CA`, а не отдельный `docara.json`. Если общей
подписи нет во французском `lang.json`, Docara может взять её из явно
настроенного `content/en/lang.json`. Markdown-страницы таким способом никогда
не подменяются.

## Проверьте сайт

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Сборка проверяет схему `lang.json`, BCP 47, fallback-граф и наличие реально
используемых UI-строк. Частые ошибки:

- `LOCALE_FALLBACK_NOT_CONFIGURED` — fallback не объявлен в `locales`;
- `LOCALE_FALLBACK_CYCLE` — цепочка fallback замкнулась;
- `MESSAGE_NOT_FOUND` — общей подписи нет в локали и её явных fallback.

Отдельного публичного language pack, поля `language_pack` и каталога
`languages/` в Docara 2 нет.
