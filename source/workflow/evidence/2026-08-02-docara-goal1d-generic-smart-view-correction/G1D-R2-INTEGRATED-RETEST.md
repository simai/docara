# G1D-R2 — integrated correction retest

Date: 2026-08-02
Implementation revision: `44acc1ff91233fa78140222fcb0589bf55b65ca0`
Verified governance/source revision: `01fce2b`
Branch: `codex/docara-unified-architecture`

## Tests and source checks

- focused compiler/provider/Gateway/renderer/search/cross-host matrix: 62 tests,
  454 assertions, PASS;
- documentation/structural/provider/cross-host matrix: 45 tests, 1,734
  assertions, PASS;
- full PHPUnit under ServBay PHP 8.4.20 with the exact SF5 checkout: 374
  tests, 7,346 assertions, PASS;
- Pint `--test`: PASS;
- Composer `validate --strict`: PASS. The installed Composer phar emits PHP
  8.4 dependency deprecation notices but returns success and reports
  `composer.json` valid;
- PHP lint: 314 repository PHP files, PASS;
- JSON: 465 files, PASS; YAML: 223 files, PASS;
- project graph validator: 1 goal, 9 stages, 12 batches, 6 mappings,
  warnings=0, blockers=0;
- `git diff --check`: PASS.

Exact cross-host output is stored in `cross-host-report-v3.json`: one unchanged
fixture, 45 assertions, both hosts exit 0, stderr/warnings empty, selected view
`default`, preset `compact`, slot `content`, and byte-identical HTML SHA-256
`7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`.

## Deterministic full and single builds

Two independent `git archive 01fce2b` source trees were built with the same
dependency tuple:

| Check | Result |
| --- | --- |
| selected routes | 103 / 103 in each build |
| files | 305 in each build |
| HTML files | 206 in each build |
| full-tree file ledger SHA-256 | `15a5f11f78c9dd115a2f388eafc303709b890922dd4a689ee8b1942d24c2121c` |
| A versus B recursive diff | empty |
| static verification | 21,430 local references, broken=0 in each build |

Running single-page builds for `/ru/components/alert/`,
`/ru/components/button/` and `/ru/development/smart-components/` on the accepted
full output retained the same 305-file ledger SHA-256 after every build. Full
and single therefore use one PageBuilder and preserve all unrelated outputs.

Representative final raw HTML SHA-256 values:

- Alert: `c3aa323c61b09595c46e805e3d9336f8ae049d8d843af7e3f32d74d3839b9f60`;
- Button: `160660ae51dbe09da961a19693ed2189151a8029ba0381beb41d6963dc92e`;
- Smart guide: `154a15067c9a4fcbc4f32b57622f9e83198be12ae7c3f6a1f5f20219aa3fc50f`.

## Public parity

Before the public Smart guide wording was synchronized, runtime candidate
`44acc1f` matched the rejected candidate's 304 public files byte-for-byte; only
the diagnostic `.docara/resolved-page-plans.json` changed to record truthful
preset provenance.

The final governance source intentionally adds one explanatory paragraph to
the Smart guide. That changes the two search indexes and their content hash
embedded in all 103 canonical HTML pages. After normalizing only the proven
`docara_v=<search-index-sha256>` query value, all HTML except the intentionally
edited Smart guide is byte-identical. No route, asset, component output or
interaction changed.

## Browser and security

Fresh implementation-bound results are in `browser-results.json`:

- Alert desktop 1440 light and Button mobile 390: console errors/warnings=0,
  overflow=0;
- navigation, search/settings/mobile panels, Markdown tab and Esc focus return
  pass;
- `full`, `compact`, `logo` and `text` branding modes resolve to the expected
  registered views; console/overflow remain zero;
- Framework Alert/Button and the Docara shell Smart components render through
  the same Gateway.

Provider/security tests keep traversal, symlink escape, duplicate ownership,
namespace collision, unknown view/preset/prop and unsafe template/asset paths
fail-closed. No author-controlled PHP/template path was added.

## Structural conclusion and nonclaims

The accepted Goal 1 central source set contains no known Smart component ID and
no `defaultCompositeView` helper. One renderer registry, one
`SmartComponentGateway` and one `PageBuilder` remain. The
`RegionCompositionResolver` shell allowlist is still the explicit Goal 2 debt;
Goal 2 Design Registry/Preview and Goal 3 SDK/MCP are unstarted.

This executor evidence sets Goal 1 to `ready_for_independent_audit`; it does not
mark independent acceptance, release or deployment.
