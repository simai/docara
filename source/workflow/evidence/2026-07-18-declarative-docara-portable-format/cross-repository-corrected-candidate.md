# Cross-repository corrected candidate evidence

Date: 2026-07-18
Status: EXECUTOR VERIFIED, PENDING INDEPENDENT ACCEPTANCE

## Exact candidates

- Standalone `simai/docara` baseline:
  `ba7724ae3d9e2b99388098637b81a35a2646e6a4` (`v1.3.65`).
- Standalone corrected export source:
  `26d0641c5df0b9b4e01b40324eaed2d97df30b62`.
- Larena Docara baseline:
  `8437fdae95fbfe024135fb1c10f8361ebc0e3422`.
- Larena corrected adapter candidate:
  `dd1e8e02cff40ae7d874759d7ec7afeddaf1727a`.
- Detached Larena evidence worktree:
  `/Users/rim/Documents/GitHub/larena-docara`, clean at the exact candidate.

## One-source boundary

Standalone Docara is the only owner of raw JSON/Markdown schemas, descriptor
inheritance, directive parsing and `ResolvedPagePlan` construction. Larena does
not parse the raw portable tree. It validates and consumes only the canonical
`.docara/resolved-page-plans.json` export, then projects the verified result to
Larena contracts and renders normalized component props again through its
Smart Registry.

The strict v1 presentation surface contains only working settings:
`layout.max_width`, `settings.theme`, and `navigation.hidden`. Markdown is the
only Smart-component authoring surface. Canonical component calls use
`docara.component_call.v1` with `id` and normalized `props`.

## Exact export

- schema: `docara.resolved_page_plans.v1`;
- bytes: `95971`;
- SHA-256:
  `3d42cf72c932554a4516d7f38085906c4e3a60ff49561f0783eae2d5c5ea3e1c`;
- guide page canonical hash:
  `5e7cd73d741743b904ab543d13349acffd4c1cc2f3899fa18816b2f87c27c8dc`;
- index page canonical hash:
  `571fe1a485ce972530084e62ec16f0569f6ef71f863920801c7449717b533c44`;
- landing page canonical hash:
  `b3db32f62125a5442307e8e17e2115d10609b35885f1b49416f207efba147078`.

## Verification

Standalone:

- focused HCS correction suite: 43 tests / 283 assertions, PASS;
- full suite: 323 tests / 1,033 assertions, PASS on PHP 8.4 with UTC;
- Pint, Composer validation, JSON/YAML parsing and `git diff --check`, PASS;
- repository/source/action gates, PASS;
- worktree clean at the corrected export source commit.

Larena:

- focused portable import suite: 24 tests / 148 assertions, PASS;
- full `composer quality:gate`: 71 tests / 878 assertions, PASS;
- PHPStan, lint, evidence contract and scope check, PASS;
- Composer validation, changed JSON parsing and `git diff --check`, PASS;
- local reversible candidate safety gate, PASS;
- primary package worktree and detached evidence worktree clean at
  `dd1e8e02cff40ae7d874759d7ec7afeddaf1727a`.

## Remaining acceptance

Fresh browser evidence and independent Tester/Human-Centered Simplicity
verdicts must be bound to the final standalone closure candidate and the exact
Larena candidate above. This executor record is not those verdicts.

## Nonclaims

- no production, publication, release, merge, tag or push readiness;
- no public licensing clearance for the exact Smart source projection;
- no fully offline Core runtime claim;
- no Larena database apply, audit mutation or round-trip export claim;
- no archive/delete readiness for `docara-template` or `docara-mix` before
  consumer migration and separate acceptance.
