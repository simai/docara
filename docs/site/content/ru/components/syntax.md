# Синтаксис и границы компонентов

## Выбор семейства

Идите от простого к сложному:

1. native Markdown;
2. typed-компонент Docara;
3. Smart-компонент Simai Framework.

Точная доступность определяется generated catalog конкретной сборки:

- [открыть каталог](/components/catalog/);
- machine-readable файл: `_docara/component-catalog.json`.

Только lifecycle `supported` создаёт callable detail-page. Для недоступной
requirement-записи используйте fallback из каталога.

## Typed-компонент Docara

Typed-директива содержит Markdown body:

```markdown
:::<typed-name>
Markdown-содержание по контракту выбранной записи.
:::
```

Имя, допустимая структура body, live example и ограничения находятся на
generated detail-page. Typed renderer принадлежит Docara и использует утилиты
Simai Framework, но не является Smart-компонентом.

## Smart-компонент Simai Framework

Smart-директива содержит один JSON-объект props:

```markdown
:::ui.<component>
{"<prop>":"<value>"}
:::
```

Префикс `ui` зарезервирован за Simai Framework. Форма идентификатора сама по
себе не даёт допуск. Сборка дополнительно проверяет exact Framework lock,
bundled manifest, consumer policy, props, dependencies и asset projection.

Берите copy-ready call только на generated detail-page, чтобы не дублировать и
не угадывать параметры.

## Общие правила fences

- Opening fence начинается без отступа на верхнем уровне документа.
- Closing fence должен быть не короче opening fence.
- Пустая строка перед closing fence необязательна.
- Блок внутри CommonMark list/container через indentation отклоняется.
- Typed- и Smart-директивы нельзя вкладывать друг в друга.
- Fenced code не исполняет показанную внутри директиву.

Если body должен содержать отдельную строку `:::`, используйте более длинный
внешний fence:

```markdown
::::<typed-name>
Эта строка остаётся содержимым:
:::
И эта тоже.
::::
```

## Ограниченный бюджет

Одна страница имеет общий fail-closed лимит строк, похожих на opening
typed/Smart directive. Marker-looking строки считаются по source до Markdown
render, включая строки в примерах. Если документ становится каталогом из
десятков вызовов, разделите его на несколько страниц.

Точный error code зависит от семейства и описан в
[справочнике ошибок](/reference/security-and-errors/).

## Проверка вызова

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Не расширяйте callable surface правкой Markdown, presentation JSON или
сгенерированного каталога. Новая возможность проходит отдельный owner contract,
tests, documentation и admission.
