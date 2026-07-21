# Декларативный preview

Основной portable-сайт Docara уже собирается разделённым декларативным
publisher. Этот раздел показывает дополнительный preview того же плана для
диагностики областей, Smart-компонентов и внутренних ссылок.

## Что открыть

- [Главная страница внутри preview](/)
- [Каталог declarative preview](/ru/_docara/declarative-preview/)
- [Machine-readable receipt](/ru/_docara/declarative-preview/index.json)
- [Preview главной страницы](/ru/_docara/declarative-preview/pages/)
- [Resolved page plans](/.docara/resolved-page-plans.json)

В каталоге каждая авторская страница помечена как собранная или пропущенная.
Для собранной страницы можно перейти в preview либо открыть основной
декларативный результат. Неподдержанный Smart-компонент останавливает сборку,
поэтому preview не маскирует неполную основную публикацию.

## Как движутся данные

:::steps
1. `docara.json`, `section.json`, `*.page.json`, Markdown и Framework lock проходят schema validation.
2. Markdown и Smart-вызовы преобразуются в типизированный `DocumentAst` без HTML.
3. Меню, active state и outline образуют `PageCompositionContext`.
4. Layout, sections, blocks и Smart manifests разрешаются в `ResolvedRenderPlan`.
5. Trusted renderer создаёт `RenderArtifact` через фиксированные templates и immutable view models.
6. Semantic и shell structural parity проверяют доверенную проекцию.
7. Тот же план проверяется Larena contract adapter.
8. Preview projector переводит известные внутренние ссылки в изолированное дерево.
9. Builder публикует основной HTML через trusted publisher template и общие shell assets.
10. Builder пишет preview HTML и deterministic JSON receipt.
11. Static verifier проверяет маршруты, hashes, build identity и полный список HTML.

:::

## Где находятся части

| Часть | Путь в репозитории |
| --- | --- |
| Контент документации | `docs/site/content/ru` |
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
| Primary publisher template | `resources/publisher/templates/page.php` |
| Primary shell assets | `resources/portable/declarative-shell.*` |
| Preview templates | `resources/previews/templates` |
| Builder | `src/PortableSite/PortableSiteBuilder.php` |
| Static verifier | `scripts/verify-static-build.php` |

## Как читать receipt

`/ru/_docara/declarative-preview/index.json` содержит:

- `build` — locale и documentation version;
- `index` — URL, output и SHA-256 каталога;
- `routes` — точное соответствие основных и preview маршрутов;
- `pages` — статус каждой авторской страницы, output, hash и unsupported IDs;
- `nonclaims` — статус переключения publisher и явное отсутствие
  `full_visual_parity`/`production_ready` claims.

:::ui.alert
{"type":"info","variant":"outlined","title":"Диагностический режим","supporting-text":"Основной publisher уже декларативный. Preview помогает исследовать тот же план, но не заявляет production readiness или полную визуальную идентичность.","closable":false,"aria-label":"Граница декларативного preview"}

:::

Для аварийного сравнения сохранён явный rollback
`DOCARA_PORTABLE_PUBLISHER=legacy`; по умолчанию он не используется.
