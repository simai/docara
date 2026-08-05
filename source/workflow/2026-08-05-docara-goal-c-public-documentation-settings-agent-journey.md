# Docara Goal C — Public Documentation, Settings Reference & Agent Journey

Status: `goal_c_ready_for_independent_audit`

## Exact entry boundary

- accepted Goal B product/runtime: `c3b91eee71ab906cd79ae7a119c6961664f03528`;
- accepted Goal B governance/handoff: `481e34cccade12a0d7f8d2dbf9b4d37933e49419`;
- branch: `codex/docara-unified-architecture`;
- independent verdict: `PASS_WITH_NOTES` (`DOCARA-AUTO-AUDIT:019fd13b-7059-7691-83bc-60ef26a50f8c`);
- public baseline: 104 Markdown owners, 307 output files, 208 HTML, 21,844 local references, broken=0;
- complete-tree baseline: `1fc8625032cca56da7256b7eaac4981ddae11a3dd8263178337fc55666772274`;
- deterministic package baseline: `7dc6d43537abdb58a503808ba7fef4dd33d8e7a19b1e82ebe07befcfa109b205`.

The transient jsDelivr browser failure from the Goal B audit is an honest online-dependency note. Goal C does not claim offline Framework delivery.

## Outcome

Publish one discoverable Russian product journey across components, design,
settings, CLI/MCP and accepted demos. Visible prose remains in physical Markdown;
cards and exhaustive field facts are derived from the admitted Atlas and schemas.
The existing Markdown -> typed Document IR -> renderer registry ->
SmartComponentGateway -> LayoutComposer -> PageBuilder pipeline remains the only
publication path.

## Batches

- [x] C0 — freeze route/source/redirect/navigation inventory.
- [x] C1 — six component entry points and complete Markdown/container coverage.
- [x] C2 — design root, real insertion chain and interface matrix.
- [x] C3 — settings root, task guides and schema-derived field reference.
- [x] C4 — discover/plan/preview/dry-run/apply/validate CLI/MCP/AI journey.
- [x] C5 — accepted Framework/project demos with Atlas support labels.
- [x] C6 — integrated route/link/schema/build/browser/SEO evidence and handoff.

## C0 decisions

- Every one of the 104 entry routes remains canonical. No prose is moved or
  duplicated, and no existing route is retired.
- `redirects.json` remains empty. Locale-owned root and legacy unprefixed
  redirects remain builder-derived and are not duplicated in project config.
- Goal C adds canonical Markdown owners under `/components/`, `/design/`,
  `/settings/`, and `/development/` without changing existing URLs.
- Primary header order remains Главная, Быстрый старт, Компоненты, GitHub.
  New product roots are discoverable from the components root, section
  navigation and contextual links; header chrome is not expanded in C0.
- Exact entry inventory: [C0-ROUTE-INVENTORY.md](evidence/2026-08-05-docara-goal-c-public-documentation/C0-ROUTE-INVENTORY.md).

## Invariants and stop conditions

- one public page = one `content/<locale>/<route>.md` owner;
- repeated UI copy only in `content/<locale>/lang.json`;
- Atlas/schema projections are derived views, never prose owners;
- no arbitrary PHP/class/callback/template/filesystem paths;
- no second parser, renderer registry, Gateway, LayoutComposer, PageBuilder or preview engine;
- no unsupported Framework surface is labeled supported;
- stop if registries/schemas disagree with generated public indexes, links cannot
  be preserved, or browser/locale/accessibility parity cannot be proved.

## Rollback

The Goal B boundary `481e34cccade12a0d7f8d2dbf9b4d37933e49419` is the recovery point. Each green
batch is committed separately; rollback is by reverting Goal C commits, never by
rewriting history or deleting user changes.

The exact Goal C product candidate is
`ae6a1e918e248517b728cf40460d6c359991b66e`. Its complete public-tree digest is
`b8b47a837f2ac067434a8da27fd950d93e99916f107441dec42dedb3c9843e81`.
The only next action is an independent Goal C reverse-outcome audit.

## Nonclaims

No Goal D/release review, merge, push, tag, release, deploy, external owner write,
test/live-site write, offline Framework bundling, or additional Framework support.
