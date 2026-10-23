---
description: Как устроен интерфейс Docara — макет задаёт области, секция наполняет их блоками, Smart-компонент и представление рисуют элемент; цепочка вставки, матрица интерфейса, вклад проекта в оболочку и просмотр.
tags: [композиция, дизайн, layout, section, block, atlas, preview]
---

# Композиция интерфейса

Docara собирает интерфейс как LEGO на трёх уровнях: page Layout задаёт регионы, Section наполняет region разрешёнными slots/blocks, а Smart/View рисует конечный элемент. Это конфигурация из принятых registry descriptors, а не второй шаблонный движок.

## Атлас проекта

:::atlas_index {kind=layout,section,block,view,binding,preset}
:::

Atlas строится командой `docara atlas --json` только из admitted DesignRegistry, SmartRegistry и BindingRegistry. Его fingerprint входит в build-owned projection; ручного списка компонентов здесь нет.

## Композиционная модель

### Три уровня

1. **Layout** владеет геометрией страницы и именами regions.
2. **Section** допускается в совместимый region и раскладывает Blocks по slots.
3. **Block/Smart/View** получает проверенные данные и создаёт конечный render artifact через общие registry/Gateway.

Content Markdown компилируется отдельно в typed in-memory Document IR. Длина `:::` fence не выбирает уровень архитектуры.

Проверенные настройки и прочитанный Markdown превращаются в общий Recipe.
Стандартный сборщик SIMAI Framework создаёт Composition Document — готовое
дерево страницы. Docara восстанавливает из него вход своих рендереров и
создаёт HTML. Авторские файлы при этом не переписываются.

### Реальная insertion chain

| Шаг | Реальный зарегистрированный файл | Ответственность |
| --- | --- | --- |
| Layout | `resources/layouts/docara.docs.json` | regions, required flags, container limits |
| Layout View Tree | `resources/views/layout.docara.docs.json` | безопасные element/region nodes |
| Region | descriptor-owned `header`, `sidebar`, `main`, `outline`, `footer` | insertion point, не компонент ID branch |
| Section | `resources/sections/docara.article.json`, `docara.header.json`, `docara.navigation.json`, `docara.outline.json`, `docara.shell.json` | compatible regions и allowed blocks |
| Section View | `resources/views/section.docara.article.json`, `section.docara.header.json`, `section.docara.shell.json` | admitted slots |
| Block | `resources/blocks/content.document.json`, `content.markdown.json`, `content.smart.json`, `shell.element.json`, `shell.smart.json` | payload schema/capability |
| Smart | package, Framework или project artifact | props/views/presets/slots/assets/hydration |
| View | registered view/preset/template | конечный HTML/assets/provenance |
| Surface presentation | `src/PortableSite/SurfacePresentation.php` | единая admitted outer geometry для Surface, Hero, Showcase и Promo; semantic content остаётся у компонента |

```text
Markdown owner
  -> typed Document IR
  -> Layout docara.docs
  -> region main
  -> Section docara.article
  -> slot content
  -> Block content.document
  -> generated Composition Recipe
  -> Framework resolveRecipe
  -> Composition Document
  -> Docara renderer projection
  -> SmartComponentGateway when a Smart leaf is present
  -> registered View
  -> LayoutComposer
  -> PageBuilder result
```

### Кто проверяет совместимость

Atlas публикует для каждого container `allowed_children`, `slots`, `min_children`,
`max_children`, `order`, `max_depth` и machine-readable `depth_semantics`.
Значение `relative_subtree_root_level_1` означает, что каждый container считает
собственный root уровнем 1. Registry отклоняет unknown View Tree
kind/tag/attribute/utility/region/slot до registration. Project config выбирает
только admitted IDs и не содержит class/callback/PHP/template path.

:::atlas_index {kind=layout,section,block}
:::

### Общая presentation не меняет semantic owner

Surface — authored content container. Hero, Showcase и Promo — отдельные typed
semantic definitions. Их Atlas ownership, states, props и provenance берутся из
реальных admitted definitions, но outer width/container/background frame
собирает одна immutable Surface presentation. Оборачивать эти semantic blocks
в Surface нельзя; page/head и границы region по-прежнему принадлежат
LayoutComposer.

## Матрица интерфейса

Один publisher shell обслуживает docs, site и landing presets. Варианты меняют зарегистрированное presentation, а не renderer path.

| Поверхность | Effective owner | Capability / presentation | Можно заменить проектом |
| --- | --- | --- | --- |
| Brand | `docara.brand` / branding binding | full, compact, logo, text | через admitted branding capability |
| Header navigation | `docara.navigation` | `header` | да |
| Sidebar tree | `docara.navigation` | `tree` | да |
| Mobile/compact navigation | `docara.navigation` | `compact` | да |
| Outline / TOC | `docara.toc` | outline binding | да |
| Search | registered search Smart leaf | shell search capability | да |
| Breadcrumbs | registered breadcrumbs Smart leaf | reading context | да |
| Previous/next pager | registered pager Smart leaf | reading context | да |
| Reader preferences | `docara.preferences` | side panel | да |
| Footer | registered/project shell Smart | footer capability | да |
| Outer document, `<head>`, canonical, metadata, asset ledger | PageBuilder/application | application-owned | нет |

### Navigation presentations

`header`, `tree` и `compact` — три presentation одного `docara.navigation`. BindingRegistry выбирает их по capability и provider descriptor; Gateway, renderer, LayoutComposer и PageBuilder остаются теми же.

:::atlas_index {kind=binding}
:::

### Граница page/head

Project contribution не может заменить canonical URL, locale metadata, build receipt, search/navigation provenance или произвольный `<head>` fragment. Эти поверхности остаются application-owned, чтобы static verification и full/single parity были проверяемыми.

## Вклад проекта в оболочку

Project-owned shell contribution — это data-only artifact в разрешённом `design/`/`smart/` root и ссылка на admitted binding ID. Он не требует изменения engine `src/`.

### Порядок подключения

1. Выберите собственный namespace в `docara.json`.
2. Создайте artifact через hash-bound scaffold dry-run/apply.
3. Проверьте manifest/schema/namespace/capabilities.
4. Убедитесь через `docara atlas --json`, что owner=`project` и support=`project`.
5. Выполните isolated preview на реальной странице.
6. Только затем соберите full output.

:::atlas_index {support=project}
:::

### Fail-closed границы

- project ID не затеняет package/Framework ID;
- binding-owned props нельзя переопределить config-ом;
- capability/region/slot collision отклоняется;
- traversal, symlink, hardlink, case collision и arbitrary path отклоняются;
- shell contribution не меняет outer page/head.

Пример footer из принятого Goal B показывает shell-role, а install builder и product configurator — content-role. Все три проходят существующие registries и Gateway.

## Просмотр по пути сборки

Preview не имеет собственного renderer. `PreviewKernel` вызывает те же parser/compiler, DesignRegistry, SmartComponentGateway, renderer, asset collector, LayoutComposer и PageBuilder; output помечен preview-purpose и production verifier его не принимает.

### Полная композиция

:::internal_preview {size=tall title="Композиция regions"}
[Открыть результат](/ru/demonstrator-results/regions-composed/)
:::

### Docs preset

:::internal_preview {size=tall title="Docs preset"}
[Открыть результат](/ru/demonstrator-results/preset-docs/)
:::

### Landing preset

:::internal_preview {size=tall title="Landing preset"}
[Открыть результат](/ru/demonstrator-results/preset-landing/)
:::

Команда `docara preview smart|region|layout|page` публикует isolated tree в `.docara-preview/output/<target>/`. `verify-static` обязан вернуть `PREVIEW_BUILD_PURPOSE_FORBIDDEN`; для production нужен обычный full build.

## Свои макеты и блоки проекта

Проект может добавить свой Layout, Section, Block и безопасный View Tree в
каталог `design/`. Менять движок для этого не нужно.

### Цепочка проекта

```text
Layout → Region → Section → Slot → Block → Smart или безопасный element
```

Все определения читает один `DesignRegistry`. Встроенные ID принадлежат
пакету, project ID — namespace из `docara.json`:

```json
{"smart":{"namespace":"project"}}
```

Project artifact должен начинаться с `project.`. Он не может заменить
`docara.*`, `content.*`, `shell.*` или `ui.*`. Дубликаты, symlink, выход из
корня и неизвестные определения останавливают сборку.

Команда `docara atlas --json` показывает effective Layout, View, Section,
Block, Smart, binding и preset из принятых registries. Atlas — только
детерминированная проекция: редактировать его как источник регистрации нельзя.
Для контейнеров он также показывает допустимые children, slots, порядок,
количество и максимальную глубину.

### Привязки данных оболочки

Блок `shell.smart` может выбрать только зарегистрированную привязку `bind`.
Привязка соединяет данные страницы с уже зарегистрированным Smart-компонентом;
она не является PHP callback или путём к шаблону. Встроенный реестр содержит:

| ID | Возможности | Smart |
| --- | --- | --- |
| `docara.branding` | `shell.brand` | `docara.brand` |
| `docara.navigation` | `shell.primary-navigation`, `shell.secondary-navigation` | `docara.navigation` |
| `docara.outline` | `shell.outline` | `docara.toc` |

Проверьте effective descriptor и его происхождение командами:

```bash
docara list binding
docara inspect binding docara.navigation
docara schema binding
```

Layout region и Section объявляют совместимые capabilities. Сборка до render
отклонит неизвестную привязку, несовместимую capability, попытку передать
binding-owned props или подменить package ID. Project JSON не может задавать
class, callback, PHP/template path или произвольный filesystem path.

Один `docara.navigation` поддерживает presentations `header`, `tree` и
`compact`. Вызов остаётся тем же `shell.smart`, Gateway и PageBuilder; меняется
только зарегистрированный `view`. Если `view` явно не указан, сохраняется
совместимое поведение по умолчанию.

Starter содержит небольшой footer-пример: project Section и Block допускают
только зарегистрированный `project.footer-links` в region `footer`. Ни config,
ни Markdown не выбирают PHP/template path; component, View и assets разрешает
обычный project Smart provider.

### Файлы

```text
design/
├── layouts/project.docs.json
├── sections/project.article.json
├── blocks/project.document.json
└── views/
    ├── layout.project.docs.json
    └── section.project.article.json
```

Layout задаёт безопасный View Tree, regions, их defaults и место документа:

```json
{
  "schema": "docara.layout.v1",
  "key": "project.docs",
  "default": false,
  "view": "layout.project.docs",
  "configuration": {
    "container": {"max": 7},
    "scrollbar": {"preset": "overlay"},
    "content": {"gap": 0}
  },
  "document": {
    "region": "stage",
    "section": "project.article",
    "slot": "content",
    "block": "project.document"
  },
  "regions": {
    "stage": {
      "required": true,
      "default_enabled": true,
      "default_sections": [],
      "section_types": ["content"]
    }
  },
  "assets": []
}
```

Section объявляет совместимые regions, slots и blocks. Block выбирает один из
уже зарегистрированных безопасных renderer kinds. JSON не принимает PHP,
template path, callback, произвольный HTML или CSS.

Чтобы выбрать project layout, сбросьте унаследованную ветку:

```json
{
  "layout": {
    "$reset": true,
    "key": "project.docs"
  }
}
```

### Предпросмотр

Preview использует обычный `PortableSiteBuilder`, PageBuilder, registries,
Smart gateway, renderer и layout composer. Результат изолирован в
`.docara-preview/` и не считается production build receipt. Cache receipt
имеет `build.purpose=preview`, поэтому `verify-static` завершится ошибкой
`PREVIEW_BUILD_PURPOSE_FORBIDDEN`. Это защита самого receipt, а не соседний
служебный marker.

```bash
docara preview page --page=/ru/components/alert/
docara preview layout --page=/ru/components/alert/
docara preview region --page=/ru/components/alert/ --selector=main
docara preview smart --page=/ru/components/alert/ --selector=ui.alert
```

Для автоматизации добавьте `--json`. Команда watch следит за effective input
chain выбранного route, locale UI-copy и реально разрешёнными layout, section,
block, Smart, template и asset dependencies из package/project providers:

```bash
docara preview region --page=/ru/components/alert/ --selector=main --watch
```

`artifact.html` содержит точный выбранный fragment, `index.html` — ту же
production page для проверки в браузере, `preview.json` — assets, provenance,
dependencies и hashes. В том же preview-root публикуются нужные local CSS, JS,
Framework и content assets, поэтому открывайте `index.html` через HTTP именно
из `.docara-preview/output/<target>/`. Production receipt и прочие HTML routes
туда не копируются. Normal `build_*` preview не перезаписывает.

Изменение нерелевантного project Smart не пересобирает выбранный target.
Изменение, появление или удаление файла внутри реально выбранного project или
package artifact вызывает одну single-page пересборку. `target_only=true`
выводится из scope и результата пересборки, а не задаётся константой.

### Проверка

```bash
php vendor/bin/phpunit --filter 'DesignRegistryTest|ProjectDesignCompositionTest|PreviewKernelTest'
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

Не добавляйте ID проекта в engine PHP или schema enum. Если нужен новый
исполняемый renderer/API, это отдельное расширение платформы, а не project
JSON.

## См. также

- [Области макета](/ru/settings/layout-and-regions/) — настройка областей автором сайта
- [Примеры для разработчиков](/ru/development/examples/)
