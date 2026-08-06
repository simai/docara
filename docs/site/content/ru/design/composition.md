# Композиционная модель

## Три уровня

1. **Layout** владеет геометрией страницы и именами regions.
2. **Section** допускается в совместимый region и раскладывает Blocks по slots.
3. **Block/Smart/View** получает проверенные данные и создаёт конечный render artifact через общие registry/Gateway.

Content Markdown компилируется отдельно в typed in-memory Document IR. Длина `:::` fence не выбирает уровень архитектуры.

## Реальная insertion chain

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

```text
Markdown owner
  -> typed Document IR
  -> Layout docara.docs
  -> region main
  -> Section docara.article
  -> slot content
  -> Block content.document
  -> SmartComponentGateway when a Smart leaf is present
  -> registered View
  -> LayoutComposer
  -> PageBuilder result
```

## Кто проверяет совместимость

Atlas публикует для каждого container `allowed_children`, `slots`, `min_children`,
`max_children`, `order`, `max_depth` и machine-readable `depth_semantics`.
Значение `relative_subtree_root_level_1` означает, что каждый container считает
собственный root уровнем 1. Registry отклоняет unknown View Tree
kind/tag/attribute/utility/region/slot до registration. Project config выбирает
только admitted IDs и не содержит class/callback/PHP/template path.

:::atlas_index {kind=layout,section,block}
:::
