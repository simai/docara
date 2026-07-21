# Просмотр декларативной цепочки

Docara собирает два результата из одного набора Markdown и JSON:

1. основной сайт через декларативный publisher;
2. изолированный declarative preview для диагностики плана и областей.

Preview больше не является кандидатом на переключение. Это диагностическая
поверхность того же `ResolvedRenderPlan`: основной сайт уже публикуется через
цепочку `Layout -> Region -> Section -> Block -> Smart`. Preview не является
production-readiness claim.

## Что открыть

После production-сборки из `docs/site` доступны:

| Что | URL на локальном сайте | Файл результата |
| --- | --- | --- |
| Основной сайт | `https://docara.test/` | `docs/site/build_production/index.html` |
| Каталог preview | `https://docara.test/ru/_docara/declarative-preview/` | `docs/site/build_production/ru/_docara/declarative-preview/index.html` |
| Machine-readable receipt | `https://docara.test/ru/_docara/declarative-preview/index.json` | `docs/site/build_production/ru/_docara/declarative-preview/index.json` |
| Preview главной | `https://docara.test/ru/_docara/declarative-preview/pages/` | `docs/site/build_production/ru/_docara/declarative-preview/pages/index.html` |
| Основная диагностика | `https://docara.test/.docara/resolved-page-plans.json` | `docs/site/build_production/.docara/resolved-page-plans.json` |

Каталог показывает каждую авторскую страницу и связывает диагностический
preview с основным результатом. Неподдержанный Smart-компонент теперь
останавливает основную декларативную сборку до замены или явного допуска.

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
  -> trusted full-document template
  -> primary HTML + immutable shell CSS/JS
  -> preview-only internal-link projection
  -> diagnostic preview HTML + deterministic JSON receipt
  -> transactional candidate promotion
  -> static verifier
```

### 1. Входные данные

- `docs/site/docara.json` задаёт сайт и общий build contract.
- `docs/site/content/ru/section.json` и вложенные `section.json` задают
  наследуемые настройки разделов.
- `docs/site/content/ru/*.page.json` задают параметры отдельных страниц.
- `docs/site/content/ru/**/*.md` содержит контент.
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

### 5. Доказательство и основная публикация

До основной публикации выполняются две независимые проверки:

- `SemanticParityChecker` сравнивает title, текст, regions, headings, links и
  Smart semantics с принятым результатом;
- `ShellStructuralParityChecker` сравнивает branding, меню, active state и
  outline.

Тот же план проецируется через
`src/Declarative/Adapter/LarenaContractAdapter.php`. Это доказывает
совместимость модели layout/region/section/block/Smart, но не превращает
Docara в Larena.

Основной полный документ выводит зарегистрированный шаблон
`resources/publisher/templates/page.php`. CSS и runtime оболочки публикуются
один раз в `_docara/declarative-shell.css` и
`_docara/declarative-shell.js`; они не дублируются внутри каждой страницы.

Builder сначала формирует отдельный `build_*.docara-candidate`. Только после
успешной генерации всех страниц, ассетов и diagnostics кандидат атомарно
заменяет destination. При ошибке прежний `build_*` остаётся нетронутым.

### 6. Diagnostic preview publication

- `DeclarativePreviewRouteMap` создаёт изолированные URL и output paths;
- `DeclarativePreviewLinkProjector` переводит только известные внутренние
  ссылки на preview-маршруты и сохраняет исходный адрес в
  `data-docara-original-href`;
- `DeclarativePreviewRenderer` собирает полный документ через фиксированные
  templates `resources/previews/templates/page.php` и `index.php`;
- `PortableSiteBuilder` записывает HTML и deterministic receipt.

Принятый `PortableHtmlRenderer` не меняется побайтово и доступен только как
явный rollback: `DOCARA_PORTABLE_PUBLISHER=legacy`. По умолчанию используется
`declarative`.

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
/Applications/ServBay/package/php/8.2/8.2.29/bin/php ../../docara build production
/Applications/ServBay/package/php/8.2/8.2.29/bin/php ../../docara verify-static build_production
```

После сборки сначала откройте каталог preview, затем одну и ту же страницу в
preview и основном сайте. В preview внутренние ссылки между поддержанными
страницами остаются внутри preview; ссылка на основной результат возвращает к
декларативно опубликованной странице.

## Где искать проблему

| Симптом | Сначала посмотреть |
| --- | --- |
| Сборка отклоняет Smart | manifest, consumer policy и trusted template |
| Неверный контент | `DocumentAst`, semantic parity в `resolved-page-plans.json` |
| Неверное меню или outline | shell structural parity и `PageCompositionContext` |
| Неверный Smart | manifest, view descriptor, trusted template и asset plan |
| Неверная ссылка preview | route map и `data-docara-original-href` |
| Build отвергнут | вывод `verify-static` и receipt SHA-256 |

Preview можно удалить вместе с каталогом `_docara/declarative-preview` без
влияния на основной сайт. Это не переключит publisher: основной путь уже
декларативный.
