# Workflow: Docara product UI restoration

Date: 2026-07-18
Status: in progress
Process model: `general_delivery`
Current state: `repository_prepared`
Target state: `ready_to_code`
Owner: `docara`
Companions: `sf5`, `ux`, `designer`, `docs`, `dev`, `tester`, `ops`
Memory decision: `inject`
Memory reason: prior Docara menu-repair evidence informed the audit and all
mutable repository/runtime facts were reverified from current sources.
Memory context pack:
`evidence/2026-07-18-docara-product-ui-restoration/personal-memory-context.md`

## Track

Track ID: `docara-product-ui-restoration`

Restore the complete Docara product shell on the accepted portable engine
before public product release, without reviving a second frontend runtime or a
second content language.

## Final Outcome

From an empty directory, a user can configure a branded, responsive Docara
documentation site or landing page through one inherited JSON shell contract
and Markdown content. Documentation supports at least four navigation levels,
search, left navigation, right TOC, breadcrumbs, previous/next, theme/reading
settings and a complete component catalogue. Every reusable interface element
uses an exact pinned Simai Framework contract, and the portable production
build remains PHP-only.

## Current Goal

Preserve `4a312c1…` as the accepted technical baseline and prepare the first
product-correction vertical slice: tree-shaped navigation with a real
four-level fixture, branding/assets, and an accessible responsive docs shell.
Do not resume public release/default-branch integration until that corrected
surface has exact UX, design, browser and tester acceptance.

## Done When

- the resolved navigation contract preserves the folder hierarchy and active
  trail without a fixed internal depth cap;
- a four-level fixture renders usable direct links, disclosure controls and
  responsive navigation from pinned Framework assets;
- logo, dark logo and favicon configuration is validated, copied and rendered;
- desktop and mobile shells have keyboard/focus and no-overflow evidence;
- every new schema field has implementation, negative tests, migration notes
  and public documentation;
- the current candidate is still described as a technical demonstrator until
  the complete product correction has independent acceptance.

## Stages

1. Prepare the exact clean baseline, launch record, component decision and
   first-slice tests.
2. Implement and accept the documentation shell vertical slice.
3. Add search, TOC, reading settings and remaining product navigation.
4. Build the landing/content component system and complete product docs.
5. Freeze an exact corrected candidate and run independent acceptance before
   release integration resumes.

## Batches

1. Batch 0: exact baseline, workflow/launch, component/accessibility decision
   and fixture design.
2. Batch 1: tree-shaped navigation contract and four-level renderer.
3. Batch 2: branding assets and responsive docs shell.
4. Batch 3: search, right TOC, breadcrumbs, previous/next and reading settings.
5. Batch 4: landing blocks, content catalogue and full documentation.
6. Batch 5: deterministic/browser/UX/design/tester acceptance of the corrected
   product candidate.

## Why This Workflow Exists

The accepted portable Docara candidate proves a safe JSON/Markdown contract,
an immutable Simai Framework lock, deterministic PHP-only builds and a working
local publication. It does **not** yet prove that the generated site is a
complete documentation product.

Fresh product review of `https://docara.test/`, the old generated site at
`https://doc.simai.io/en/`, the canonical repository and the released Framework
assets found a large product-shell regression:

- the page tree is flattened to one level;
- the header exposes only a text title and theme button;
- logo, search, settings, locale switch, breadcrumbs, previous/next links and
  the right table of contents are not configurable;
- the mobile layout places the whole navigation before the article instead of
  using an off-canvas navigation surface;
- the landing preset is only a centered article and the published docs contain
  no landing demonstration;
- the public guide describes only the small subset that exists, so the missing
  documentation is mostly a missing product surface rather than a writing gap.

The current local site must therefore be positioned as a **technical
demonstrator**, not as the product UI baseline.

## Product Verdict

`NEEDS_REVISION` before public product release or a claim that the new Docara
replaces the old documentation experience.

This verdict does not revoke the existing deterministic-build, security,
Framework-lock or local-runtime evidence. It supersedes only the earlier
product-interface and Human-Centered Simplicity interpretation. Removing
unimplemented settings made the v1 schema honest, but it also made the product
too narrow for the core tasks of finding, reading and navigating documentation.

## Direct Answers

### Four-level navigation

The Framework already contains reusable foundations:

- released Core v5.3.2 contains `.sf-menu` / `.sf-menu-item` and an explicit
  four-level example; visual levels deeper than four are normalized to level
  four;
- immutable Smart v5.3.1 contains recursive `<sf-tree>` and
  `<sf-tree-item>` with no coded depth limit and with `open`, `selected`,
  `active` and `disabled` states;
- immutable Smart v5.3.1 also contains `<sf-admin-menu>`, but its administrative
  shell, embedded logo, search and collapse behavior make it the wrong default
  for public documentation;
- the old Docara Blade menu is recursive and has no hard depth limit.

The current one-level menu is caused by
`PortableSiteBuilder::navigation()`, which reduces every page to one flat
`title/url` list, followed by one loop in `PortableHtmlRenderer`. It is not a
Framework limitation.

The product implementation should preserve an unrestricted semantic tree and
prove at least four visible levels. Existing `sf-menu` and `sf-tree` are
candidate building blocks, but the chosen navigation mode still needs a
documentation-specific accessibility acceptance: link semantics, active
trail, disclosure controls, keyboard flow, focus restoration and mobile drawer.

### Logo, search, settings and right navigation

These features cannot currently be enabled through portable JSON because the
schema has only:

- `layout.max_width`;
- `settings.theme`;
- page-level `navigation.hidden` and `navigation.order`.

The old repository still contains working product concepts for all required
regions: header logo/search/tools/language, left recursive menu, right heading
navigation, breadcrumbs, previous/next and responsive side menu. They should
be ported as behavior and information architecture, not restored as an old
Mix/Blade runtime wholesale.

## Product Principles

1. Keep one canonical portable format shared with Larena Docara.
2. Keep Markdown as the content language and JSON as the inherited shell and
   presentation configuration; do not create a second JSON content language.
3. Preserve the accepted precedence `docara.json -> _section.json -> page
   sidecar` and keep provenance explainable.
4. Generate the navigation tree from the content folder tree by default;
   section/page metadata only names, orders, hides, expands or overrides it.
5. Render every reusable visual primitive through pinned Simai Framework
   utilities, components or Smart-components. Docara owns documentation data
   adapters and layout recipes, not duplicate generic UI controls.
6. Keep the portable production build PHP-only. Development tooling may build
   and release Framework assets, but a Docara user must not need Node.js to
   build a site from a published package.
7. Simplicity means progressive disclosure and strong defaults, not removal of
   search, navigation or reading context.

## Target Layout Model

Docara needs two first-class presets with the same renderer and inheritance
model.

### `docs`

- sticky header: brand/logo, optional top links, search, locale/version and a
  compact settings action;
- left rail: hierarchical navigation tree, active trail and optional section
  tools;
- main: breadcrumbs, page metadata, Markdown/Smart-component content and
  previous/next navigation;
- right rail: heading table of contents with active-section tracking;
- footer: optional project links and copyright;
- responsive mode: header menu/search controls, off-canvas navigation, optional
  TOC drawer or inline collapsed TOC, with content first in the reading flow.

### `landing`

- configurable brand/header and footer;
- full-width semantic sections authored through typed Markdown directives;
- hero, feature grid, cards, call to action, media, code/demo and FAQ/tabs
  blocks assembled from Framework components and utilities;
- no forced documentation sidebars;
- the same theme, responsive and accessibility contract as `docs`.

Presets are named recipes, not separate engines. A future `article` or
`minimal` recipe may be added only after real demand.

## Proposed Portable Configuration Surface

The exact v2 schema must be designed test-first, but the public concepts should
be no broader than the following:

```json
{
  "schema": "docara.site.v2",
  "preset": "docs",
  "branding": {
    "title": "Product",
    "label": "Docs",
    "logo": "assets/logo.svg",
    "logo_dark": "assets/logo-dark.svg",
    "favicon": "assets/favicon.svg"
  },
  "layout": {
    "max_width": "wide",
    "header": true,
    "left_navigation": true,
    "right_toc": true,
    "footer": true
  },
  "navigation": {
    "mode": "tree",
    "expand": "active_path"
  },
  "search": {
    "enabled": true,
    "mode": "local",
    "hotkey": "/"
  },
  "toc": {
    "enabled": true,
    "depth": "2-3"
  },
  "settings": {
    "theme": "system",
    "reading_width": true,
    "text_size": true
  }
}
```

This is a product model, not an accepted schema. Every field must be rejected
until it has a renderer, documentation, positive/negative tests and browser
evidence. Section metadata should cover `title`, `order`, `hidden`, `expanded`,
`icon`, a possible section index page and inherited layout overrides. Page
metadata should cover only page-specific navigation, TOC, layout and discovery
values.

## Component Ownership And Backlog

### Accepted navigation decision for the first vertical

The first product vertical uses the released Core `.sf-menu` contract from
the already pinned Simai Framework v5.3.2 commit
`7e836d8a9414d5da553fb1ab0404721e5b48769a`. The semantic Docara tree has no
internal depth cap; the product fixture and browser acceptance prove four
visible levels, matching the released Framework example and visual depth
contract.

Docara renders native `nav -> ul -> li -> a` semantics, a separate disclosure
button, `aria-current="page"` and an open active trail. A bounded shell adapter
keeps native link activation from being consumed by the Core disclosure
handler and synchronizes disclosure accessibility state. This is a data/layout
adapter for the accepted Framework component, not a second menu component.

Mobile navigation uses a native `details` disclosure with the same recursive
Framework menu. It restores focus and closes on Escape. The unreleased Smart
`sf-drawer`, Smart `sf-tree`, moving references and administrative
`sf-admin-menu` are explicitly out of this vertical. The existing Framework
lock and bounded Smart projection therefore remain unchanged.

### Already available in pinned Framework releases

| Need | Existing foundation | Docara action |
| --- | --- | --- |
| Four-level menu | Core `.sf-menu` | render the real hierarchy and accept accessibility/responsive behavior |
| Recursive tree | Smart `sf-tree` / `sf-tree-item` | add exact manifests/projection; evaluate navigation mode rather than file-tree styling |
| Breadcrumbs | Smart `sf-breadcrumbs` | project the resolved ancestor trail |
| Theme | Core `.sf-theme-button` | replace Docara's private inline controller |
| Search controls | Smart input/list/modal/dropdown primitives | Docara generates index and result data |
| Alerts, buttons, steps, downloads, references | existing Smart surfaces | add typed Markdown calls only after exact manifest acceptance |

### Framework gaps to develop deliberately

| Component | Generic responsibility | Docara responsibility |
| --- | --- | --- |
| `sf-anchor-nav` / `sf-scrollspy-nav` | accessible anchor list, current-section state, scroll tracking | extract headings and pass stable anchors |
| `sf-search` | query UI, keyboard command, results and empty/error states | generate the static index and page URLs |
| `sf-drawer` | responsive off-canvas surface, focus trap/restore, escape and overlay | provide navigation or TOC content |
| optional `sf-locale-switch` | accessible locale/version selection | resolve available locales and matching URLs |

`sf-drawer` currently exists only in an unreleased Smart worktree and must not
be consumed until a coordinated pinned release. Search and TOC must not be
implemented as Docara-only lookalikes if they are intended for other products.

### Docara-owned layout blocks and adapters

- header and brand/logo block;
- documentation shell and landing shell recipes;
- page-tree builder and active trail;
- Markdown heading extractor and TOC/search index adapters;
- breadcrumbs/previous-next data resolution;
- page/section inherited configuration;
- SEO/meta/favicon/social-image projection;
- landing section recipes built from Framework primitives.

## Content Component Catalogue

The official Retype catalogue is a useful coverage benchmark, not a syntax to
copy. Docara should document and demonstrate the following groups:

1. Text: headings/anchors, badge, icon/emoji and inline reference.
2. Layout: columns, container, panel, tabs and cards.
3. Code/data: code block, external snippet, copy action, Mermaid, math, color
   chip and responsive table.
4. Lists/procedure: normal lists, task/check lists and steps.
5. Media: image/figure, file download, video and controlled embed.
6. Interactive: button, callout/alert, reference link, last-updated and
   previous/next.
7. Landing recipes: hero, feature grid, CTA, logo/media strip, demo/code pair
   and FAQ.

Raw arbitrary HTML and arbitrary remote embeds must not become the default
escape hatch. Typed directives, allowlisted attributes and safe fallbacks keep
the portable format predictable for people and AI.

Every catalogue entry must show:

- purpose and when to use it;
- minimal Markdown invocation;
- supported parameters and defaults;
- live rendered example;
- responsive, theme and accessibility behavior;
- exact Framework owner/version and readiness;
- honest blocked or fallback state when no contract exists.

Reference coverage:

- Retype components: <https://retype.com/components/>
- Retype project configuration: <https://retype.com/configuration/project/>
- Retype page configuration: <https://retype.com/configuration/page/>
- Docusaurus hierarchical/autogenerated sidebars:
  <https://docusaurus.io/docs/sidebar>
- Mintlify nested navigation groups:
  <https://www.mintlify.com/docs/organize/navigation>

## Documentation Required For Product Acceptance

- quick start from an empty directory;
- one complete `docara.json` reference with defaults and inheritance;
- branding and assets: logo, dark logo, favicon and social image;
- folder/section/page navigation and a real four-level example;
- layouts and responsive behavior for `docs` and `landing`;
- search, TOC, breadcrumbs, previous/next, locale and reading settings;
- component catalogue with live examples;
- a complete documentation demo site and a separate landing demo route;
- migration mapping from legacy `.settings.php`/layout configuration;
- troubleshooting for asset locks, missing manifests and build validation.

## Delivery Order

### P0: product documentation shell

1. Replace the flat page list with a stable semantic tree and prove four
   levels.
2. Introduce typed branding and real asset copying.
3. Add left navigation, responsive mobile navigation, breadcrumbs,
   previous/next and right TOC.
4. Add a static search index and accessible search UI.
5. Replace private theme code with the pinned Framework contract and restore
   reading settings through progressive disclosure.
6. Publish a complete configuration/layout guide and four-level fixture.

### P1: content and landing system

1. Add the missing high-value typed Markdown components in the catalogue.
2. Build a real responsive landing page with hero, features, code/demo, CTA
   and footer.
3. Add a universal live demonstrator for every accepted component and layout
   recipe.

### P2: advanced documentation product

- locale/version switching;
- edit/report/copy/print actions;
- last-updated, related/backlink and feedback surfaces;
- multi-product navigation and optional API/OpenAPI presentation;
- additional layouts only when supported by real use cases.

## Acceptance Gates

- exact pinned Framework Core/Smart revisions; no `main` or `latest`;
- no custom Docara duplicate when a coherent accepted Framework component
  exists;
- no schema field without rendering, docs and tests;
- four-level navigation fixture with active trail, collapsed branches and
  direct links at every level;
- desktop, tablet and mobile browser acceptance in light/dark/system themes;
- keyboard-only navigation, visible focus, reduced motion, escape/focus return
  for drawers/dialogs and no inaccessible ARIA tree claims;
- search result relevance, empty state and no-JavaScript content access;
- right TOC anchor accuracy and current-section behavior;
- landing and documentation layouts without horizontal overflow;
- deterministic clean build, local link/asset checks and clean console;
- independent UX/design review and tester verdict bound to an exact candidate;
- no release/product-readiness claim from technical build tests alone.

## Evidence Plan

Store exact revisions, component versions, commands, assertions, screenshots,
browser/console results, UX decisions and limitations under:

`source/workflow/evidence/2026-07-18-docara-product-ui-restoration/`

Generated sites, browser profiles and dependency trees stay outside Git.
Every implementation batch records the changed paths, verification commands,
semantic outcome, unresolved Framework gaps and next safe batch. Final tester
and UX/design verdicts must bind to one exact candidate revision.

## Batch 0 Evidence

- clean implementation branch: `codex/docara-consolidation` at
  `adc67ec8f46812e0460f75b86d095a97c9f1eb66`, with accepted technical
  ancestor `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`;
- repository action-gate preflight: PASS, low risk, no blockers;
- federation process validation: PASS; Human-Centered Simplicity remains
  pending until implementation evidence and verdict exist;
- baseline PHPUnit suite: PASS, 424 tests and 1919 assertions;
- Framework inventory: released Core `.sf-menu` selected; current flat menu is
  confirmed to be a portable Docara renderer limitation, not a Framework
  limitation;
- release, default-branch integration, Framework-owner writes and public
  publication remain outside the active batch.

## Human-Centered Simplicity

The product correction keeps `human_centered_simplicity` as a quality control.
The default UI should expose the primary reading and discovery actions while
putting optional branding, layout and advanced controls behind progressive
configuration. Search, hierarchical navigation and reading context are core
task support, not removable visual clutter.

## Kaizen

The earlier correction correctly removed no-op settings but treated contract
minimality as product simplicity. The reusable lesson is to audit simplicity
against the complete user job before deleting protective product complexity.
No canonical skill or graph change is proposed from this planning batch.

## Evidence Reviewed

- current flat renderer:
  `src/PortableSite/PortableHtmlRenderer.php:51-123`;
- current flattening:
  `src/PortableSite/PortableSiteBuilder.php:265-301`;
- current narrow presentation schema:
  `resources/schemas/presentation.schema.json:1-48`;
- old recursive menu:
  `stubs/site/source/_core/_components/_nav/menu.blade.php:1-60`;
- old product layout and branding:
  `stubs/site/config.php:7-115`;
- Core four-level menu example:
  `/Users/rim/Documents/GitHub/ui-play/examples/components/menu/default/index.html`;
- Core menu depth logic:
  `/Users/rim/Documents/GitHub/ui-loader/src/component/menu/js/_menu.js`;
- Smart tree:
  `/Users/rim/Documents/GitHub/ui-loader/src/smart/tree/index.js` and
  `/Users/rim/Documents/GitHub/ui-loader/src/smart/tree-item/index.js`;
- old search and right navigation adapters under
  `stubs/site/source/_core/_assets/js` and
  `stubs/site/source/_core/_components/_nav`;
- live checks: `https://docara.test/` -> 200,
  `https://docara.test/landing/` -> 404, old reference -> 200;
- browser comparison of desktop/mobile structure and console state.

## Next Safe Batch

Do not start the previously proposed public release/default-branch integration
from `4a312c1...` as the product baseline. Preserve that exact candidate as the
accepted technical engine and start a clean product-correction branch from it.

The first implementation batch is bounded to the `docs` shell vertical slice:

1. tree-shaped resolved navigation contract and four-level fixture;
2. pinned Framework menu/tree evaluation and one accepted renderer;
3. brand/logo/favicon configuration and asset projection;
4. desktop left rail plus accessible mobile navigation scaffold;
5. schema, migration and public docs for only those implemented fields;
6. exact tests and browser evidence before search/TOC work begins.

Release, mirror publication, consumer default-branch migration and
`docara-mix` retirement remain separate gated work after the corrected product
candidate passes acceptance.
