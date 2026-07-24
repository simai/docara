# Docara header navigation plan

Status: completed
Track: `docara-consolidation`
Process model: `docara_documentation_site_publication`

## Follow-up visual correction

The framework menu component exposes independent logical and physical border
width properties. Setting only `--sf-menu-element--border-width` removed the
top and bottom borders but left the horizontal navigation with one-pixel
inline separators. The published Framework component CSS also repeats physical
inline widths as literal `1px`, so its directional custom properties currently
do not affect the computed border. Docara sets all supported directional
properties and a narrow logical `border-block-width` / `border-inline-width`
fallback to the Framework zero-size token. Active, hover and `focus-visible`
states stay unchanged. The fallback can be removed after the Framework
generator stops emitting the duplicate literal declarations.

## Current Goal

Спроектировать настраиваемое мультиязычное верхнее меню Docara, которое
использует существующую декларативную модель, Smart-компонент навигации и один
адаптивный мобильный сценарий.

## Primary scenario

Владелец сайта задаёт ключевые ссылки отдельно для каждой языковой версии.
Читатель видит их горизонтально в шапке, а на узком экране открывает те же
ссылки одной кнопкой меню вместе с навигацией документации.

## Architecture decision

### Configuration ownership

- `docara.json` продолжает хранить общесайтовые механические настройки:
  locales, routing, branding, layout, theme and search.
- Состав и подписи верхнего меню принадлежат языковому контенту и задаются в
  корневом `content/<locale>/section.json`.
- Настройка называется `header_navigation`, чтобы не смешивать её с
  `navigation`, которая уже управляет включением страницы в дерево
  документации.
- Корневой `section.json` наследуется всеми страницами locale. Вложенный
  раздел или page sidecar при необходимости может отключить или полностью
  заменить список.
- Списки заменяются целиком при наследовании. Это позволяет языковым версиям
  иметь разное количество и порядок пунктов без выравнивания по индексам.

Recommended authoring form:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {
        "id": "features",
        "label": "Возможности",
        "href": "/ru/#features"
      },
      {
        "id": "documentation",
        "label": "Документация",
        "href": "/ru/quick-start/"
      },
      {
        "id": "components",
        "label": "Компоненты",
        "href": "/ru/components/"
      },
      {
        "id": "github",
        "label": "GitHub",
        "href": "https://github.com/simai/docara"
      }
    ]
  }
}
```

Another locale is independent:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {
        "id": "docs",
        "label": "Docs",
        "href": "/en/"
      },
      {
        "id": "github",
        "label": "GitHub",
        "href": "https://github.com/simai/docara"
      }
    ]
  }
}
```

Absence of `header_navigation` or `enabled: false` disables the menu without
emitting an empty wrapper, trigger, assets or accessibility label.

### Data contract

`header_navigation`:

- `enabled`: boolean;
- `items`: ordered list, maximum 8;
- `items[].id`: required stable language-independent identifier;
- `items[].label`: required localized visible label;
- `items[].href`: required safe internal path, fragment or HTTPS URL.

Version 1 deliberately has one level. Dropdowns, arbitrary HTML, icons,
per-item CSS and executable callbacks are excluded. The header is for a small
set of key destinations, while the left documentation navigation remains the
multi-level tree.

### Component model

- Do not create a second generic menu engine.
- Extend the existing product-owned Smart component `docara.navigation` with a
  `header` view/preset.
- Add a `header_navigation` binding to the page composition context. It maps
  authored records into the existing typed navigation item model and resolves
  `active` from the current canonical URL.
- Extend the built-in `docara.header` section so it composes
  `docara.brand` and the `header` view of `docara.navigation`.
- Keep search, locale selector and reader settings as publisher chrome actions
  on the right.
- Use Simai Framework menu/button/icon primitives and layout utilities. Add
  Docara CSS only for the responsive composition that cannot be expressed by
  existing utilities.

## Responsive behaviour

### Desktop

Header structure:

```text
[brand] [primary navigation................] [search] [language] [settings]
```

- horizontal, one line;
- menu occupies the flexible middle area;
- no text clipping or wrapping inside an item;
- current internal destination uses `aria-current="page"` and the Framework
  active state;
- 3–5 items is the recommended authoring range, while the schema accepts up
  to 8.

### Narrow desktop and mobile

- horizontal navigation is replaced by one menu trigger before search;
- do not create a second competing hamburger;
- the existing Docara mobile navigation sheet becomes the single mobile
  navigation surface;
- configured key links appear first under a localized heading;
- the documentation tree follows under its own heading when the page uses the
  `docs` preset;
- on a landing without a documentation tree, the same sheet contains only the
  key links;
- the trigger is omitted when both key links and documentation navigation are
  absent.

The first implementation should use a deterministic Framework breakpoint.
Runtime overflow measurement and an `Ещё` dropdown are deferred until real
sites prove they are necessary.

## States

- disabled / absent;
- enabled with items;
- active internal item;
- external item;
- desktop horizontal;
- mobile sheet closed/open;
- docs sheet with both primary links and documentation tree;
- landing sheet with only primary links;
- long localized label;
- RTL locale;
- keyboard focus and Escape close.

There is no loading state because the menu is compiled into static HTML. An
invalid or empty enabled configuration is a build-time validation error, not a
partially rendered runtime state.

## Implementation surfaces

1. Add `header_navigation` to `presentation.schema.json`, `site.schema.json`,
   `section.schema.json` and `page.schema.json`.
2. Resolve and validate it through the existing configuration inheritance
   chain.
3. Add the typed projection to `PageCompositionContext`.
4. Add `header` view/template and manifest state to `docara.navigation`.
5. Compose the new binding in `docara.header`.
6. Reuse/extend the existing mobile navigation dialog rather than adding a
   second modal.
7. Add localized chrome labels to every bundled language pack.
8. Add a documentation-site example and user documentation showing
   independent per-locale menus. Keep the universal starter menu-free.

## Done when

- Russian and English fixtures build with different item counts and labels.
- Missing or disabled configuration produces no empty navigation surface.
- Desktop renders the horizontal menu between brand and actions.
- Mobile exposes one menu trigger and one sheet.
- Internal active state, external URL, RTL, keyboard focus, Escape and
  no-JavaScript link navigation are verified.
- Schema rejects unsafe URLs, duplicate ids, more than 8 items, nesting,
  arbitrary HTML and unknown properties.
- Production build and `verify-static` pass.
- Browser acceptance passes at wide desktop, narrow desktop and mobile without
  clipping, overlap or horizontal overflow.

## Non-goals for version 1

- dropdown or mega menu;
- automatic translation or forced item parity between locales;
- database-backed navigation;
- arbitrary author templates, HTML or CSS;
- a second generic Smart component that duplicates `docara.navigation`;
- JavaScript overflow calculation or automatic `Ещё` grouping.

## Risks

- Brand, long localized labels and search can compete for width. The first
  release avoids clipping by switching the complete navigation to the mobile
  pattern at a deterministic breakpoint.
- Two separate mobile menu triggers would be confusing. The key links and the
  documentation tree must share one existing sheet.
- Putting localized labels in `docara.json` would mix global mechanics with
  language content and make arbitrary locale sets harder to maintain; the
  locale-root `section.json` avoids that coupling.

## Result

The bounded implementation is complete:

- `header_navigation` is a strict inherited presentation contract;
- localized item lists live in each locale-root `section.json`;
- `docara.navigation` owns both the horizontal header view and the compact
  mobile projection;
- the existing mobile navigation dialog remains the only mobile surface;
- LTR and RTL use logical positioning;
- user documentation, schema coverage, runtime coverage, static verification
  and browser acceptance pass.

## Progress

### Batch 1 — contract and projection

- Status: completed
- Confirmed:
  - `header_navigation` belongs to the inherited presentation configuration;
  - `docara.navigation` remains the only menu Smart component;
  - the existing mobile navigation sheet is the only narrow-screen surface;
  - localized labels belong to `content/<locale>/section.json`.

### Batch 2 — Smart view and responsive composition

- Status: completed
- Added:
  - official `header` view and templates for `docara.navigation`;
  - brand + primary navigation composition in `docara.header`;
  - one mobile trigger and one dialog containing primary links and, for docs
    pages, the documentation tree;
  - active-state projection and logical RTL drawer positioning.

### Batch 3 — documentation, QA and local publication

- Status: completed
- Automated acceptance:
  - full PHPUnit suite passed;
  - independent RU, EN and AR item counts passed;
  - formatter and `git diff --check` passed;
  - production build and deployed static verification passed.
- Browser acceptance:
  - wide desktop, narrow desktop, 390 px mobile and AR RTL passed;
  - keyboard Escape closes the dialog and returns focus;
  - no horizontal overflow or browser console errors.
- Evidence:
  `source/workflow/evidence/2026-07-24-docara-header-navigation/acceptance.md`.
