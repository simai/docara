# Docara Surface & Hero Media post-roadmap track

Date: 2026-08-06
Status: `surface_hero_track_ready_for_user_decision`
Current stage: `docara.stage.s3.shared_adoption`
Current batch: `docara.batch.s3.integrated_acceptance`
Current product candidate: `dd2c0d623f0757e172861fdac959b839a7fff495`
Next roadmap goal: `docara.track.surface-hero-media` (`complete`, authorized=`false`)
Current next action: `explicit_user_surface_hero_decision`
Track ID: `docara.track.surface-hero-media`
Repository: `/Users/rim/Documents/GitHub/docara-unified`
Branch: `codex/docara-unified-architecture`
Planning baseline HEAD: `4eeb3e0b6578239415bd7b20b84c2a20305195a6`
Previously accepted product/runtime candidate: `eb35f5c6f18e5eb9be69e91887b09486f5703136`
Executor thread: `019fbd13-8284-7453-97e8-3183819fbd34`

## 0. Authority and boundary

The user explicitly authorized this separate post-roadmap feature track after
the Goal 1-3/A-C product track reached `ready_for_user_release_decision`.
This is not Goal D/4 and does not reopen or weaken the independently accepted
Goals 1-3/A-C. Their exact candidates, external pins, contracts and evidence
remain the regression baseline.

This track authorizes only the Docara product, tests, schemas, bundled examples,
public documentation and governance changes required below. It does not
authorize:

- merge into a default branch;
- push, tag, package publication or release;
- deployment or replacement of `docara-new.test`, `docara.test` or any other
  local/test/live site;
- changes in Framework or other external owner repositories;
- arbitrary cleanup outside this track.

The installed Docara skill is stale and disabled. Repository specification,
graph, workflow, tests and handoff are the source of truth.

Implementation is sequential:

```text
Goal S1 Full-bleed and Surface runtime
  -> independent audit
Goal S2 Hero background media
  -> independent audit
Goal S3 Adoption, documentation and integrated acceptance
  -> independent audit
```

Do not start a later goal before the preceding goal has an independent
`PASS` or `PASS_WITH_NOTES` verdict.

Goal S1 is independently accepted with `PASS` on product candidate
`ac53ea4...` and audited governance HEAD `4feb910...`. Its independently
reproduced canonical 393-file ledger is `650a678c...`; the former
`90bf6378...` claim remains rejected. That verdict opens only Goal S2. Goal S3
and release/live actions remain unauthorized.

Goal S2 plus diagnostic-location correction S2-C1 is independently accepted on
exact product candidate `7eeba4a...` with `PASS_WITH_NOTES`; its package
reproduction uses exact tag input `v2.0.0-alpha1-s2c1`. Goal S3 is complete on
exact product `dd2c0d6...`: Surface, Hero, Showcase and Promo share one outer
presentation implementation with frozen default bytes, public docs and fresh
integrated evidence. The track is terminal pending an explicit user decision;
there is no S4/Goal D.

## 1. Problem and factual baseline

### 1.1 Current visible defect

The accepted renderer already emits a full-width request for Hero:

```html
<section data-docara-block="hero" data-docara-width="full">
  <div data-docara-container>...</div>
</section>
```

The current landing DOM at `https://docara-new.test/ru/` places this section
directly under the constrained article:

```text
main.docara-landing
  -> article.docara-content.container
    -> section[data-docara-block=hero][data-docara-width=full]
```

The current CSS applies the breakout only through an intermediate
`[data-docara-section][data-docara-region-owner="main"]` node which is absent
from that DOM. Therefore the request is not honored and the Hero background
stops at the article container. The served stylesheet is byte-identical to
`resources/portable/declarative-shell.css` at SHA-256
`c9744647bb7ac672c88a1918de663343e3dfa9527b60156aa2d6c930cc209548`.

### 1.2 Product need

Authors need two distinct but related capabilities:

1. a general content surface/band which may occupy the full admitted Layout
   Region and contain other admitted content;
2. a semantic Hero which can keep its current side-media presentation or use
   its existing Markdown image as a decorative background.

Creating independent full-width/background implementations for `surface` and
`hero` would duplicate geometry, security, responsive and accessibility
behavior. The target is one shared presentation primitive with two public
semantic entry points.

## 2. Required user outcome

After the track:

- `docara.surface` lets an author create a safe content band with constrained
  or full admitted width, tokenized background, overlay and spacing;
- a Surface can contain admitted Markdown and content components according to
  a machine-readable child contract;
- `docara.hero` keeps the current default result and adds a background-media
  mode without requiring an authored Surface wrapper;
- Hero, Surface, Showcase and Promo use one shared Surface presentation model,
  not separate breakout/background engines;
- full width means the full width allowed by the active Layout Region;
- on the landing Layout this reaches the page edges, while a documentation
  Layout cannot overlap its sidebar, outline, header or trusted page boundary;
- public examples explain when to use Surface, Hero side media and Hero
  background media;
- preview, full build and single-page build render through the existing single
  production pipeline and remain deterministic.

## 3. Product and architecture decisions

### 3.1 One owner per responsibility

| Responsibility | Owner |
| --- | --- |
| region and physical breakout boundary | `LayoutComposer` / active Layout |
| width request, content width, background layer, overlay and spacing | shared typed Surface presentation model |
| H1, description, actions and semantic media mode | `docara.hero` |
| admitted nested content and nesting diagnostics | `docara.surface` container contract |
| parsing and typed in-memory nodes | existing Markdown compiler and Document IR |
| final rendering/build | existing renderer registry and PageBuilder |

### 3.2 Public Surface versus Hero

- `docara.surface` is the general authored content container.
- `docara.hero` is a semantic specialization backed by the same internal
  Surface presentation model.
- Authors do not wrap Hero in Surface.
- In v1, `docara.hero`, `docara.showcase`, `docara.promo` and another
  `docara.surface` are not admitted children of `docara.surface`.
- Do not add an `inherit`, nested-surface or double-background mode in this
  track.

### 3.3 Shared primitive, not another renderer pipeline

Introduce one immutable typed internal value such as `SurfacePresentation`
(the exact class name may follow repository conventions) which contains
validated presentation data. Existing typed renderers may delegate HTML
construction to it, but this must not create:

- a second Markdown parser or directive dialect;
- a second typed-component catalog or renderer registry;
- a second Smart Gateway, LayoutComposer or PageBuilder;
- a component-ID/namespace branch in PageBuilder, LayoutComposer or the Smart
  runtime;
- a project-provided template, callback, class or style path.

### 3.4 Background media is a real media layer

Do not concatenate an arbitrary `style="background-image: ..."` string.
Render a validated decorative `<img>`/`<picture>` layer behind the overlay and
content. It must have `alt=""` and `aria-hidden="true"`; fit and position are
controlled by fixed data attributes/classes and bundled CSS.

A meaningful image remains ordinary content media with meaningful alternative
text. It cannot be silently converted into a decorative background.

### 3.5 Full-width semantics

`full` means the full boundary admitted by the current Layout Region, not
unconditional viewport escape.

- landing `main` Region: edge-to-edge page surface;
- documentation `main` Region: full main Region without covering navigation,
  outline or trusted shell;
- page `<head>` and outer document remain application-owned;
- use the established container-query breakout (`100cqw`) or an equally
  deterministic Layout-owned mechanism; do not use `100vw`, which may include
  scrollbar width and create horizontal overflow.

## 4. Public authoring contract

Fence length only protects nesting. The registry/Atlas `authoring_kind`
defines a container. Documentation may show `::::surface` when its body contains
shorter `:::` directives, while the canonical call remains `:::surface`.

### 4.1 General Surface

Proposed authoring:

```markdown
:::::surface {width=full content_width=container background_image="/assets/hero.webp" background_fit=cover background_x=right background_y=center overlay=dark overlay_strength=medium padding=xl tone=default}

## A full-width product band

Text remains aligned with the ordinary content grid.

::::grid {columns=3 gap=2}
:::card
First card
:::
:::card
Second card
:::
:::card
Third card
:::
::::

:::::
```

Required v1 props:

| Prop | Values | Default | Rule |
| --- | --- | --- | --- |
| `width` | `content`, `full` | `content` | outer Surface width request |
| `content_width` | `container`, `full` | `container` | width of the inner content layer |
| `background_image` | safe local public asset reference | absent | decorative only; no remote/protocol/data/traversal reference |
| `background_fit` | `cover`, `contain`, `auto` | `cover` | valid only with an image; no distortion/stretch mode |
| `background_x` | `left`, `center`, `right` | `center` | physical horizontal image position |
| `background_y` | `top`, `center`, `bottom` | `center` | physical vertical image position |
| `overlay` | `none`, `light`, `dark` | `none` | fixed design-token overlay |
| `overlay_strength` | `soft`, `medium`, `strong` | `medium` | valid only when overlay is not `none` |
| `padding` | `none`, `sm`, `md`, `lg`, `xl` | `md` | fixed spacing tokens only |
| `tone` | `default`, `muted`, `accent`, `contrast` | `default` | registered theme tokens only |

The Surface v1 container contract is:

- one declared `content` slot;
- `min_children=1`, `max_children=64`, `order=declared`,
  `max_depth=3`;
- `depth_semantics=relative_subtree_root_level_1`: every container is level 1
  of its own subtree; Surface -> Grid -> Card is 3/3 for Surface and 2/2 for
  Grid;
- native Markdown is admitted;
- inline components remain inside admitted Markdown;
- typed and Smart children require a registry-owned content-embeddable
  capability (exact durable capability name to be frozen in S1);
- shell contributions, page Layout/Section artifacts, Surface-owning semantic
  blocks and arbitrary raw HTML are rejected;
- another Surface is rejected in v1;
- unsupported child, invalid count/depth/order, mismatched/unclosed fence and
  invalid child props have stable error codes and source locations.

The capability must be derived from admitted registries/definitions. Do not
maintain a second hand-written public component list in the renderer.

### 4.2 Hero

The existing `variant=split|centered|compact` contract remains. Add:

| Prop | Values | Default | Rule |
| --- | --- | --- | --- |
| `media` | `auto`, `side`, `background`, `none` | `auto` | `auto` preserves exact prior behavior |
| `background_fit` | `cover`, `contain`, `auto` | `cover` | background mode only |
| `background_x` | `left`, `center`, `right` | `center` | background mode only |
| `background_y` | `top`, `center`, `bottom` | `center` | background mode only |
| `overlay` | `light`, `dark` | `dark` | background mode only; no unprotected text-on-image mode |
| `overlay_strength` | `soft`, `medium`, `strong` | `medium` | background mode only |

`media=auto` must preserve accepted output for existing authored Hero blocks.
`media=side` requires one image and is allowed only with the compatible split
presentation. `media=none` rejects an authored image. `media=background`
requires exactly one existing Markdown image with empty alt text because it is
decorative:

```markdown
:::hero {variant=split media=background background_fit=cover background_x=right background_y=center overlay=dark overlay_strength=medium}
# Documentation that is easy to create and read

Docara builds documentation and product pages from Markdown and validated JSON.

[Create the first site](/start/)

![](/assets/hero.webp)
:::
```

Hero must not gain a second `image=`/`background_image=` source. Its existing
Markdown image remains the single media source. A non-empty alt in background
mode fails closed and tells the author to use side media for meaningful imagery.

Background-only props in `auto`, `side` or `none` mode fail closed instead of
being ignored. Unknown props and invalid combinations retain stable diagnostic
codes and source locations.

## 5. Target runtime and DOM contract

The exact internal class names may follow repository conventions, but the
semantic structure is fixed:

```text
Surface presentation
  -> optional decorative media layer
  -> optional tokenized overlay
  -> inner content-width container
    -> component-owned semantic content
```

Representative DOM:

```html
<section data-docara-surface data-docara-width="full" data-docara-tone="default">
  <img data-docara-surface-background alt="" aria-hidden="true">
  <span data-docara-surface-overlay aria-hidden="true"></span>
  <div data-docara-container data-docara-content-width="container">
    <!-- already rendered admitted content -->
  </div>
</section>
```

The final DOM may omit absent layers and may retain existing block attributes
for backward compatibility. Default Hero/Showcase/Promo HTML must remain
byte-identical wherever the contract explicitly requires parity; the shared
helper is an implementation detail, not permission for unrelated markup churn.

All public Markdown still compiles into the existing typed in-memory Document
IR. Container parsing must use the existing directive/CommonMark machinery.
No intermediate mandatory page JSON and no new alternate preview representation
are allowed.

## 6. Security, accessibility and responsive requirements

### 6.1 Background asset admission

`background_image` and Hero Markdown media must pass the normal public asset
and URL policy plus Surface-specific local-only admission:

- reject `javascript:`, `data:`, protocol-relative and remote URLs;
- reject traversal, root escape, NUL/control characters, case collisions,
  symlink/hardlink escape and unsupported asset types;
- bind copied/generated assets into the normal deterministic build receipt;
- never discover or download media from the network during build or preview;
- fail closed on missing assets or hash/projection mismatch.

### 6.2 Accessibility

- background media is decorative and absent from the accessibility tree;
- meaningful images stay ordinary media with meaningful alt text;
- heading order, landmark semantics and link/button names remain valid;
- shipped examples meet text/background contrast in light and dark themes;
- overlay and tone are tokenized, not arbitrary colors;
- focus visibility and keyboard order are unchanged by the background layer;
- reduced-motion is respected; parallax and fixed-background effects are out of
  scope.

### 6.3 Responsive and directionality

- no horizontal overflow at 320, 390, 768, 1024 and 1440 CSS pixels;
- inner container alignment matches adjacent normal content;
- full Surface reaches exactly the active Region boundary;
- background fit and physical X/Y positioning are deterministic in LTR and RTL;
- mobile does not crop the text/actions or make them unreadable;
- the background layer cannot capture pointer or focus events;
- `100vw` breakout and negative values derived from arbitrary author input are
  forbidden.

Focal-point percentages, breakpoint-specific author props, repeating textures,
video backgrounds, parallax and arbitrary minimum heights are parked for a
future separately justified extension.

## 7. Compatibility and migration

1. Record an exact clean baseline and current public output before S1 changes.
2. Fix the current direct-child full-bleed selector/DOM mismatch first.
3. Add the shared Surface presentation model and public Surface additively.
4. Preserve existing Hero output when the new `media` prop is absent.
5. Add background Hero as a new explicit state; do not silently reinterpret
   existing meaningful images.
6. Move Showcase and Promo construction to the same helper only after byte
   parity and focused tests.
7. Remove duplicate old construction only after zero-reference and rollback
   evidence.
8. Do not change the current homepage from side media to background media in
   this track. Demonstrate background mode in the component catalog; changing
   homepage art direction is a later explicit content/design decision.

## 8. Sequential goal queue

### Goal S1 — Full-bleed Geometry & Shared Surface Runtime

#### Required outcome

Correct the current Layout-owned full-width behavior and introduce one safe
shared Surface presentation primitive plus the public `docara.surface`
container. Current Hero must visually reach the admitted landing Region edges
while its inner content stays aligned with the page container. No Hero
background mode is implemented in S1.

#### Required work

1. Freeze baseline HEAD, accepted product candidate, DOM/CSS mismatch and
   representative full/single output.
2. Synchronize this separately authorized track into current workflow/handoff
   and graph without rewriting historical Goal 1-3/A-C acceptance.
3. Define `docara.surface.v1`, props, schema, Atlas authoring/container contract
   and stable diagnostics.
4. Freeze a registry-owned content-embeddable capability and apply it only to
   genuinely safe authored children.
5. Implement the shared immutable Surface presentation model inside the one
   existing typed-renderer path.
6. Correct Layout/CSS full-bleed behavior for the real direct-child landing DOM
   and the registered Section-wrapped contour if both remain supported.
7. Render background media for public Surface through a safe decorative media
   layer and normal asset receipt.
8. Add focused examples/tests without changing homepage art direction.

#### S1 Done When

- real landing Hero outer surface is edge-to-edge within the landing main
  Region and has no horizontal overflow;
- inner Hero content aligns with adjacent `.container` content;
- documentation layouts do not overlap sidebar/outline/header;
- public `docara.surface` renders content/full widths and all admitted token
  combinations through one typed runtime;
- Surface Atlas entry is `authoring_kind=container` and exposes exact
  child/slot/count/order/depth/capability/provenance data;
- valid Markdown/grid/card/eligible Smart/project child examples render;
- nested Surface, Hero/Showcase/Promo child, shell child, unsupported child,
  invalid depth/count/fence/prop and unsafe background asset fail closed;
- default existing Hero HTML/content semantics remain unchanged; the only
  planned default visual difference is corrected outer width;
- full and representative single build, preview/production and two-build
  determinism pass;
- tracked worktree is clean and S1 evidence is bound to one exact candidate;
- state stops at `goal_s1_ready_for_independent_audit`; S2 is not started.

#### S1 required evidence

- baseline and exact diff map;
- schema/catalog/Atlas fingerprints;
- focused renderer/parser/container/asset/security tests;
- full PHPUnit and static verification;
- two clean full builds plus representative single-page equality;
- browser desktop/mobile, light/dark, LTR/RTL, overflow, inner alignment and
  documentation-layout boundary matrix;
- rollback and zero-reference report for any replaced construction;
- synchronized specification, graph, workflow and handoff.

### Goal S2 — Hero Background Media on the Shared Surface

#### Entry gate

Goal S1 independently accepted with `PASS` or `PASS_WITH_NOTES` on one exact
candidate.

#### Required outcome

Add explicit `media=auto|side|background|none` to the existing semantic Hero.
The background variant must consume the existing Markdown image and delegate
all surface geometry/background/overlay work to the accepted shared Surface
presentation model. Existing Hero authoring without `media` remains compatible.

#### S2 Done When

- `media=auto` reproduces the accepted S1 default Hero output;
- `media=side` renders meaningful ordinary media with the existing semantics;
- `media=background` renders exactly one empty-alt decorative image behind a
  tokenized overlay and readable Hero content;
- `media=none` rejects an authored image;
- missing image, meaningful-alt background, unsafe asset, incompatible
  variant/media, background props outside background mode and unknown props
  have stable fail-closed diagnostics;
- there is no authored Surface wrapper and no duplicate background renderer;
- component catalog/Atlas exposes the new states and parameters from the real
  admitted definition;
- default public pages remain unchanged except the already accepted S1 width
  correction; a background Hero exists in an isolated component demo;
- focused/full tests, preview/production, full/full/single determinism, static,
  security and fresh browser accessibility/responsive matrices pass;
- tracked worktree is clean and evidence is bound to one exact candidate;
- historical S2 execution stopped at its independent-audit gate before the
  accepted S3 transition recorded at the top of this completed track.

### Goal S3 — Shared Adoption, Public Documentation & Integrated Acceptance

#### Entry gate

Goal S2 independently accepted with `PASS` or `PASS_WITH_NOTES` on one exact
candidate.

#### Required outcome

Move existing Hero, Showcase and Promo outer presentation construction onto the
one shared Surface model without changing their default semantic/visual output,
complete the public Surface/Hero documentation and run the full integrated
acceptance matrix.

#### Required work

- prove default Hero/Showcase/Promo parity before retiring duplicated outer
  construction;
- add a public Surface component page and update containers/composition,
  Hero, catalog, Design Atlas and authoring documentation;
- document Surface versus Hero decision guidance and the prohibition on double
  wrapping/nesting;
- show copyable contained/full Surface, safe background Surface, Hero side
  media and Hero background media examples;
- include accessibility, responsive, failure and security examples;
- update settings/reference only if actual registered Surface settings exist;
  do not invent global config fields;
- synchronize specification, roadmap, acceptance, graph, generated context,
  workflow and handoff.

#### S3 Done When

- one shared Surface presentation implementation is used by Surface, Hero,
  Showcase and Promo;
- no duplicate full-bleed/background engine or indefinite compatibility branch
  remains;
- default Hero/Showcase/Promo parity is demonstrated, apart from the explicitly
  accepted S1 full-width correction;
- public catalog and Atlas truthfully show ownership, authoring kind,
  capabilities, states, props, children and provenance;
- documentation contains no unsupported arbitrary CSS/path/nesting claims;
- package/fresh consumer, focused/full tests, schemas, graph/context, static
  verification, preview/production, two full builds, representative single
  build, security negatives and rollback evidence pass;
- browser QA passes landing, documentation layout and isolated demos on
  desktop/mobile, light/dark, LTR/RTL, keyboard/focus, contrast, reduced motion,
  no overflow and clean console/network for local assets;
- tracked worktree is clean and all evidence identifies one exact final
  candidate;
- state is `surface_hero_track_ready_for_user_decision`.

## 9. Whole-track acceptance matrix

| Dimension | Required proof |
| --- | --- |
| User outcome | full-width band and background Hero are understandable and useful without manual wrapper composition |
| Geometry | outer Surface reaches exactly its admitted Layout Region; inner content remains aligned |
| Architecture | one parser/typed IR/renderer registry/Gateway/LayoutComposer/PageBuilder and one shared Surface presentation primitive |
| Semantics | Hero owns H1/description/actions/media meaning; Surface owns general band presentation |
| Containers | machine child/slot/count/order/depth/capability contract and stable diagnostics |
| Compatibility | old Hero/Showcase/Promo authoring and default output preserved except accepted width correction |
| Security | local-only asset admission; traversal/root/symlink/hardlink/case/protocol/data/remote negatives |
| Accessibility | decorative background hidden, semantic images keep alt, contrast/focus/heading/landmark checks |
| Responsive | desktop/mobile, LTR/RTL, no horizontal overflow, deterministic fit/position |
| Build | preview/production equality, full/full/single equality, package/fresh consumer determinism |
| Documentation | copyable Surface and all Hero media modes, decision guidance, failure examples, exact Atlas links |
| Simplicity | no double wrapper, second registry, second dialect, arbitrary style API or component-ID branch in shared pipeline |

## 10. Evidence and handoff

Use one evidence root per goal:

```text
source/workflow/evidence/2026-08-06-docara-surface-hero/
  S1-BASELINE-AND-SURFACE.md
  S1-INTEGRATED-ACCEPTANCE.md
  S2-HERO-BACKGROUND.md
  S2-INTEGRATED-ACCEPTANCE.md
  S3-DOCUMENTATION-AND-ADOPTION.md
  S3-INTEGRATED-ACCEPTANCE.md
  INDEX.md
```

Every evidence record names exact source HEAD/candidate, commands, environment,
results, tree/ledger hashes, browser target and limitations. A green command
without semantic output evidence is not acceptance. Evidence from a predecessor
candidate is historical and cannot be silently reused after product changes.

At each goal end, synchronize the canonical router and stop at
`ready_for_independent_audit`. The executor cannot self-accept its goal.

## 11. Independent automation protocol

The monitoring task checks the executor every 30 minutes.

1. Active/in-progress work is a quiet no-op.
2. An interrupted turn may receive one idempotent resume marker only.
3. A new completed turn is audited backward from the goal Done When against the
   exact repository state; the executor report is not evidence by itself.
4. Verdict is exactly one of `PASS`, `PASS_WITH_NOTES`, `PARTIAL`,
   `CORRECTION_REQUIRED` or `BLOCKED`.
5. A failed goal receives one bounded correction/retest assignment and no next
   goal.
6. Accepted S1 receives S2; accepted S2 receives S3.
7. Accepted S3 ends the automation. It does not invent Goal S4/D, deploy the
   test site or start release work.

All executor messages use one marker per completed turn:

```text
[DOCARA-SURFACE-AUTO-AUDIT:<completed-turn-id>]
```

Resume markers use:

```text
[DOCARA-SURFACE-AUTO-RESUME:<interrupted-turn-id>]
```

## 12. Track stop conditions and non-goals

Stop and report instead of guessing when:

- another task is actively modifying overlapping files;
- the accepted Goal 1-3/A-C invariants would need to be weakened;
- implementation requires an external Framework owner change;
- background assets cannot be admitted without remote/network discovery;
- Surface requires arbitrary CSS, classes, callbacks, templates or paths;
- Hero and Surface would need separate full-width/background engines;
- default parity cannot be isolated from an unrelated redesign;
- a live/test site write, release, merge, tag, push or publication is required.

Out of scope:

- homepage art-direction change from side image to background image;
- visual editor or drag-and-drop builder;
- arbitrary user CSS/classes/colors/sizes;
- video/parallax/fixed backgrounds;
- focal-point percentages or breakpoint-specific author props;
- nested Surface/Hero composition or `surface=inherit`;
- new Framework components or owner artifacts;
- release-review, merge, push, tag, publication or deployment.

## 13. First authorized assignment

```text
Goal S1 — Full-bleed Geometry & Shared Surface Runtime. На baseline HEAD 4eeb3e0b6578239415bd7b20b84c2a20305195a6 и принятом product candidate eb35f5c6f18e5eb9be69e91887b09486f5703136 исправить реальный direct-child full-bleed контракт landing Layout и реализовать один безопасный shared Surface presentation runtime плюс публичный container docara.surface по source/workflow/2026-08-06-docara-surface-hero-track.md. Surface должен поддерживать content/full outer width, container/full inner width, локальный декоративный background media layer, enum fit/X/Y, token overlay/strength/padding/tone и registry-owned child capability с точным slot/count/order/depth contract. Сохранить один Markdown -> typed IR -> renderer registry -> Gateway -> LayoutComposer -> PageBuilder path; не создавать второй parser/registry/renderer/background engine, arbitrary CSS/class/PHP/callback/template/path или component-ID ветвление в shared pipeline. Existing Hero HTML/semantics сохранить; в S1 не добавлять Hero background mode и не менять homepage art direction. Обязательны focused/full tests, Atlas/schema/container/security negatives, preview/production, two full + representative single determinism, static/package, browser landing/docs desktop/mobile/light/dark/LTR/RTL/no-overflow/inner-alignment, rollback, specification/graph/handoff/evidence и clean worktree. Остановиться на goal_s1_ready_for_independent_audit; не начинать S2, не менять внешние repos/sites, не merge/push/tag/release/deploy.
```
