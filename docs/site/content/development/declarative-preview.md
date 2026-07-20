# Декларативный preview

Docara собирает новый разделённый rendering pipeline параллельно с принятым
publisher. Благодаря этому весь результат можно открыть в браузере, не
переключая основной сайт.

## Что открыть

- [Основной сайт](/)
- [Каталог declarative preview](/_docara/declarative-preview/)
- [Machine-readable receipt](/_docara/declarative-preview/index.json)
- [Preview главной страницы](/_docara/declarative-preview/pages/)
- [Resolved page plans](/.docara/resolved-page-plans.json)

В каталоге каждая авторская страница помечена как собранная или пропущенная.
Для собранной страницы можно перейти в preview либо открыть принятый результат.
Для пропущенной страницы показывается неподдержанный Smart-компонент.

## Как движутся данные

:::steps
1. `docara.json`, `section.json`, `*.page.json`, Markdown и Framework lock проходят schema validation.
2. Markdown и Smart-вызовы преобразуются в типизированный `DocumentAst` без HTML.
3. Меню, active state и outline образуют `PageCompositionContext`.
4. Layout, sections, blocks и Smart manifests разрешаются в `ResolvedRenderPlan`.
5. Trusted renderer создаёт `RenderArtifact` через фиксированные templates и immutable view models.
6. Semantic и shell structural parity сравнивают результат с принятым publisher.
7. Тот же план проверяется Larena contract adapter.
8. Preview projector переводит известные внутренние ссылки в изолированное дерево.
9. Builder пишет preview HTML и deterministic JSON receipt.
10. Static verifier проверяет маршруты, hashes, build identity и полный список HTML.

:::

## Где находятся части

| Часть | Путь в репозитории |
| --- | --- |
| Контент документации | `docs/site/content` |
| Настройки сайта | `docs/site/docara.json` |
| Layout | `resources/layouts/docara.docs.json` |
| Sections | `resources/sections` |
| Blocks | `resources/blocks` |
| Smart manifests и views | `resources/smart` |
| Parser и AST | `src/Declarative/Document` |
| Composition context | `src/Declarative/Composition` |
| Compiler и plan | `src/Declarative/DeclarativePageCompiler.php`, `src/Declarative/Plan` |
| Trusted rendering | `src/Declarative/Rendering` |
| Preview routing и rendering | `src/Declarative/Preview` |
| Preview templates | `resources/previews/templates` |
| Builder | `src/PortableSite/PortableSiteBuilder.php` |
| Static verifier | `scripts/verify-static-build.php` |

## Как читать receipt

`/_docara/declarative-preview/index.json` содержит:

- `build` — locale и documentation version;
- `index` — URL, output и SHA-256 каталога;
- `routes` — точное соответствие legacy и preview маршрутов;
- `pages` — статус каждой авторской страницы, output, hash и unsupported IDs;
- `nonclaims` — явное подтверждение, что основной publisher не переключён.

:::ui.alert
{"type":"info","variant":"outlined","title":"Shadow mode","supporting-text":"Preview доказывает работоспособность нового конвейера, но пока не заменяет основной publisher и не заявляет полную визуальную идентичность.","closable":false,"aria-label":"Граница декларативного preview"}

:::

Если страница содержит компонент вне текущего вертикального среза, она
продолжает собираться основным publisher и остаётся видимой в каталоге со
статусом `Только legacy`.
