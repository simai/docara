# Просмотр декларативной цепочки

Docara параллельно собирает два результата из одного набора Markdown и JSON:

1. основной сайт через принятый publisher;
2. изолированный declarative preview через новый разделённый конвейер.

Preview нужен для проверки архитектуры до переключения основного publisher.
Он не заменяет основной сайт, не заявляет полную визуальную идентичность и не
является production-readiness claim.

## Что открыть

После production-сборки из `docs/site` доступны:

| Что | URL на локальном сайте | Файл результата |
| --- | --- | --- |
| Основной сайт | `https://docara.test/` | `docs/site/build_production/index.html` |
| Каталог preview | `https://docara.test/_docara/declarative-preview/` | `docs/site/build_production/_docara/declarative-preview/index.html` |
| Machine-readable receipt | `https://docara.test/_docara/declarative-preview/index.json` | `docs/site/build_production/_docara/declarative-preview/index.json` |
| Preview главной | `https://docara.test/_docara/declarative-preview/pages/` | `docs/site/build_production/_docara/declarative-preview/pages/index.html` |
| Основная диагностика | `https://docara.test/.docara/resolved-page-plans.json` | `docs/site/build_production/.docara/resolved-page-plans.json` |

Каталог показывает каждую авторскую страницу. Для поддержанной страницы
доступны две ссылки: declarative preview и основной результат. Если компонент
ещё не вошёл в вертикальный срез, каталог показывает `Только legacy` и его
точный Smart ID.

## Полная последовательность

```text
Markdown + docara.json + section.json + *.page.json + Framework lock
  -> загрузка и schema validation
  -> Markdown/Smart parsing
  -> DocumentAst
  -> navigation topology + active state + outline
  -> PageCompositionContext
  -> ResolvedRenderPlan
  -> trusted Smart rendering
  -> RenderArtifact
  -> semantic parity + shell structural parity
  -> Larena contract projection
  -> preview-only internal-link projection
  -> trusted full-document template
  -> preview HTML + deterministic JSON receipt
  -> static verifier
```

### 1. Входные данные

- `docs/site/docara.json` задаёт сайт и общий build contract.
- `docs/site/content/section.json` и вложенные `section.json` задают
  наследуемые настройки разделов.
- `docs/site/content/*.page.json` задают параметры отдельных страниц.
- `docs/site/content/**/*.md` содержит контент.
- `docs/site/simai-framework.lock.json` фиксирует exact revisions и ассеты
  Simai Framework.

Portable loader проверяет JSON schemas и собирает `ResolvedPagePlan` с trace
всех участвующих файлов.

### 2. Контент без разметки

`src/Declarative/Document/DocumentParser.php` разбирает Markdown и Smart-вызовы
в типизированный `DocumentAst`. В нём находятся узлы Markdown, заголовки,
ссылки, Smart calls и координаты источника, но нет HTML.

### 3. Макет и области

- `resources/layouts/docara.docs.json` объявляет layout и области;
- `resources/sections/*.json` описывают состав областей;
- `resources/blocks/*.json` описывают блоки;
- `resources/views/*.json` описывает безопасный каркас Layout и слоты Section;
- `resources/framework/view-utilities.json` фиксирует допустимые утилиты
  Simai Framework;
- `resources/smart/*` содержит manifests и view descriptors.

После построения меню и outline
`src/Declarative/Composition/PageCompositionContext.php` передаёт компилятору
branding, активное дерево навигации и оглавление. Затем
`src/Declarative/DeclarativePageCompiler.php` собирает неизменяемый план:

```text
Page -> Region -> Section -> Block -> Smart
```

### 4. Trusted rendering

`src/Declarative/Rendering/DeclarativePageRenderer.php` превращает план в
`RenderArtifact`. Пути к PHP-шаблонам не приходят из контента:
`ViewTreeRenderer` строит каркас только из разрешённых тегов, атрибутов и
Framework-утилит. `TrustedTemplateRegistry` допускает только известные renderer
IDs; сложное меню использует зарегистрированный Blade leaf и получает
подготовленную immutable view model. Пользовательский JSON не содержит
шаблонов или путей.

### 5. Доказательство эквивалентности

До публикации preview выполняются две независимые проверки:

- `SemanticParityChecker` сравнивает title, текст, regions, headings, links и
  Smart semantics с принятым результатом;
- `ShellStructuralParityChecker` сравнивает branding, меню, active state и
  outline.

Тот же план проецируется через
`src/Declarative/Adapter/LarenaContractAdapter.php`. Это доказывает
совместимость модели layout/region/section/block/Smart, но не превращает
Docara в Larena.

### 6. Preview publication

- `DeclarativePreviewRouteMap` создаёт изолированные URL и output paths;
- `DeclarativePreviewLinkProjector` переводит только известные внутренние
  ссылки на preview-маршруты и сохраняет исходный адрес в
  `data-docara-original-href`;
- `DeclarativePreviewRenderer` собирает полный документ через фиксированные
  templates `resources/previews/templates/page.php` и `index.php`;
- `PortableSiteBuilder` записывает HTML и deterministic receipt.

Основной `PortableHtmlRenderer` не меняется. Unsupported-страница остаётся в
основном сайте и получает в receipt явный статус `skipped`.

### 7. Проверка результата

`scripts/verify-static-build.php` fail-closed проверяет:

- schema, locale и documentation version receipt;
- точное соответствие route map и списка preview-страниц;
- отсутствие неизвестных HTML-файлов;
- SHA-256 каждого preview HTML;
- безопасные regular files без symlink/hardlink;
- обязательные nonclaims.

Команды:

```bash
cd docs/site
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build production
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara verify-static build_production
```

После сборки сначала откройте каталог preview, затем одну и ту же страницу в
preview и основном сайте. В preview внутренние ссылки между поддержанными
страницами остаются внутри preview; кнопка `Открыть legacy` всегда возвращает
к основному результату.

## Где искать проблему

| Симптом | Сначала посмотреть |
| --- | --- |
| Страница имеет `Только legacy` | `index.json` → `unsupported_components` |
| Неверный контент | `DocumentAst`, semantic parity в `resolved-page-plans.json` |
| Неверное меню или outline | shell structural parity и `PageCompositionContext` |
| Неверный Smart | manifest, view descriptor, trusted template и asset plan |
| Неверная ссылка preview | route map и `data-docara-original-href` |
| Build отвергнут | вывод `verify-static` и receipt SHA-256 |

Preview можно удалить вместе с каталогом `_docara/declarative-preview` без
влияния на основной сайт. Переключать publisher можно только отдельной целью
после поддержки требуемых Smart-компонентов и независимой приёмки.
