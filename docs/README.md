# Документация Docara

Исходники пользовательской и технической документации находятся в
`docs/site/content/ru`. JSON-файлы рядом со страницами задают навигацию и
представление. Каталоги `build_*` являются производным результатом и не
редактируются.

## Пути читателя

| Кто | Начало | Результат |
| --- | --- | --- |
| Новый автор | `content/ru/start.md` | Собранный, проверенный и открытый по HTTP сайт |
| Автор документации | `content/ru/authoring.md` | Markdown-only страницы, бренд, макет и локали |
| Владелец сайта | `content/ru/build.md` | Безопасное обновление и staged-публикация с rollback |
| Мигрирующий проект | `content/ru/migration.md` | Явный переход без скрытого преобразования |
| Разработчик Docara | `content/development.md` | Зарегистрированные Layout, Section, Block, View Tree и Smart |

Архитектура единого декларативного конвейера описана в
[`declarative-rendering-pipeline.md`](declarative-rendering-pipeline.md).
Текущий список ещё не выпущенных изменений находится в
[`changes.md`](changes.md).

## Источники истины

- Markdown и JSON в `docs/site` владеют объяснением пользовательских задач.
- `resources/schemas` и реализация CLI владеют исполняемым контрактом.
- `resources/component-catalog` и точные example fixtures владеют сведениями о
  компонентах.
- `resources/language-packs` и проектные `languages/*.json` владеют
  переводимыми системными подписями и presentation компонентов; canonical
  технический контракт остаётся в component catalog.
- `resources/layouts`, `sections`, `blocks`, `views` и `smart` владеют
  зарегистрированной декларативной композицией; авторский JSON только вызывает
  разрешённые ID.
- `/components/catalog/` генерируется при сборке и является единственным
  детальным справочником компонентов.
- `docs/site/examples/*.json` связывает реальные скрытые Markdown-страницы с
  `/examples/`; detail-поверхность показывает живой результат и точные
  исходные файлы без второго renderer.
- `_docara/component-catalog.json` и
  `.docara/resolved-page-plans.json`,
  `_docara/declarative-examples.json` — диагностические результаты, а не
  редактируемые источники.

Не создавайте ручную страницу с копией параметров, состояний или ограничений
компонента. Обновите владеющую запись и пример, затем пересоберите каталог.

## Когда обновлять документацию

- изменился CLI, schema, default или правило наследования;
- изменился starter;
- добавлен или удалён пользовательский маршрут;
- изменился процесс build, verify или publish;
- изменился способ допуска native, typed или Smart-компонента;
- изменился migration или security boundary.
- изменился accepted portable candidate или update/rollback contract;
- изменились locale registry, fallback либо language-pack schema;
- добавлена регистрационная поверхность Layout, Section, Block, View Tree или
  Smart-компонента.

## Сборка и проверка

Из корня репозитория:

```bash
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
php ../../docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Откройте `http://127.0.0.1:8000`, проверьте нужные маршруты, поиск и темы, затем
остановите сервер сочетанием `Ctrl+C`. `file://` не является проверкой
переносимого сайта.

Для финальной приёмки собирайте из точного immutable source tree и сравнивайте
две последовательные сборки. Документация сама по себе не является заявлением
о production или публичном release.
