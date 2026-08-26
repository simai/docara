# Профили страниц и page SDK

Docara дополняет обычный Markdown необязательным авторским контрактом. Он
помогает человеку или внешнему агенту понять назначение страницы, увидеть
измеримые пробелы и получить технические связи одним SDK-запросом. Это не
отдельная база знаний и не второй каталог страниц.

## Источники истины

- Markdown остаётся владельцем страницы и текста;
- существующие settings, registries, `examples/`, translation lock и Git
  сохраняют свои роли;
- `docara.authoring.json` хранит только аудитории, профиль по умолчанию и
  правила `путь → профиль`.

В authoring-файле запрещены версии, страницы, hashes, статусы и копии
каталогов. Без него сборка и публичный результат старого проекта не меняются.

## Разрешение профиля

Путь считается относительно content root локали. Порядок: `profile` во front
matter → совпавшее правило пути → `default_profile`. Конфликт профилей разных
правил является ошибкой. Встроены `landing`, `article`, `tutorial`, `how_to`,
`reference`, `explanation`.

Docara проверяет только измеримые признаки структуры, ссылки, assets и
примеры. Смысловой checklist остаётся `review_required`; движок не вызывает
сеть или модель.

## SDK

```text
list page
inspect page <public-route>
validate page <public-route>
validate project
schema authoring
scaffold page <locale-relative-route> --locale --title --profile --dry-run
```

`inspect page` возвращает `docara.page_inspection.v1`: источник, route/locale,
front matter, effective settings и provenance, профиль, компоненты и
`docs_ref`, примеры, ссылки, translation relation, lock descriptors, Git и
engine revisions, hashes и diagnostics. CLI и MCP используют те же Application
services.

Scaffold создаёт только отсутствующий draft Markdown внутри настроенного
content root и применяется по неизменившемуся SHA-256 plan. Обновление
существующей страницы остаётся обычным редактированием Markdown.

## Не-цели

- `knowledge/`, knowledge lock и `knowledge *`;
- новый Mirai-профиль;
- автоматическая смысловая оценка или перевод;
- копии catalog, receipts, translation report или Git state.
