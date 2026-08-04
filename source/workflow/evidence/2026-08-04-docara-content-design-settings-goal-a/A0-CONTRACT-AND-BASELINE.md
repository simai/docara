# A0 — contract and baseline

Date: 2026-08-04
Status: `PASS`
Exact baseline: `d748eca04cd09e79ed6e2079a56b077265bcf905`

## Recovery and gate

- repository: `/Users/rim/Documents/GitHub/docara-unified`;
- branch: `codex/docara-unified-architecture`;
- initial tracked state: clean;
- preserved user proposal:
  `source/workflow/2026-08-04-docara-content-design-settings-track.md`;
- federation route: stale `docara` owner disabled, repository raw fallback;
- process resolver: local parser gap on historical launch YAML, no product
  blocker;
- action gates: env, repository hygiene and source policy PASS.

## Closed-path inventory

- `resources/schemas/declarative-section.schema.json` enumerates
  `branding`, `navigation`, `header_navigation`, `outline`;
- `DeclarativePageCompiler::boundProps()` matches the same four names;
- built-in Sections own those calls in `docara.header`, `docara.navigation` and
  `docara.outline`;
- `docara.navigation` already owns `default`, `header`, `tree`, `compact`
  Views/presets through one Smart artifact;
- `RegionCompositionResolver` already rejects executable authored surfaces and
  project code paths but does not yet admit registered binding IDs in authored
  blocks;
- DesignRegistry already supplies deterministic package/project ownership,
  duplicate, path and symlink policies and is retained unchanged as the design
  registry.

## Frozen contract

See the parent workflow and
`docs/specification/architecture/SHELL-CONTRACT.md`. Goal A adds one
`BindingRegistry`; it does not add another DesignRegistry or rendering path.

## Baseline reproduction

```bash
cd docs/site
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build goal-a-baseline
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build goal-a-baseline --page=/ru/components/alert/
```

Results:

- full: 104 routes, 307 files, 208 HTML;
- tree ledger after full and selected build:
  `89576ca2f272f044be688d636cb19b2f88de39f3ac909426c64d577e112df7db`;
- Alert HTML:
  `e1803412dc2ed849afc2f74711831ab2309df9f39f90c2034d2db0c43a281131`.

The first attempt through the binary shebang hit the known broken Homebrew PHP
`libicuio.73` dependency. The accepted ServBay PHP 8.4 command above is the
reproducible project toolchain and passed.

## Rollback

Return the branch to the parent of each Goal A batch with normal `git revert`.
The baseline build is disposable evidence and no external/live state exists.

## Governance validation

- `scripts/project-context.php generate` and `check`: PASS, issues `[]`;
- `ProjectContextContractTest`: 9 tests, 195 assertions, PASS;
- canonical project graph: 1 goal, 12 stages, 15 batches, 4 metrics,
  8 mappings, warnings 0, blockers 0;
- graph JSON: 67 files decoded with `JSON_THROW_ON_ERROR`;
- `git diff --check`: PASS.

The generated context is owned by canonical graph state and the active track,
not by Goal-specific hard-coded handoff phrases. Goal 3 is independently
accepted; Goal A is active; Goal B and release remain unauthorized.
