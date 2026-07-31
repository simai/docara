# Docara component system visual prototype

Status: visual review ready, review batch 4 applied

## Goal

Provide one disposable, browser-openable page for reviewing the proposed Docara authoring vocabulary before changing the compiler, schemas, registry, or generated documentation.

## Boundaries

- The prototype uses the current immutable SIMAI Framework Core/Smart pair locked by Docara.
- Existing Framework classes are used for buttons, badges, alerts, icons and layout utilities.
- Classes prefixed with `docara-prototype-` describe proposed product-owned `docara.*` components only.
- Public authoring groups are Markdown and Docara. `ui.*` and Framework implementation details are intentionally not exposed as public content components.
- This file is not a runtime implementation and is not a release claim.

## Decisions represented

- `button` replaces a separate public `cta` primitive; call-to-action is a recipe.
- `hero` and `banner` remain distinct: hero introduces a page, banner communicates a bounded message.
- Width belongs to `grid`, not to component names such as `card-1/4`.
- `badge` and `kbd` are inline primitives.
- `html` is an advanced escape hatch and must be governed by an explicit trust policy.
- Documentation components include alert, details, tabs, steps, tree, figure, diagram and download.
- Mathematical notation is native TeX-style authoring rendered by a pinned,
  self-hosted KaTeX dependency; it is not a separate arbitrary HTML component.
- Landing components include hero, banner, features, logos and media.
- Inline components use `:name[text]{parameters}`.
- Block components use `:::name {parameters}` with a matching closing fence.
- Containers use the same block syntax; an outer nested fence is longer than
  every child fence.

The accepted target grammar and current implementation gap are recorded in
`docs/authoring-syntax-contract.md`.

## Review batch 2: component decisions

The 2026-07-26 visual review produced the following source-backed decisions.

| Area | Decision | Ownership |
| --- | --- | --- |
| Alert | Use `sf-alert--default` for every semantic type so icon, background and border are always present. The earlier mixed `flat` and `outlined` examples were incorrect composition. Framework source additionally contains an actual defect: success sets `--sf-icon--color: transparent`; it must use `--sf-success`. | Existing Framework contract plus one source correction; Docara usage fix. |
| FAQ | Default documentation view is quiet `lines`; optional `surface` view groups short answers. Do not reuse the current high-emphasis accordion appearance for long documentation. | Candidate view/preset; validate for Framework admission before product duplication. |
| Steps | Keep the current horizontal `sf-steps` for compact processes. Add a content-rich vertical `timeline` view with `wait`, `process`, `finish` and `error` states. | Framework Smart-component candidate. |
| Tree | Use existing `sf-tree`: chevron only for expandable branches, `folder`/`folder_open` for directories and a type icon for leaves. | Existing Framework Smart contract; Docara view/template. |
| Figure | Expose a validated subset of existing aspect utilities: `auto`, `1x1`, `4x3`, `3x2`, `16x9`, `9x16`; support `fit=cover|contain`. | Existing Framework utilities; Docara schema and renderer. |
| Diagram | Author Mermaid source and render accessible SVG during the build or with a pinned portable renderer. Arbitrary executable diagram HTML is not accepted. | Docara Markdown extension plus pinned renderer. |
| Mathematics | Accept TeX notation `\\(...\\)` inline and `\\[...\\]` for blocks; render with pinned self-hosted KaTeX. | Docara Markdown extension plus pinned renderer. |
| Headings | Use native `h1`-`h6` and current Framework typography. Do not add local line-height overrides. Current source uses `--sf-text-size-*` with `--sf-title-height-*`; the supplied `--sf-heading-N--*` table is an older contract. | Existing Framework contract. |
| Card | Keep one `card` entity. Use `variant=plain` and `padding=none` when border/background/internal spacing are not required; grid owns width and gaps. | Docara component options composed from Framework utilities. |

## Framework admission gaps

Only two items currently justify Framework-level design work:

1. A quiet FAQ/accordion view suitable for documentation, if it is useful to
   more than Docara.
2. A vertical content-rich `timeline` view for the existing `sf-steps` Smart
   component.

They must be implemented in the Framework source owner, built through the
normal immutable Core/Smart pipeline and accepted independently. Docara should
not create competing system components while those admission decisions are
open.

The existing `sf-tree`, typography and aspect-ratio utilities need no new
framework implementation for this batch. `sf-alert` needs the bounded success
icon color correction above; Docara must not patch it locally.

## Static icon runtime note

The prototype is opened directly as a static HTML file and therefore does not
run the normal SIMAI Framework icon subset runtime. Core intentionally hides
`.sf-icon` until that runtime marks the resolved glyph with
`.sf-icon-loaded`. All static prototype icons now carry the marker explicitly;
the real Docara runtime must continue to let the Framework loader manage it.
This is not a reason to add a Docara CSS override or replace Framework icons
with local SVG/Unicode symbols.

## Review batch 3: composition refinement

The 2026-07-27 visual review refined eight presentation contracts without
changing the accepted authoring grammar.

| Area | Decision | Ownership |
| --- | --- | --- |
| Badge | Use the accepted Framework contract directly: `main`, `tonal` and `outline`; validated semantic schemes; sizes `1/3`, `1/2` and `1`. The official high-contrast surface variant is `main + on-surface`; it is shown as the neutral example inside `Main`, not as a separate type. Do not create an inverse override. `tonal + on-surface` normalizes to `main + on-surface`. | Existing Framework Core and Smart contract; Docara authoring projection. |
| Kbd | Render key caps inline with the `1/3` text scale, compact `c0` height and normal one-pixel outline. Align them to the middle of the text line; do not imitate raised keyboard keys with a thick bottom border. Documentation examples must demonstrate keys inside real prose, not as oversized standalone controls. | Docara component composed from Framework tokens. |
| Steps | Timeline titles use bold body typography instead of heading typography. Step state and hierarchy provide the emphasis; oversized headings are unnecessary. | Vertical timeline candidate. |
| Tree | Every row reserves separate chevron and type-icon columns. Each nested list starts at the parent type-icon column, producing a strict, predictable hierarchy. | Existing tree contract; Docara view refinement. |
| Button | Use only valid Framework combinations. `tonal + primary` is unsupported; the neutral example is `tonal + on-surface`, which remains readable in both themes. | Existing Framework button contract. |
| Grid + Card | A plain card may compose a `figure` with `ratio=16x9` above its text. This needs no additional card type. | Existing Docara composition model. |
| Features | Feature labels use bold body typography and `space-1` between icon, label and copy. They are content labels, not document headings. | Docara landing composition. |
| Media | Media composes a primary semantic icon, a `figure` with validated aspect ratio, copy and action. Image geometry remains owned by `figure`. | Docara landing composition. |

### Plain card comparison

The plain-card demonstrator now separates two comparable rows: three cards
without media and three cards with a composed `figure` at `16x9`. Media cards
use a small `h5` and exactly `space-1` after the figure. The spacing belongs to
the media-card recipe; `figure` remains reusable and does not impose spacing on
every surrounding context.

## Review batch 4: accepted Badge contract

The Badge section no longer contains the prototype-only inverse candidate or a
separate `Main · On Surface` group. It demonstrates the accepted Framework
types `main`, `tonal` and `outline`; `main + on-surface` replaces the unclear
grey neutral badge inside the `Main` group. The three public sizes remain in a
shared row. The examples use only public `sf-badge` classes and remain readable
in both light and dark themes. The displayed authoring form separates `type`,
`scheme` and `size`, matching the accepted Smart-component contract.

Correction verification on 2026-07-27:

- HTML parser and `git diff --check`: PASS;
- obsolete grey `main + neutral`, separate `Main · On Surface` group and its
  duplicate icon example: absent;
- browser review: PASS in light and dark themes at desktop width and at
  `390px`; no horizontal overflow in the Badge article;
- the only browser console message is the prototype server's missing
  `favicon.ico`, unrelated to the component contract.

## Review batch 5: Icon composition and Features retirement

The component system now has one universal inline `icon` entity instead of a
separate landing-only `features` component.

| Area | Decision | Ownership |
| --- | --- | --- |
| Icon grammar | Use `:icon[name]{size=1/2 container=none variant=plain scheme=primary}`. The same entity may appear inside prose or as a child of another block. | Docara inline component projected onto Framework icon classes and semantic tokens. |
| Sizes | Public examples cover `1/3`, `1/2`, `1` and `2`, using the existing `sf-icon--size-*` contract. | Existing Framework icon utilities. |
| Containers | `none` is the default for prose; `square` and `circle` add an optional visual container. | Docara composition using Framework radius and spacing tokens. |
| Variants | `plain`, `tonal`, `main` and `outline` are independent from semantic schemes. Examples use `primary`, `secondary`, `info`, `success`, `warning` and `error`. | Framework semantic color tokens; no raw Docara colors. |
| Accessibility | Decorative icons use `aria-hidden=true`. An icon that carries meaning without adjacent text requires an accessible label. | Mandatory renderer rule. |
| Features | Remove `:::features`. A feature row is a documented recipe composed from `grid`, `card` and `icon`; grid still owns width and responsive column count. | No new parser or Framework component. |

The demonstrator now shows icons embedded in real sentences, the full accepted
size scale, square and circular containers, four visual variants and a
four-card feature recipe. This keeps the authoring vocabulary small while
allowing the same visual result to be assembled from reusable parts.

## Review batch 6: Button is inline

`button` is an inline component and uses the compact authoring form
`:button[Label]{variant=main scheme=primary}`. It does not create a section,
choose its own width or own surrounding layout. A button may be the only item
on a source line while remaining an inline entity in the parser and renderer.

Groups of actions are block compositions owned by a parent container. The
container controls direction, wrapping, alignment and spacing; it does not
require a second CTA or button component. The demonstrator therefore places
Button in the inline section and removes the obsolete `:::button` example from
the layout section.

## Review batch 7: Icon family, weight and fill

The Icon contract now exposes the complete existing SIMAI Framework axis set
without creating a second icon implementation:

| Axis | Public Docara value | Framework projection |
| --- | --- | --- |
| Family | `outlined` (default), `rounded`, `sharp` | default `.sf-icon`, `.sf-icon-rounded`, `.sf-icon-shape` |
| Weight | `300` (`light`), `400` (`regular`, default), `500` (`medium`) | `.sf-icon-light`, `.sf-icon-regular`, `.sf-icon-medium` |
| Fill | `filled=false` (default), `filled=true` | outline glyph, `.sf-icon-filled` |
| Container | `none` (default), `square`, `circle` | Docara composition from Framework spacing and radius tokens |

`container` replaces the earlier authoring parameter `shape`. This prevents a
name collision with the Framework class `.sf-icon-shape`, whose actual meaning
is the Material Symbols **Sharp** family. Docara authors see the correct term
`family=sharp`; the renderer performs the compatibility mapping.

The Framework has equivalent low-level fill aliases `solid` and `filled`.
Docara exposes only the Smart-component-compatible boolean `filled` so authors
do not have to choose between duplicate spellings. The normal call remains
`:icon[search]`; family, weight and fill are progressive optional controls.

The Framework retains the complete technical weight scale from 100 through
700. Docara intentionally exposes only `light`, `regular` and `medium`: these
three produce useful documentation UI choices without turning a content
component into a font laboratory. Filled is not a heavier weight; it remains an
independent boolean axis.

The static prototype loads the three Material Symbols families only for visual
review. The earlier preview incorrectly declared one local Outlined font file
as the whole variable range, so computed weight classes changed while the
visible glyphs remained nearly identical. The preview now loads real requested
weights and demonstrates the curated Docara subset.
Production Docara must continue to use the Framework icon subset runtime and
must not ship those preview font declarations as product CSS.

## Review batch 8: semantic inline elements, neutral tree and media authoring

The review removes three avoidable ambiguities from the public authoring
contract.

| Area | Decision | Ownership |
| --- | --- | --- |
| Icon weight | The curated public set is `light` (`300`), `regular` (`400`) and `medium` (`500`). `thin` is valid at Framework level but is too fragile for ordinary documentation UI and is not exposed by Docara. | Existing Framework weights; narrower Docara projection. |
| Kbd and code | `kbd` remains a semantic inline element only for keys and user input. Standard Markdown backticks render inline `code` for commands, paths, parameter names and machine values. Badge remains a status/label primitive. Visual similarity does not justify merging their semantics. | Native HTML/Markdown semantics composed from Framework tokens. |
| Tree | A documentation tree represents structure, not menu selection. Static rows have no hover or selected background. Only expandable branches are interactive and retain a visible keyboard focus. Chevron, type icon and label use separate columns with a Framework spacing token. | Existing tree behavior with a quiet Docara documentation view. |
| Image | Standard Markdown image syntax is the default. Docara accepts a validated attribute extension such as `{ratio=16x9 fit=cover}`. An image embedded in prose remains inline; an image that is the only item in its paragraph is rendered as block media. | Markdown extension and context-aware Docara renderer. |
| Figure | `figure` is not a second image component. It is a block semantic container for an image with caption, source/credit or another self-contained media composition. It reuses the same validated image attributes and Framework aspect utilities. | Native figure semantics and Docara block renderer. |

This keeps the authoring vocabulary small: ordinary images stay ordinary
Markdown, inline code stays ordinary Markdown, and the custom components exist
only where HTML semantics or structured composition actually require them.

## Review batch 9: mirrored Media composition

`media` remains one component with one content model. The `side` parameter only
changes desktop composition: `left` places media before copy and `right` mirrors
the same data without introducing a second component or duplicated authoring
contract.

Both variants normalize to the same order on narrow screens: media first, copy
second. This preserves a predictable reading and focus order while desktop can
choose the composition that best matches the surrounding page.

The demonstrator shows the two variants side by side in sequence using the same
image, text and action so the effect of `side` can be reviewed independently
from content differences.

## Review batch 10: explicit inline code, quotes and coverage review

The inline section now separates `kbd` and inline `code`. Each has its own
authoring example, rendered result and semantic boundary. Inline code remains
native Markdown written with one pair of backticks; Docara does not introduce a
directive for it.

Quotes follow the same progressive rule. A plain quote uses native Markdown
`>` syntax. Optional validated `author`, `source` and `url` attributes add a
semantic citation without creating a second visual quote component. The
prototype shows source and result for both forms.

The Retype component index was reviewed as a reference inventory, not copied as
the Docara public API. Its 30 entries separate into these decisions:

| Decision | Capabilities |
| --- | --- |
| Already represented in the prototype | badge, button, callout/alert, card, columns/grid, file download, headings, icon, image/figure, math, Mermaid diagram, steps, tabs and layout containers |
| Native Markdown or source behavior; no custom visual component | comments, emoji, lists, ordinary links, ordinary tables and ordinary quotes |
| One shared Docara composition instead of provider-specific components | embed, YouTube, maps and social embeds use one allowlisted `embed` contract; octicons use the common icon contract |
| Important gaps to demonstrate before implementation | full code block, external code snippet/include, responsive table, reference link/footnote, page metadata (`last updated`) and backlinks |
| Optional or domain-specific | color chip and provider-specific social examples |

The next prototype batch should cover the important gaps in that order. It
should not add separate components for every external provider or for native
Markdown syntax.

## Review batch 11: complete documentation scenarios

The demonstrator now closes the important gaps identified in batch 10:

- one `Code` presentation accepts either a fenced Markdown body or a validated
  project-local `src`; these are two input modes, not two visual components;
- ordinary Markdown tables receive a responsive, keyboard-focusable overflow
  wrapper without a new authoring directive;
- reference links and footnotes remain standard Markdown and are shown with a
  rendered source list;
- author, updated date and content version are shown as one page metadata row;
  the proposed `publishing` object is explicitly marked as a future extension
  because the current `docara.page.v1` schema does not admit it yet;
- backlinks are generated from the internal link graph at build time and may
  either be placed explicitly or supplied by the `docs` preset;
- video, maps and external widgets share one allowlisted `Embed` contract with
  sandbox, title, aspect ratio, fallback text and consent-aware loading.

This completes the core visual scenario inventory needed for general product
and technical documentation. Provider-specific social blocks, color chips and
other domain recipes remain optional compositions rather than public Docara
primitives. Prototype syntax is not a claim that the current engine already
implements the corresponding parser, schema or build-time graph behavior.

## Review artifact

Open `source/workflow/prototypes/docara-component-system-preview.html` directly in a browser. Review both themes and resize to mobile width before approving implementation.
