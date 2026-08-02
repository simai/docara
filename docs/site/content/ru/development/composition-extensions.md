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
`.docara-preview/` и не считается production build receipt.
В manifest это явно записано как `accepted_build_receipt=false`.

```bash
docara preview page --page=/ru/components/alert/
docara preview layout --page=/ru/components/alert/
docara preview region --page=/ru/components/alert/ --selector=main
docara preview smart --page=/ru/components/alert/ --selector=ui.alert
```

Для автоматизации добавьте `--json`. PHP-only watch следит только за input
chain выбранного route и его project design/Smart/assets:

```bash
docara preview region --page=/ru/components/alert/ --selector=main --watch
```

`artifact.html` содержит точный выбранный fragment, `index.html` — ту же
production page для проверки в браузере, `preview.json` — assets, provenance,
dependencies и hashes. Normal `build_*` preview не перезаписывает.

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
