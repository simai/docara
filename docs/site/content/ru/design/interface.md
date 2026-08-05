# Матрица интерфейса

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

## Navigation presentations

`header`, `tree` и `compact` — три presentation одного `docara.navigation`. BindingRegistry выбирает их по capability и provider descriptor; Gateway, renderer, LayoutComposer и PageBuilder остаются теми же.

:::atlas_index {kind=binding}
:::

## Граница page/head

Project contribution не может заменить canonical URL, locale metadata, build receipt, search/navigation provenance или произвольный `<head>` fragment. Эти поверхности остаются application-owned, чтобы static verification и full/single parity были проверяемыми.
