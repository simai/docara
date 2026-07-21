# Workflow: language-independent Docara

Date: 2026-07-21
Status: complete
Workflow ID: `2026-07-21-language-independent-docara`
Process model: `full_qa`
Current state: `accepted`
Target state: `review_ready`
Project mode: `productization`
Requested level: `goal`
Recommended level: `goal`
Track ID: `docara-consolidation`
Owner: `dev`
Coordinator: `teamlead`
Gatekeeper: `tester`
Memory decision: `skip`
Memory reason: the current repository is authoritative and prior Docara memory
describes the retired Jigsaw generation; all facts are reverified live.
Baseline HEAD: `bd9d42a87e41c2914c9ab7bcb8ff2fdf3b3d3f3f`

## Graph Gap

The federation route selects the installed `docara` skill, but the user has
confirmed that skill belongs to the legacy Jigsaw product and must not govern
the new declarative engine. The live repository contracts are authoritative;
`dev` owns implementation, `teamlead` owns goal control and `tester` owns
acceptance. No legacy Docara methodology is used.

## Track Goal

Build one declarative Docara product whose layouts, components, content and
generated sites can be used in any language without changing product code.

## Current Goal

Перевести новую Docara на полностью языконезависимую архитектуру: удалить зависимости от ru/en из схем, каталогов компонентов и PHP-кода; отделить технические манифесты от language packs; поддержать произвольное количество BCP 47 локалей, настраиваемые fallback-цепочки, отдельные деревья Markdown-контента, сборку всех локалей, переключатель языка и направления ltr/rtl. Мигрировать текущие русские и английские тексты без потери функциональности и проверить минимум ru, en, ar, zh-Hans и fr-CA.

## Final Outcome

A project declares any finite set of BCP 47 locales, content roots, language
packs, fallback chains and text directions. One build publishes every locale,
keeps navigation/search/alternate links isolated by locale, renders a language
switcher, supports RTL and resolves all product/component copy without
language-specific branches or required `ru`/`en` keys in technical contracts.

## Done When

- technical component, Smart, block, section and layout definitions contain no
  localized product copy and require no named language;
- all bundled Russian and English catalogue/UI copy lives in external language
  packs and remains available;
- locale validation accepts canonical BCP 47 examples including `ru`, `en`,
  `ar`, `zh-Hans` and `fr-CA`, with deterministic canonicalization;
- `docara.json` accepts an arbitrary non-empty locale map with per-locale
  content root, language pack, public prefix, direction and fallback chain;
- fallback resolution is exact locale -> configured fallbacks, detects cycles
  and fails closed when a required message is unresolved;
- one build publishes all configured locale trees without cross-locale menu,
  search or route leakage;
- every page exposes correct `lang`, `dir`, locale switcher and alternate
  locale links; layouts use logical-direction behavior for RTL;
- current single-locale projects remain buildable through a documented
  migration path without losing routes or content;
- fixtures prove at least `ru`, `en`, `ar`, `zh-Hans` and `fr-CA`, including
  three or more simultaneous locales, custom fallback, RTL and missing-pack
  negative cases;
- schemas, focused tests, full regression, deterministic duplicate builds,
  static verification and desktop/mobile browser acceptance pass;
- the accepted build is published only to `https://docara.test/` with local
  rollback evidence; no push, release or production readiness claim occurs.

## Scope

In scope: portable schemas, locale registry/resolver, language-pack repository,
component catalogue projection, new-engine UI copy, multi-locale content/build,
search/navigation/routes, language switcher, alternate links, RTL, fixtures,
tests, documentation and local generated site.

Out of scope: legacy Jigsaw Docara, automatic machine translation, translation
management SaaS, public release, repository merge/push/tag and production
deployment.

## Architecture Contract

```text
language-neutral manifests + semantic message IDs
  + docara.json locale registry
  + locale-specific Markdown trees
  + external language packs
  -> BCP47 canonicalization and fallback graph
  -> per-locale resolved page plans
  -> isolated route/navigation/search projections
  -> one deterministic multi-locale publication
```

No product code may branch on `ru`, `en` or another language identity. A
locale pack may contain any Unicode text, while identifiers, parameter names,
state tokens, renderers and schemas remain language-neutral.

## QA Plan

- static inventory: no hard-coded locale branches or named-language schema
  requirements outside language packs, test fixtures and localized content;
- contract tests: BCP 47 canonicalization, arbitrary keys, fallback order,
  cycle/missing-key/path/direction rejection;
- integration tests: five locales, separate content roots, route mapping,
  menus, search indexes, switcher and alternate links;
- compatibility tests: current Russian site and existing default-locale routes;
- deterministic tests: two clean multi-locale builds have the same manifest;
- browser matrix: desktop/mobile, LTR/RTL, locale switching, light/dark,
  navigation, search and no horizontal overflow or console errors.

## Scope Matrix

| Surface | Current defect | Required evidence | Status |
| --- | --- | --- | --- |
| Schemas | locale regex is partial; catalogue requires `ru` | schema and negative tests | complete |
| Component catalogue | copy is embedded in manifests | language-neutral definitions plus packs | complete |
| UI projectors | binary `ru`/English branches | message IDs resolved by pack | complete |
| Build | one configured locale only | one command publishes 5 locales | complete |
| Content | one shared root | independent locale roots | complete |
| Navigation/search | locale recorded but not multi-build isolated | cross-locale isolation tests | complete |
| HTML shell | `lang` exists, direction/switcher/alternates incomplete | browser and DOM evidence | complete |
| Compatibility | current local routes must survive | regression and HTTP matrix | complete |

## Stages

- [complete] Language-neutral contracts and locale runtime.
- [complete] Catalogue and UI language-pack migration.
- [complete] Multi-locale content and publication pipeline.
- [complete] Switcher, alternate links and RTL acceptance.
- [complete] Migration documentation, full regression and local publication.
- [complete] Reverse outcome audit and tester verdict.

## Batches

- [complete] Baseline inventory, workflow, QA/readiness matrices and locale
  architecture.
- [complete] Implement BCP 47 value object, locale registry, pack loader and
  fallback graph.
- [complete] Replace named-language schema assumptions and migrate component
  catalogue copy to external packs.
- [complete] Replace hard-coded projector/UI copy with translator message IDs.
- [complete] Implement multi-root discovery, per-locale plans and public routes.
- [complete] Add locale switcher, hreflang/alternates, search isolation and RTL.
- [complete] Add five-locale fixture, migration compatibility and documentation.
- [complete] Run full/static/determinism/browser acceptance and publish locally.
- [complete] Complete readiness matrix, commit and final audit.

## Findings Register

| ID | Finding | Severity | Disposition |
| --- | --- | --- | --- |
| I18N-001 | catalogue presentation schema requires only `ru` | blocker | resolved: technical records contain no presentation |
| I18N-002 | catalogue and demonstrator projectors branch on Russian vs English | blocker | resolved: semantic message IDs and Translator |
| I18N-003 | site/section/page locale regex is not full BCP 47 | blocker | resolved: central LocaleTag format/runtime |
| I18N-004 | builder rejects pages not matching one build locale | blocker | resolved: arbitrary locale registry build |
| I18N-005 | no first-class fallback graph or cycle detection | blocker | resolved: explicit deterministic fallback graph |
| I18N-006 | `dir` and language switcher are not first-class shell data | blocker | resolved and browser accepted |
| I18N-007 | shared runtime assets were initially published only at root | blocker | resolved: assets replicated into every locale prefix |
| I18N-008 | internal preview initially mixed locale route maps | blocker | resolved: preview is intentionally scoped to default locale |

## Readiness Matrix

| Requirement | Evidence | State |
| --- | --- | --- |
| Language-neutral manifests | static inventory | complete |
| Arbitrary BCP 47 locales | contract tests | complete |
| External language packs | pack fixtures and schema | complete |
| Fallback chains | resolver tests | complete |
| Multi-locale build | five-locale fixture plus static verifier | complete |
| Switcher and alternates | DOM/browser evidence | complete |
| RTL | Arabic fixture and responsive browser | complete |
| Compatibility | current site regression | complete |
| Documentation | author/migration guides | complete |
| Final acceptance | full suite and tester verdict | complete |

## Evidence Plan

Evidence root:
`source/workflow/evidence/2026-07-21-language-independent-docara/`

- `baseline.md`
- `contract-verification.md`
- `multi-locale-verification.md`
- `static-language-independence.md`
- `browser-acceptance.md`
- `deployment.md`
- `readiness-matrix.md`
- `acceptance.md`

## Allowed Changes

- new declarative engine schemas, resources, PHP runtime, templates and assets;
- new-engine documentation, fixtures and tests;
- durable workflow/evidence/project memory;
- local staging/build output and reversible `docara.test` publication.

## Forbidden Changes

- legacy Jigsaw repositories or old Docara skill sources;
- implicit Russian/English fallback in product code;
- locale-specific component IDs, renderer IDs or schema branches;
- arbitrary executable translation content;
- push, merge, tag, public release or production deployment.

## Stop Conditions

- locale resolution becomes nondeterministic or permits path escape;
- one locale can leak routes/search/navigation/content into another;
- current default-locale routes are silently broken without migration support;
- RTL requires language-specific templates instead of logical layout behavior;
- translated values can select templates, callbacks, scripts or executable code;
- full regression contradicts the intended compatibility claim.

## Current Remaining

Implementation, regression, deterministic duplicate build, local publication,
responsive LTR/RTL acceptance and reverse-outcome audit are complete.

## First Batch

Create the complete language-dependency inventory and implement the central
locale/language-pack contract before modifying renderers or moving content.

## Completion Gate

The readiness matrix must contain only `complete` or justified
`not_applicable`; the five-locale fixture and current Docara site must both
pass full/static/deterministic/browser acceptance.

## Do Not Complete Until

There are zero named-language branches in product PHP and zero required named
locale keys in technical schemas, all five requested locale scenarios are
proven, and the local served site matches the accepted generated output.

## Next Safe Batch

No implementation batch remains. A future public release requires its own
release goal and must not reuse this local acceptance as production readiness.
