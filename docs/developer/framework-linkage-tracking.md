# Framework linkage tracking (design proposal)

Status: proposal; phase 1 is implemented (read-only report and build line).
Owner of the input formats: SIMAI Framework (`simai/ui-source`, `simai/ui-builder`).

A documentation site of SIMAI Framework units (the Framework's own docs, a
component-set site) should know, before it publishes, which of its pages
describe units that changed in a way that needs a docs update -- and why, in
the Framework's own words. The Framework release route already produces the
data for it under standard `simai.linkage` 1.0.0. This document says what
Docara reads, how it matches pages to units, what it reports and what still
has to be agreed with the Framework.

## 1. What the Framework produces today

All of this was read from real files on 2026-11-09; nothing below is invented
by Docara.

| Input | Schema (`schema`, `schema_version`) | Where it lives | Real example |
| --- | --- | --- | --- |
| Unit inventory of a release | `simai.framework.unit-inventory`, 1 (pinned `ui-source/contracts/standards/pinned/unit-inventory-1.0.0.schema.json`) | `simai/ui` `contracts/releases/ui-<12>.inventory.json`; named by the release lock v2 under `unit_inventory` (`path`, `sha256`, `units`) | `ui/contracts/releases/ui-b4a77681f0a7.inventory.json` (404 units) |
| Change report between two releases | `simai.framework.unit-change-report`, 1 (pinned `unit-change-report-1.0.0.schema.json`) | `simai/ui` `contracts/releases/ui-<12>.changes.json`; named by the release lock under `unit_inventory.change_report` (`path`, `sha256`, `from`) | `ui/contracts/releases/ui-b4a77681f0a7.changes.json` (from `ui-cb287da78ab1`) |
| Change journal | `simai.change-journal`, 1 (pinned `change-journal-1.0.0.schema.json`) | `simai/ui-source` `contracts/changes/journal.json` only -- not in `simai/ui`, not in the npm/CDN distribution | 130 records |
| Package inventory / changes (ui-pro) | same two schemas | GitHub release assets `package-inventory.json`, `package-changes.json` of `pro-<12>` (`ui-control/tools/release/publish-package-asset.sh`) | none on disk; produced by `tools/release/package-inventory.mjs` |
| Usage record (consumer side) | `simai.framework.unit-usage`, 1 (`ui-builder/schemas/simai.framework.unit-usage.v1.schema.json`) | kept by the consumer | `ui-builder/tests/linkage/fixtures/usage.json` |
| Reconcile report | `simai.framework.unit-reconcile-report`, 1 (`ui-builder/schemas/…reconcile-report.v1.schema.json`) | output of `ui-builder/scripts/reconcile.mjs` (`sf-reconcile`) | -- |

Field names used by Docara:

- inventory: `source`, `revision`, `release`, `units[]` with `id`
  (`<kind>.<name>`), `kind` (`component`, `utility`, `plugin`, `smart`,
  `port`, `tokens`, `font`, `icons`, `module`, `core`), `version`,
  `content_sha256`, `dependencies[]` (`id`, `range`);
- change report: `source`, `from`/`to` (`revision`, `release`), `added`,
  `removed`, `changed[]` (`id`, `from_version`, `to_version`, `step`),
  `dependencies_changed[]`, `violations[]`;
- journal record: `id`, `recorded_on`, `stage` (`compat`, `removed`, ...),
  `items[]` (`component`, `from`, `to`), `rewrite` (`none` asks for nothing),
  `manual`, `summary`, `examples.before/after`, `remove_after`, `removed_on`,
  and -- defined by reconcile but not yet used by any real record -- `units[]`
  / `unit` + `version` for per-version entries.

**"Journal"** is the change journal above: the dated record of every rename,
removal and consumer-facing change, and the text of what a consumer must do
(`manual`). **"Foundations"** are units of kind `tokens`, `font` and `icons`
(13 in `ui-b4a77681f0a7`: `tokens.colour`, `tokens.space`, `font.inter`,
`icons.material-symbols`, ...). Requirement `foundations-count`: a page that
documents a unit also depends on the foundations the unit reads, transitively
through other units but never through `core.*` / `module.*`; a changed colour
token marks every page drawn with it even when no component version moved.

**Reconcile already exists.** Requirement `one-reconcile` says one command
gives every place one status -- `current`, `update-available`, `former-name`,
`action-needed` (a major step, or a journal entry asks for a change, with the
entry's instruction quoted), `missing` -- and the owner decided (2026-10-27)
that it lives in ui-builder. `ui-builder/scripts/reconcile.mjs` implements it
(commits `86fd667`, `71ef256`), with fixtures in `ui-builder/tests/linkage/fixtures`.

## 2. What Docara already has

- `documentation_tracking` (`src/Documentation/*`): a `documentation.lock.json`
  (`docara.documentation_lock.v1`) binding each source entity key to the page
  that documents it (`key`, `route`, `page_path`, `source_sha256`,
  `page_sha256`, `examples_sha256`, `review`), `docara documentation
  status|accept`, `.docara/documentation-status.json` in every build and one
  build line. The `simai_framework` provider derives entity keys
  `component.<name>`, `smart.<name>`, `utility.<family>`, `core.*` -- **the
  same ids as linkage units**: in ui-doc 286 of 295 lock keys are unit ids of
  `ui-b4a77681f0a7`; the rest are former names (`smart.inputs`), utilities
  that left (`utility.font-size-ext`) and Docara's own `core.design-tokens`.
- `translation_tracking`: the model this feature copies -- a lock in the site,
  a `status` command, a receipt in the build, one build line, never a failed
  build in report mode.
- `simai-framework.lock.json` (`docara.framework_lock.v1`) pins one release by
  `runtime.pair_id` (`ui-<12>`), and already pins hash-bound contract files
  copied into the site (`framework_registry.documentation_source`:
  `relative_path`, `file_sha256`).

## 3. Key decisions

1. **No new page markup.** Which unit a page documents is already in
   `documentation.lock.json` (entity key = unit id). Docara derives the usage
   record from it: one place per accepted entry, `place` =
   `docs:<route>`, `note` = the page path, `units` = the entry's key with the
   version and hash of the baseline inventory. Front matter or
   `:::component_reference` scanning is not needed for the Framework's own
   docs; it may come later for pages that show several units (phase 3).
2. **The standard's formats, unchanged.** Docara reads inventories, change
   reports and the journal as published, emits the derived usage record as
   `simai.framework.unit-usage` v1 and the result as
   `simai.framework.unit-reconcile-report` v1, so `sf-reconcile` can be run on
   the same inputs and must give the same answer. A site that prefers to keep
   its own record (several units per page, examples, hand-kept) names it with
   `usage` and Docara uses it as is.
3. **A PHP twin of reconcile, held to the Framework's fixtures.** Docara builds
   run without Node, so Docara does not shell out to `sf-reconcile`. It ports
   the reconcile algorithm (`src/Documentation/FrameworkLinkageReconciler.php`)
   and proves parity with golden reports produced by `reconcile.mjs` itself on
   ui-builder's own fixtures (`tests/fixtures/framework-linkage/conformance`).
   When reconcile changes, the fixtures are regenerated and the test says
   where the twin diverged. (Alternative, if the Framework prefers: Docara only
   reads a report `sf-reconcile` wrote; see question F8.)
4. **Inputs are files in the site, pinned like the rest.** No network at build
   time. The site copies the inventories, change reports and journal it needs
   (e.g. `contracts/linkage/`) and names them in `docara.json`.
5. **Smallest surface: folded into documentation tracking.** Configuration is
   an optional `linkage` object of a documentation source; the command is
   `docara documentation linkage`; the build receipt is
   `.docara/framework-linkage.json`. No new top-level setting, no new lock
   file in phase 1.
6. **Report, never block, never edit.** Like translation tracking in report
   mode: the build prints one line and the places that need action, and stays
   valid. Invalid inputs fail closed with a diagnostic code, as documentation
   tracking does.

## 4. Configuration

```json
"documentation_tracking": {
  "enabled": true, "source_locale": "ru", "mode": "report",
  "lock_file": "documentation.lock.json",
  "sources": [{
    "id": "simai-framework",
    "provider": "simai_framework",
    "framework_lock": "simai-framework.lock.json",
    "linkage": {
      "inventory": "contracts/linkage/ui-b4a77681f0a7.inventory.json",
      "since": "contracts/linkage/ui-cb287da78ab1.inventory.json",
      "changes": ["contracts/linkage/ui-b4a77681f0a7.changes.json"],
      "journal": "contracts/linkage/journal.json",
      "brought_up_on": "2026-10-15"
    }
  }]
}
```

- `inventory` (required): the release the site builds against -- for a
  `simai_framework` source, the inventory of the pinned `runtime.pair_id`;
  another release is reported (`DOCUMENTATION_LINKAGE_RELEASE_MISMATCH`).
- `since`: the inventory of the release the pages were last brought up to (the
  baseline). Required unless `usage` is given.
- `changes`: change reports between the baseline and the target; stand in for
  `since` when checking foundations.
- `journal`: the change journal; without it no instruction is quoted and a
  former id reads as missing.
- `brought_up_on`: the date the pages were brought up to `since`; journal
  records without a unit version (all of today's) count when recorded after
  it.
- `usage`: a hand-kept `simai.framework.unit-usage` record instead of the
  derived one.

## 5. Reconciliation algorithm

Exactly `reconcile.mjs` (ui-builder `docs/developer/linkage-reconcile.md`):

1. Build the usage record (derived or `usage`). Lock entries whose key is not a
   unit of the baseline are listed as `unmatched` (with route), not guessed.
2. For each unit of each place: find it in the target inventory (by `source`,
   else by id); if absent, follow the journal's former ids (`items[].from` ->
   `items[].to`): `former-name` until `remove_after`, `missing` once
   `stage: removed`; else `missing`.
3. Same id: equal hash -> `current`; newer patch/minor -> `update-available`;
   major (or minor below 1.0.0) -> `action-needed`; same version, other hash ->
   `update-available` (a release-gate violation); older -> `action-needed`.
4. Journal entries between the two versions (versioned entries), or recorded
   after `brought_up_on` and naming the Smart tag `sf-<name>` (unversioned
   entries), with `rewrite` other than `none`, make the unit `action-needed`
   and are quoted (`manual`, else `summary` + example).
5. Foundations: the transitive `tokens`/`font`/`icons` dependencies (not
   through `core`/`module`) that moved between baseline and target add `via`
   entries with the dependency chain; the unit takes the worse status.
6. A place takes its worst unit's status; the report totals places and units.

## 6. Outputs

- `docara documentation linkage [--source <id>] [--json] [--today yyyy-mm-dd]`:
  a person-readable list (like `sf-reconcile`'s summary) or
  `docara.framework_linkage_status.v1` JSON: per source the target, baseline,
  diagnostics, `unmatched` lock entries, the derived `usage` record and the
  `report` (`simai.framework.unit-reconcile-report` v1).
- Build: `.docara/framework-linkage.json` (the same JSON) and one line, e.g.
  `Framework linkage against ui-b4a77681f0a7: 3 page(s) need action, 12 have
  updates; run "docara documentation linkage". The build remains valid.`
  followed by up to ten `action-needed`/`missing` pages with the first quoted
  instruction.

Diagnostic codes (all fail closed except the warning):

| Code | When |
| --- | --- |
| `DOCUMENTATION_LINKAGE_NOT_CONFIGURED` | `documentation linkage` on a site where no source has `linkage` |
| `DOCUMENTATION_LINKAGE_INVENTORY_INVALID` | an inventory is not `simai.framework.unit-inventory` v1 or a unit lacks a valid id, version or hash |
| `DOCUMENTATION_LINKAGE_CHANGES_INVALID` | a change report is not `simai.framework.unit-change-report` v1 |
| `DOCUMENTATION_LINKAGE_JOURNAL_INVALID` | the journal is not `simai.change-journal` with `records` |
| `DOCUMENTATION_LINKAGE_USAGE_INVALID` | the usage record breaks `simai.framework.unit-usage` v1 |
| `DOCUMENTATION_LINKAGE_BASELINE_REQUIRED` | neither `since` nor `usage` is given |
| `DOCUMENTATION_LINKAGE_RELEASE_MISMATCH` (warning) | the target inventory is not the release the source's framework lock pins |

## 7. What Docara needs from the Framework (questions to agree)

- **F1. Where a site gets the inventory.** Today it is only in `simai/ui`
  `contracts/releases/`, not in `distr/` nor the npm package
  (`not_yet_checked` of the standard says so). Will the inventory (and the
  change report from the previous release) travel inside the distribution, e.g.
  `distr/core/contracts/unit-inventory.json`, so Docara can take it with the
  bytes it already bundles per release? Until then the site copies it.
- **F2. Pinning by hash.** The release lock v2 has `unit_inventory.path`,
  `sha256`, `change_report`. Can the Docara site lock
  (`docara.framework_lock.v1`, written by the sync tooling) carry the same
  pointer, like `framework_registry.documentation_source`, so Docara verifies
  the inventory against the pinned release instead of trusting the copy?
- **F3. The journal in a release.** The journal is in `ui-source` only. Which
  public, versioned location should a consumer read -- a copy in `simai/ui`
  per release (`contracts/releases/ui-<12>.journal.json` or inside the
  inventory), or `raw.githubusercontent.com/simai/ui-source/<commit>`? A
  consumer must read the journal at the release's commit, not `main`.
- **F4. Per-version journal entries.** No real record carries `units[]` /
  `unit`+`version` yet, so every quote is matched by Smart tag and date
  (`brought_up_on`) and never for components, utilities or tokens. When will
  `journal-per-version` be enforced, and is `units: [{id, version}]` the final
  field?
- **F5. Unit ids of documentation entities.** Docara's `simai_framework`
  provider keys are unit ids today by convention. Confirm that `component.*`,
  `smart.*`, `utility.<folder>` keys are the inventory's ids and stay so, and
  say how former Smart ids (`smart.inputs`) and dropped utilities should be
  shown (we report them `unmatched`).
- **F6. Packages and component sets.** ui-pro publishes
  `package-inventory.json` as a release asset, but the package receipt
  (`simai.framework.product-package-receipt` v1) does not name it or its
  hash; a component set (`set.json`) has no inventory yet. Can the receipt
  carry `inventory: {path, sha256}` and `changes`, so Docara's package sync
  (`scripts/sync-framework-package-runtime.php`) takes them with the package?
- **F7. Consumer kind and place locator.** Is `consumer.kind: "docs"` and
  `place: "docs:<route>"` (route of the source-locale page) the agreed form for
  documentation sites? Should the place also carry the page's content hash so
  a Figma-kit-style "made against" check is possible?
- **F8. One reconcile.** The standard wants one command. Docara proposes a PHP
  twin held to `ui-builder/tests/linkage/fixtures` golden reports. Is the
  Framework willing to treat those fixtures (plus expected reports) as the
  conformance suite and announce algorithm changes in its release notes -- or
  should Docara instead consume a reconcile report produced by `sf-reconcile`
  in the site's release route?
- **F9. Report schema version.** Docara emits
  `simai.framework.unit-reconcile-report` v1 as `reconcile.mjs` writes it. Are
  sort orders (journal entries by `recorded_on` then `id`) part of the
  contract? `reconcile.mjs` uses `localeCompare`, Docara compares bytes; they
  differ only for ids that differ by punctuation.

## 8. Phased plan

1. **Phase 1 (implemented).** `linkage` in `documentation_tracking.sources[]`;
   PHP reconcile twin with golden-report parity tests on the Framework's
   fixtures and on an excerpt of real `ui-cb287da78ab1` -> `ui-b4a77681f0a7`
   data; `docara documentation linkage`; build receipt and build line. Read
   only.
2. **Phase 2 -- accept.** `documentation accept` records, per lock entry, the
   unit id, version and `content_sha256` it was accepted against (an optional
   `linkage` field of the lock entry, written only when linkage is
   configured), so the baseline is per page and `since` is no longer needed;
   `validate project` reports `DOCUMENTATION_LINKAGE_ACTION_NEEDED` as
   `review_required`; status filters by linkage status.
3. **Phase 3 -- pinned inputs.** Once F1-F3 and F6 are agreed: read the
   inventory, changes and journal through the framework lock and the package
   receipts, verified by hash; drop the hand-copied files.
4. **Phase 4 -- more places.** Optional: pages that document several units
   (front matter `units:` or `:::component_reference` usage) and examples as
   their own places; an opt-in `mode: "require-current"` that fails a
   production build on `action-needed`/`missing` (requirement `no-silent-drift`
   says a publisher states what it leaves out of date).
