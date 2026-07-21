# Проектный language pack

Language pack переводит системные подписи Docara и представления компонентов,
но не Markdown-страницы. Создавайте собственный pack, когда встроенного
`@docara/<tag>` нет или проекту нужна своя терминология.

## 1. Создайте файл словаря

Например, `languages/fr-CA.json`:

```json
{
  "schema": "docara.language_pack.v1",
  "locale": "fr-CA",
  "messages": {
    "common.continue": "Continuer",
    "common.greeting": "Bonjour, {name}!"
  }
}
```

Это полный schema-valid файл, но намеренно неполный перевод. Ключи сообщений
имеют вид `group.message_id`; значения непустые. Имена параметров в фигурных
скобках должны совпадать с canonical сообщением.

## 2. Подключите pack и явный fallback

В объекте `locales` файла `docara.json` добавьте `fr-CA` и уже настроенный
`en`:

```json
{
  "en": {
    "label": "English",
    "direction": "ltr",
    "content_root": "content/en",
    "language_pack": "@docara/en",
    "public_prefix": "en",
    "fallbacks": []
  },
  "fr-CA": {
    "label": "Français (Canada)",
    "direction": "ltr",
    "content_root": "content/fr-CA",
    "language_pack": "languages/fr-CA.json",
    "public_prefix": "fr-ca",
    "fallbacks": ["en"]
  }
}
```

Этот блок является содержимым поля `locales`, а не самостоятельным
`docara.json`. Сначала проверяется проектный pack `fr-CA`, затем английский.
Fallback никогда не выводится автоматически из части тега `fr-CA`.

## 3. Переведите представление компонента

Поле `components` необязательно. Добавляйте его только для точного ID из
generated catalog. Пример фрагмента внутри корневого объекта pack:

```json
{
  "components": {
    "ui.alert": {
      "title": "Alerte",
      "description": "Affiche un message important dans le contenu."
    }
  }
}
```

Это фрагмент для объединения с полным файлом выше. Параметры, состояния и enum
нельзя придумывать или удалять: после merge Docara сравнивает presentation с
технической canonical-записью. Для полного перевода удобнее скопировать
структуру соответствующего встроенного pack и менять только текстовые
значения.

## 4. Проверьте pack реальной сборкой

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Build проверяет JSON Schema, совпадение `locale`, безопасный относительный путь,
fallback-граф и наличие всех реально запрошенных сообщений и представлений.
`verify-static` затем проверяет локализованные страницы, ссылки, поиск и
generated catalog.

Частые ошибки:

- `LANGUAGE_PACK_LOCALE_MISMATCH` — поле `locale` не совпало с ключом реестра;
- `LOCALE_FALLBACK_NOT_CONFIGURED` — fallback не объявлен в `locales`;
- `LOCALE_FALLBACK_CYCLE` — цепочка замкнулась;
- `MESSAGE_NOT_FOUND` — сообщения нет ни в pack, ни в fallback;
- `COMPONENT_PRESENTATION_NOT_FOUND` — нет представления компонента.

Schema находится в `resources/schemas/language-pack.schema.json`, а встроенные
pack — в `resources/language-packs`. Эти package-файлы не редактируйте из
проекта: создавайте собственный файл в `languages/`.
