# Макеты и блоки проекта

Проект может добавить свой Layout, Section, Block и безопасный View Tree в
каталог `design/`. Менять движок для этого не нужно.

## Как устроена композиция

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

## Файлы

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

## Предпросмотр

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

Для автоматизации добавьте `--json`. PHP-only watch следит за effective input
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

## Проверка

```bash
php vendor/bin/phpunit --filter 'DesignRegistryTest|ProjectDesignCompositionTest|PreviewKernelTest'
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

Не добавляйте ID проекта в engine PHP или schema enum. Если нужен новый
исполняемый renderer/API, это отдельное расширение платформы, а не project
JSON.
