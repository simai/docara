# M4: unified public Russian publication contour

Status: `in_progress`

Input revision: `230ce7504e72162dfb85db4687ba851b49353335`

Branch: `codex/docara-unified-architecture`

Evidence index:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/INDEX.md`

## Outcome

The current 103-page Russian public site has one physical Markdown owner per
route and no generated public page owner. Full and isolated builds select the
route before irrelevant compilation/projection, pass through one PageBuilder,
typed in-memory Document IR and the existing renderer registry/Smart gateway.
Legacy publication paths are removed only after parity and zero-reference
proof. This goal does not claim another locale, release or production deploy.

## Frozen input

- exact M3 audit revision: `230ce7504e72162dfb85db4687ba851b49353335`;
- selected pages: 103;
- physical Markdown owners: 89;
- generated public page owners: 14, all under `/ru/examples/`;
- output files: 309;
- normalized baseline tree SHA-256:
  `fb9ea27bb35fc67ad4b7da9247e34006b9f641f0c59f0f68a574328c9894cdab`;
- static baseline: 206 HTML, 18,942 local references, zero broken;
- inventory SHA-256:
  `0b0fbfef39359545d565809ccfd88ce648debcb9b84bd4c384ea9bc619a65a84`;
- 14-route full/single parity ledger SHA-256:
  `70fcfdd36a519353157f2c7db4cd55b6de54a6bb5fd2b9b0a8a75224c66acb30`.

## Invariants

1. A public route has one `docs/site/content/ru/<route>.md` owner.
2. Page prose lives only in that Markdown owner. Shared Russian UI labels live
   only in `docs/site/content/ru/lang.json`.
3. The compiler creates typed Document IR in memory; serializations are
   disposable diagnostics, cache, search or evidence only.
4. Full and isolated builds use the same PageBuilder and renderer pipeline.
5. Every Smart node uses the existing registry and SmartComponentGateway.
6. No Alert/example/projector-specific page engine or second content registry.
7. Other locales and package-owned CLI messages are not silently migrated or
   deleted.
8. No legacy deletion precedes positive parity, negative zero-reference proof
   and a commit-addressable rollback path.

## Bounded execution sequence

### M4.1 Recovery and durable deletion contract

- preserve the exact 103-route owner inventory and all 14 legacy route hashes;
- capture representative browser screenshots before migration;
- inventory every projector, trusted-main bridge, coarse IR and Smart consumer;
- update graph lifecycle from M3-blocked to M4-in-progress only;
- checkpoint with graph/JSON/hygiene validation.

### M4.2 Physical examples migration

- add `content/ru/examples.md` and thirteen
  `content/ru/examples/<id>.md` owners;
- preserve public URLs, canonical demonstrator results, source visibility,
  metadata, navigation, search, outline and previous/next;
- add only generic typed nodes/renderers required for an internal preview and
  source presentation;
- select an isolated route before scanning unrelated example descriptors;
- prove focused/full/single/static/browser parity in small commits.

### M4.3 Generated page path retirement

- reduce the generated allowlist from 14 to zero only after M4.2 parity;
- remove the RU public output role from PortableDeclarativeExampleProjector;
- classify PortableComponentCatalogProjector consumers by locale and remove
  only public page generation proven unused by the current contour;
- remove obsolete public receipts/tests only after zero-reference proof.

### M4.4 One typed rendering contour

- map consumers of InlineComponentRenderer, Declarative DocumentParser,
  MarkdownNode, SmartRenderer, `buildGenerated()` and trusted main HTML;
- converge all 103 pages on PageBuilder typed IR and the one registry/gateway;
- remove `buildGenerated()`, trusted-main bypass and obsolete coarse nodes only
  when positive/negative tests prove no consumer remains.

### M4.5 Integrated acceptance

- prove 103 physical owners and zero generated public owners;
- prove all-route full/single HTML parity;
- produce two byte-identical disposable full builds;
- run PHPUnit, focused negative tests, lint, JSON/YAML/graph validation,
  formatter, static verification and `git diff --check`;
- browser-test representative landing/docs/examples/components and smoke every
  route at 1920/1440/390, light/dark, keyboard/focus/copy/tabs, zero errors and
  zero overflow.

### M4.6 SOT and handoff

- update graph mappings/gates and accepted specification only to proven state;
- record deletion map, rollback commits, nonclaims and remaining M5/release;
- run reverse-outcome audit and leave a clean worktree.

## Deletion gates

For each candidate, evidence must name the old owner, new owner, consumer scan,
focused positive test, focused negative test, full/single parity, rollback SHA
and exact deletion commit. A candidate that still has a legitimate non-Russian
or package consumer remains and is explicitly classified; it is not called
retired.

## Rollback

Each green batch is a separate commit. Revert the smallest candidate commit to
restore its previous owner/path. The M3 input revision above is the whole-goal
fallback. Generated output is never edited and can be discarded/rebuilt.

## Stop conditions

Stop only for destructive divergence from the accepted M3 baseline, an
unavoidable external Framework/dependency change, irreversible migration
without parity/rollback, an unsafe conflict with user work, or a product choice
that changes a public URL or meaning. Ordinary parser, renderer, CSS, build,
test or browser defects are fixed inside this goal.

## Current checkpoint

M4.1-M4.4 are green. All 103 selected routes now have physical Markdown
owners and pass through one typed PageBuilder artifact; generated public
owners, public projector paths and trusted-main/generated bypasses are zero.
M4.5 integrated all-route/browser acceptance is active. M4 PASS, release and
production remain unclaimed.
