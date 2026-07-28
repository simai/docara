# Workflow: Symmetric locale routing for Docara

Date: 2026-07-21
Status: accepted locally
Workflow ID: `2026-07-21-docara-symmetric-locale-routing`
Process model: `full_qa`
Current state: `review_ready`
Target state: `review_ready`
Project mode: productization
Requested level: goal
Recommended level: goal
Scale reason: the result changes the portable configuration contract, URL
generation, migration behavior, documentation source tree, SEO identity and
local publication; it is larger than one implementation batch.
Parent track: `docara-consolidation`
Owner: `docara`
Reviewers: `docs`, `content`
Gatekeepers: `tester`, `seo`, `ops` for local publication
Recovery source: this file
Memory decision: `skip`
Memory reason: this proposed workflow and current repository facts are the
authoritative planning source; no personal-memory update is needed.
Baseline HEAD: `5727b10fb1648a675c1857caf74632e3986334b5`
Live Goal: completed against the local exact build on 2026-07-21.
Implementation candidate: `e8aaac5665b99415034928ad6bc8c63f7ff6b831`.

## Completion Summary

- symmetric locale routing, root routing and legacy redirects are implemented;
- Docara and its starter use `content/ru` and `/ru/`;
- all generated and authored internal links use the locale projection;
- 618 tests and 5,426 assertions pass;
- two production builds are byte-identical;
- static verification checks 271 pages and 20,566 references with zero broken;
- the accepted tree is rollback-safely published to `docara.test` and passes
  desktop/mobile browser acceptance;
- detailed evidence is stored in
  `source/workflow/evidence/2026-07-21-docara-symmetric-locale-routing/`.

No public push, release, sitemap or hosting-level 301/308 readiness is claimed.

## Kaizen Review

No reusable lesson found outside the product boundary: the locale-routing and
internal-link projection rules are already captured in product code, schemas,
tests and Docara documentation. This goal found no canonical federation or
owner-skill correction that should be proposed separately.

## Current Position

- the Docara engine already builds arbitrary BCP 47 locale registries and was
  tested with `ru`, `en`, `ar`, `zh-Hans` and `fr-CA`;
- each locale can already own a content root, language pack, direction and
  public prefix;
- the current Docara documentation is a single-locale Russian project:
  `content_root=content`, `default_locale=ru`, `public_prefix=""`;
- therefore Russian content and URLs are currently unprefixed;
- the current portable redirect contract cannot use the empty route as a
  redirect source, so a generated root redirect is not yet a complete product
  capability.

## Current Goal

Make symmetric locale routing the recommended Docara product model: every
locale owns an isolated `content/<locale>` tree and an explicit `/<locale>/`
URL prefix, while `/` deterministically routes to the configured default
locale. Migrate the current Russian Docara documentation to `content/ru` and
`/ru/` without losing old URLs, static portability, deterministic builds,
search, navigation, Smart components, RTL support or update safety.

## Final Outcome

Docara can initialize, configure, build, document and verify single- and
multi-language sites through one explicit locale-routing contract. The product
starter and the Docara documentation use the same symmetric structure, while
legacy unprefixed projects retain a supported compatibility mode and migration
path.

## Done When

- `docara.json` has a validated locale-routing contract with explicit policies
  for locale prefixes, the root route and browser-language detection;
- the recommended mode is `all locales prefixed`, `root redirects to
  default_locale`, `browser detection disabled`;
- every configured locale has a distinct `content_root` and `public_prefix`;
- `/` produces a safe static redirect/fallback page for the configured default
  locale and exposes an `x-default` language identity;
- the existing compatibility mode where the default locale lives at `/`
  remains supported and tested;
- current Docara Russian sources move from `content/` to `content/ru/` and are
  published under `/ru/`;
- every old prefixless public documentation route has a deterministic redirect
  to its `/ru/` counterpart; redirect loops, chains and collisions fail closed;
- canonical, `hreflang`, sitemap, search index, navigation, breadcrumbs,
  previous/next, generated catalogs, examples and assets use the correct
  locale prefix;
- missing translations do not create invented pages or cross-language content
  fallback; language packs may use declared fallback only for interface copy;
- LTR, RTL and arbitrary BCP 47 tags pass focused and negative tests;
- duplicate production builds are byte-identical and static verification finds
  no broken local references;
- the exact candidate is rollback-safely published to local `docara.test` and
  accepted on desktop/mobile for `/`, `/ru/`, `/en/` fixture and `/ar/` RTL
  fixture;
- author and migration documentation explains source folders, URL policy,
  default locale, adding a language, missing translations and old-URL
  migration;
- no public push, release or production-readiness claim is made inside this
  goal.

## Proposed Contract

The exact schema names must be finalized in the architecture batch. The
recommended semantic shape is:

```json
{
  "default_locale": "ru",
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false
  },
  "locales": {
    "ru": {
      "content_root": "content/ru",
      "public_prefix": "ru"
    },
    "en": {
      "content_root": "content/en",
      "public_prefix": "en"
    }
  }
}
```

This is a proposed contract, not a currently accepted public API.

## Architecture Flow

```text
docara.json locale registry
  -> locale-routing validator
    -> per-locale content roots
      -> canonical route projector
        -> generated root route
        -> authored and generated locale pages
        -> legacy migration redirects
          -> canonical/hreflang/x-default/search/sitemap
            -> deterministic static artifact
```

Language and documentation version remain separate dimensions. This goal
standardizes locale routing only; a future version-routing contract must not be
silently mixed into it.

## Stages

- completed: contract and compatibility boundary;
- completed: routing and metadata implementation;
- completed: product integrations;
- completed: starter and documentation migration;
- completed: user and developer documentation;
- completed: full acceptance and local publication.

### 1. Contract and compatibility boundary

Define the final schema, defaults and invariants. Preserve the current
default-unprefixed mode, introduce the symmetric prefixed mode and specify
static-host behavior for `/`.

Acceptance: schema examples, failure codes, compatibility matrix and URL matrix
are reviewable before renderer changes.

### 2. Routing and metadata implementation

Implement root-route publication, locale-prefixed authored/generated routes,
legacy migration redirect planning and shared canonical URL projection.

Acceptance: focused positive/negative tests cover collisions, loops, missing
targets, base URLs, arbitrary BCP 47 tags and deterministic output.

### 3. Product integrations

Apply the locale projection consistently to component catalogs,
demonstrators, declarative preview, search, navigation, breadcrumbs,
previous/next, assets, canonical links, `hreflang`, `x-default` and sitemap.

Acceptance: no subsystem builds a locale URL independently of the canonical
projector.

### 4. Starter and documentation migration

Update the portable starter and migrate `docs/site/content` to
`docs/site/content/ru`. Generate exact compatibility redirects from the current
prefixless documentation routes to `/ru/`.

Acceptance: source inventory is preserved, no Markdown or sidecar is lost,
and all old public paths resolve to one exact new route.

### 5. User and developer documentation

Update configuration, localization, multilingual-site, migration, static
hosting and troubleshooting guides. Clearly separate content translation from
language-pack fallback and locale routing from documentation versioning.

Acceptance: a new author can create Russian, English and RTL locale trees from
the guide without reading PHP source.

### 6. Full acceptance and local publication

Run the complete suite, deterministic builds, static verification, SEO URL
checks and desktop/mobile browser acceptance. Back up and atomically publish
the accepted artifact to `docara.test`.

Acceptance: all Done When rows have evidence and the exact candidate receives
tester acceptance; the previous local build has a documented rollback path.

## Batches

1. Freeze route and compatibility matrices; add schema and negative fixtures.
2. Add canonical locale URL projector and generated root route.
3. Add deterministic legacy unprefixed-route migration redirects.
4. Migrate all authored and generated product surfaces to the projector.
5. Update starter, init/update behavior and migration tooling.
6. Move the Docara Russian content tree to `content/ru`.
7. Update author, migration, hosting and troubleshooting documentation.
8. Run focused/full/negative/deterministic/static acceptance.
9. Publish locally with backup and verify desktop/mobile, LTR/RTL and redirects.
10. Record reverse-outcome audit, project memory and closure.

## Owner Map

| Area | Owner | Reviewer / gatekeeper |
| --- | --- | --- |
| portable locale contract and generator | `docara` | `dev`, `tester` |
| documentation source migration and author guides | `docs` | `content`, `tester` |
| canonical, hreflang, x-default, sitemap and redirect policy | `seo` | `tester` |
| local ServBay publication, backup and rollback | `ops` | `tester` |
| workflow, scope and acceptance continuity | `teamlead` | reverse-outcome audit |

## Track Linkage

- parent track: `docara-consolidation`;
- previous accepted goal: Docara Smart-component unification;
- this goal removes the remaining locale-routing asymmetry before public
  product/release preparation.

## Scope

- portable site schema, locale registry and route generation;
- generated root route and static fallback behavior;
- compatibility and migration redirects;
- all generated Docara product surfaces that emit URLs;
- starter/init/update contract;
- current Docara documentation source tree and local `docara.test` artifact;
- tests, documentation, workflow, evidence and project memory.

## Non-Goals

- translating the full Russian documentation into English or other languages;
- automatic machine translation;
- documentation-version URL design;
- geo/IP-based locale selection;
- forced `Accept-Language` redirect;
- public push, merge, tag, package release or production deployment;
- external consumer migrations not required to verify the new contract.

## Facts And Assumptions

Facts:

- multi-locale building, language packs, RTL, language switcher, `hreflang` and
  locale-aware search already exist;
- the current Docara documentation has only the Russian locale and no `ru`
  source directory or URL prefix;
- `public_prefix` already controls locale URL prefixes;
- the current redirect schema cannot represent an empty root source.

Assumptions:

- symmetric locale directories and URLs are the new recommended product
  default, while old projects require compatibility;
- `/` should route deterministically to `default_locale`, not guess from the
  browser language;
- existing prefixless Docara documentation URLs must remain usable after the
  migration.

No blocking product question remains for planning. Execution confirmation is
the next human checkpoint.

## Risks And Mitigations

| Risk | Mitigation |
| --- | --- |
| old external links break | generate and verify one redirect for every old route |
| redirect loop or chain | fail-closed route graph validation |
| assets or generated pages miss `/ru/` | one canonical locale URL projector for all consumers |
| duplicated SEO pages | canonical/hreflang/x-default matrix and SEO gate |
| incomplete translation appears as Russian | no Markdown fallback; absent translation remains absent |
| current single-locale projects break | retain and test default-unprefixed compatibility mode |
| local publication regresses | exact build, timestamped backup, atomic swap and browser smoke |

## Stop Conditions

- preserving old URLs requires duplicate content instead of redirects;
- one generated subsystem cannot consume the canonical locale URL projector;
- migration loses or overwrites authored Markdown/JSON;
- root behavior requires a hosting-only dependency and lacks a portable static
  fallback;
- SEO, tester or static verification finds contradictory route identities;
- unrelated worktree changes overlap the planned write scope.

## Evidence Plan

Evidence root:
`source/workflow/evidence/2026-07-21-docara-symmetric-locale-routing/`

- contract and compatibility matrix;
- route and redirect fixtures;
- source migration inventory;
- integration consumer audit;
- focused, negative and full test results;
- deterministic/static build report;
- SEO URL matrix;
- deployment and rollback record;
- desktop/mobile/LTR/RTL browser acceptance;
- exact-candidate tester verdict;
- reverse-outcome audit.

## First Batch

Freeze the route matrix and compatibility policy, then add the schema contract
and failing/positive fixtures before changing the publisher.

## Completion Gate

Do not complete the goal while any authored/generated route bypasses the
canonical locale projector, any old prefixless documentation URL lacks an exact
redirect, static verification reports a broken reference, deterministic builds
differ, or the exact-candidate tester verdict is not PASS.

## Current Remaining

No work remains inside this local goal. Public hosting redirects, sitemap,
translations, push and release are explicitly separate future scopes.

## Next Safe Batch

If a public release is requested later, prepare a separate release workflow
covering hosting-level 301/308 redirects, sitemap policy, public monitoring and
consumer migration.

## Kaizen

No reusable lesson found outside the product boundary. Product rules are
captured in Docara source, schema, tests and documentation; no owner-skill or
federation source update is proposed.
