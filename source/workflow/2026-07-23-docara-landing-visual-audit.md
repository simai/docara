# Docara landing visual and media audit

Date: 2026-07-23
Status: completed_audit
Owner: `docara`
Companions: `ux`, `designer`
Target: `https://docara.test/ru/landing/`
Reference: `https://diplodoc.com/`

## Current Goal

Поблочно сопоставить текущий демонстрационный лендинг Docara с публичным
лендингом Diplodoc, определить недостающие роли изображений и подготовить
минимальный архитектурный план улучшения без копирования React/Page Constructor
и без произвольных пользовательских CSS-классов.

## Scenario

Автор должен собрать выразительный адаптивный лендинг из Markdown, проектных
изображений и зарегистрированных компонентов Docara. Читатель за первый экран
понимает продукт и действие, а ниже получает визуальные доказательства, а не
только последовательность текстовых карточек.

## Verdict

`NEEDS_REVISION`

Текущая страница структурно ясна, компактна и технически аккуратна, но по
визуальной выразительности остаётся документационной страницей. Изображение
поддерживается только в `docara.hero`, `docara.logos` принимает изображения,
но демонстрация использует лишь текст. У остальных блоков нет согласованного
медиа-контракта.

## Evidence

- Browser review: Diplodoc and Docara at a 1296px desktop viewport.
- Diplodoc DOM: 19 content images besides the brand logo.
- Docara DOM: one image, the 32px brand mark in the header.
- Docara source:
  `docs/site/content/ru/landing.md`.
- Docara renderer:
  `src/PortableSite/PortableMarkdownRenderer.php`.
- Diplodoc Page Constructor extension:
  `https://github.com/diplodoc-platform/page-constructor-extension`.

## Reference block map

| Block | Diplodoc visual role | Current Docara | Finding | Recommendation |
| --- | --- | --- | --- | --- |
| Header | Brand, navigation, contact action | Brand and reading settings | `P2`: adequate for docs, sparse for a landing | Landing header preset with optional navigation and primary action; keep current minimal docs preset |
| Hero | Full-width background illustration, product copy, two actions | Contained surface, text, one action, no image in demo | `P1`: correct hierarchy but no product presence | Layout-owned `full` region; hero media slot; primary and optional secondary action; inner text remains in a container |
| Benefits | Six small icons give every benefit a visual anchor | Three equal text cards | `P2`: readable but visually repetitive | Optional leading icon/image in each feature; 24–40px `contain`; no large decorative photo |
| Trust | Brand tabs plus a large real documentation screenshot | Plain text names in a logo grid | `P1`: names do not prove trust or show the product | Use real logo assets and add one `showcase` block with screenshot, caption and link; tabs only after multiple real cases exist |
| Process | Three purpose-made diagrams above explanatory text | Three textual steps | `P2`: sequence is clear, mechanism is abstract | Keep `steps` for sequence; add a separate diagram/showcase or optional step illustrations for genuinely different mechanisms |
| Documentation / landing scenarios | Large illustrated cards with individual actions | Two unframed text columns | `P1`: scenarios are named but not demonstrated | Media cards with screenshot/illustration, title, description and action; equal-height rows and aligned actions |
| Installation proof | Reference uses visual product demonstrations throughout | Code sample only | `P2`: proves command syntax, not resulting experience | Split proof block: command/code plus a real screenshot of generated output |
| Promo CTA | Wide illustrated contribution banner | Single button | `P2`: action lacks context and emotional finish | New `promo` block: H2, short copy, one action and optional decorative media; may use full-width region |
| Community / footer | Recognizable channel icons and links | Not demonstrated | `P3`: optional for generic starter | Reuse image-link form of `logos`; do not add a separate component until required |

## Proposed media vocabulary

Do not expose arbitrary width, height, classes or template paths to authors.
The component context determines the registered media preset:

| Preset | Purpose | Fit | Loading | Accessibility |
| --- | --- | --- | --- | --- |
| `hero` | Product illustration or screenshot | `cover` for decorative art; `contain` for UI | eager, high priority | meaningful alt for informative media; explicit decorative state permits empty alt |
| `feature-icon` | Small benefit marker | `contain`, square | lazy | empty alt when the title already names the concept |
| `showcase` | Product screenshot or customer proof | `contain`, wide | lazy | meaningful alt and optional caption |
| `diagram` | Architecture or process | `contain`, intrinsic ratio | lazy | meaningful alt; optional long explanation in adjacent text |
| `card` | Scenario illustration | registered 4:3 or wide ratio | lazy | alt depends on whether image adds information |
| `logo` | Brand mark | `contain`, intrinsic ratio | lazy | organization name as alt when the image is the only label |
| `promo` | Decorative closing illustration | `cover` or `contain` by registered view | lazy | decorative by default unless it conveys information |

Project images remain under `assets/`. Markdown contains only the image and
alternative text. Renderer/build code adds the preset classes, loading policy,
dimensions and safe URL handling. This keeps authoring simple and avoids one
JSON sidecar per image.

## Component changes

### Extend existing components

1. `docara.hero`
   - layout views: `contained`, `wide`, `full`;
   - content views: `text`, `media-right`, `media-background`;
   - one primary and optional secondary action;
   - informative or explicitly decorative media.
2. `docara.features`
   - optional one leading image/icon per item;
   - retain the current text-only form;
   - keep the two-to-six item limit.
3. `docara.logos`
   - demonstrate real image and linked-image states;
   - preserve intrinsic ratio and bound logo height;
   - keep plain text as fallback.

### Add only two new primitives

1. `docara.showcase`: one large media item with eyebrow/title, description,
   optional caption and link. It covers product screenshots, customer proof and
   architecture demonstrations without adding tabs immediately.
2. `docara.promo`: wide closing banner with H2, short copy, one action and
   optional media. It is semantically different from the H1-only hero.

Do not add a generic Page Constructor, carousel, tabs or arbitrary nested block
tree in this batch. Tabs become justified only when there are at least two real
showcase cases and keyboard/mobile states can be accepted.

## Recommended page composition

1. Full-bleed hero with a real Docara UI/product illustration.
2. Three concise benefits with small icons.
3. Real ecosystem logos.
4. One large product screenshot as evidence.
5. Three process steps plus one explanatory diagram.
6. Two media cards: documentation and landing.
7. Code plus screenshot of the generated result.
8. Full-width promo CTA.

The sequence alternates text density and media scale. Do not place a large
illustration in every section; images must explain, prove, orient or motivate.

## Work packages

### A. Media foundation

- full-width region contract;
- registered media presets and image accessibility states;
- hero/features/logos extensions;
- renderer tests for safe paths, alt/decorative state and responsive output.

### B. Product proof

- `docara.showcase`;
- `docara.promo`;
- real Docara screenshots/illustrations stored in project `assets/`;
- generated catalogue examples.

### C. Demonstration and acceptance

- rebuild `/ru/landing/` with the composition above;
- desktop and mobile, light and dark themes;
- no horizontal overflow;
- retina raster sources at least 2x displayed size;
- intrinsic dimensions or stable aspect ratio to avoid layout shift;
- keyboard/focus checks for every link and any future tabs.

## Not checked

- Diplodoc mobile breakpoints were not accepted: the connected reference tab
  did not adopt the temporary viewport override reliably.
- No new Docara media assets were designed or generated in this audit.
- No product code, build output or local deployment was changed.

## Risks

- Allowing arbitrary component parameters would quickly recreate a generic page
  constructor and weaken Docara's simple authoring model.
- Decorative images with forced descriptive alt create noise for screen-reader
  users; the contract needs an explicit decorative state.
- Heavy raster artwork can negate the static-site performance advantage;
  dimensions, lazy loading and asset-size verification must be part of the gate.

## Next

Implement package A first, then place one real image in each of the four roles:
hero illustration, feature icon, showcase screenshot and promo illustration.
Only after that visual language is accepted should package B add the two new
components.
