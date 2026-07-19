# Переносимый формат Docara

## Решение

Переносимый формат развивается в основном репозитории `simai/docara` как
явный режим. Отдельная третья реализация означала бы ещё один runtime, release
line и migration path.

Legacy Blade/Jigsaw остаётся доступным для существующих проектов.
`docara init --portable` не преобразует legacy-проект неявно.

## Граница продукта

Переносимый Docara намеренно меньше Larena:

- файлы являются источником истины;
- Markdown хранит содержание;
- JSON задаёт проверяемое представление и наследуемые настройки;
- результатом является детерминированный статический сайт;
- нет базы данных, административной панели, workflow, ролей и runtime CRUD.

Обычному автору нужны PHP и Composer. Vite относится только к разработке
исходных ассетов темы и не является зависимостью переносимой сборки.

## Файлы

```text
docara.json
simai-framework.lock.json
assets/
content/
  _section.json
  index.md
  index.page.json
  guides/
    _section.json
    install.md
    install.page.json
```

`docara.json` определяет сайт и указывает отдельный immutable Framework lock.
Каждый `_section.json` действует на свой каталог и потомков. Sidecar
`<page>.page.json` относится только к соседней Markdown-странице.

Для `content/guides/install.md` порядок фиксирован:

1. встроенные значения;
2. `docara.json`;
3. необязательный корневой `_section.json`;
4. `content/_section.json`;
5. `content/guides/_section.json`;
6. `content/guides/install.page.json`;
7. Markdown как содержание.

Объекты объединяются рекурсивно, массивы заменяются целиком, скалярное значение
заменяет унаследованное. Отсутствующее поле продолжает наследоваться.
`{"$reset": true}` сначала удаляет унаследованную ветку; соседние ключи затем
заполняют её заново. Reset без соседних ключей сохраняется в resolved plan как
пустой объект.

Каждое итоговое значение и сам reset имеют источник в `provenance`.

## Defaults и starter

Встроенный resolver гарантирует:

- `content_root: "content"`;
- `search.enabled: false`;
- `search.indexed: true`;
- `reading.breadcrumbs: true`;
- `reading.toc: true`;
- `reading.toc_depth: 3`;
- `reading.previous_next: true`.

Это не то же самое, что пример starter. Поставляемый starter дополнительно
выбирает `docs`, русский locale, корневой `base_url`, бренд, широкий макет,
системную тему и включает интерфейс локального поиска. Эти значения принадлежат
`docara.json` starter и могут быть изменены владельцем сайта.

Проверить итог и источник каждого поля можно в
`build_<environment>/.docara/resolved-page-plans.json`.

## Presentation scopes

Наследуемые ветки `branding`, `layout`, `settings`, `navigation`, `search` и
`reading` доступны на уровне сайта, раздела и страницы. `preset`, `title` и
`locale` также могут уточняться в section/page descriptor. Только сайт задаёт
`framework_lock`, `content_root`, `base_url` и `default_locale`. Только page
sidecar задаёт `description` и `slug`.

Ветки строгие: неизвестное поле, неправильный тип, пустой `{}` или `[]`
отклоняются. Чтобы очистить ветку, используйте явный reset.

## Presets

Формат содержит два presentation recipe:

- `docs` — брендовая шапка, иерархическая навигация, breadcrumbs, outline и
  previous/next согласно настройкам;
- `landing` — сфокусированная страница без документационного дерева и reading
  context.

Preset не меняет формат содержания. Одна Markdown-страница становится
лендингом через page sidecar:

```json
{
  "schema": "docara.page.v1",
  "title": "Лендинг",
  "preset": "landing",
  "layout": { "max_width": "wide" },
  "navigation": { "hidden": true },
  "search": { "enabled": false, "indexed": false }
}
```

Навигационное дерево выводится из путей Markdown, а не из публичного `slug`.
Overview-файл и одноимённый каталог образуют одну ветку. Текущая страница
получает `aria-current`, предки раскрываются, а мобильная версия использует
нативный disclosure.

Hidden page сохраняет реальную ancestry, но не показывается в меню и
previous/next. `navigation.hidden` не является контролем доступа и не
исключает страницу из поиска автоматически.

## Markdown и компоненты

Raw HTML удаляется, небезопасные ссылки запрещаются. Богатые элементы
вызываются fenced-директивами, а не произвольным HTML или вторым списком в
конфигурации.

Исполняемые возможности принадлежат трём семействам:

- bounded native Markdown;
- typed-компоненты Docara с собственным renderer;
- Smart-компоненты Simai Framework, допущенные точным lock и manifest.

Точная доступность не перечисляется в этом документе. Каждая сборка создаёт:

- `_docara/component-catalog.json`;
- `/components/catalog/`;
- detail-route только для записей lifecycle `supported`.

Недоступная requirement-запись показывает owner, причину, fallback и условие
допуска, но не становится исполняемым синтаксисом.

Производный каталог не является вторым реестром Simai Framework и не может сам
расширить Smart surface. Exact call, параметры, состояния, ограничения,
пример и provenance смотрите в generated catalog конкретной сборки.

## Simai Framework runtime

`simai-framework.lock.json` связывает immutable Core/Smart revisions, manifests,
consumer policy, hashes и asset projection. Moving references вроде `main`,
`master` и `latest` отклоняются.

План ассетов строится детерминированно и проверяется до публикации. Локальные
байты должны совпадать с ожидаемым SHA-256. Наличие runtime-файла без полного
authoring, dependency, accessibility и host contract не означает допуск
компонента.

Текущий переносимый контур может использовать точные сетевые Core-ассеты.
Поэтому PHP-only означает отсутствие Node.js в author/build path, а не
полностью offline browser runtime.

## Explainability и determinism

Сборка пишет `.docara/resolved-page-plans.json`. Для каждой страницы там есть:

- итоговая configuration;
- упорядоченный trace входов с SHA-256;
- provenance каждого значения;
- canonical plan hash;
- нормализованные component calls;
- exact asset plan;
- output и public URL.

Этот файл диагностический. Его нельзя редактировать вместо Markdown или JSON.

Сборка не добавляет timestamp или локальные абсолютные пути. Одинаковые входы и
один lock должны давать byte-identical output.

Если поиск включён, создаются `_docara/search-index.json` и закреплённый
browser runtime. Индекс содержит только публичный текст страниц, locale
изолируется, а внешний сервис поиска не нужен.

## Build, verification и публикация

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Preview работает по HTTP и блокирует терминал до `Ctrl+C`. `file://` не
проверяет маршруты и `base_url`.

Публикация должна брать только успешно проверенный каталог: build → verify →
staging → digest comparison → smoke → switch → rollback при ошибке. Секреты и
credentials не входят в source или output Docara.

## Security boundary

- configuration paths относительны и остаются внутри корня сайта;
- root/content/lock/destination symlink отклоняется;
- schemas запрещают неизвестные поля;
- unsafe Markdown links и raw HTML не исполняются;
- scalar props экранируются host renderer;
- `_docara` и `.docara` зарезервированы;
- brand assets проверяются по пути, типу, размеру и SHA-256;
- output collision проверяется до очистки существующего destination;
- static verifier проверяет маршруты, fragments, assets, search, resolved plans,
  component catalog и Framework projection;
- hidden/search flags не являются авторизацией: готовый HTML публичен.

## Larena import boundary

Standalone Docara интерпретирует content tree, JSON descriptors и Markdown и
выдаёт канонический resolved-plan artifact. Larena может принять его только как
внешний проверяемый input: сверить receipt, hashes и Framework lock, затем
применить собственные application contracts.

Standalone-формат не зависит от внутренних классов Larena.

## Release boundary

Этот контракт не является заявлением о production, полном покрытии компонентов,
полностью offline runtime или готовом публичном release. Публикация пакета,
тега и upstream-ассетов требует отдельной exact-candidate, license и owner
приёмки. Legacy-репозитории нельзя архивировать только на основании этой
документации.
