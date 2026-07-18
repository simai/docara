# Cross-repository pre-acceptance evidence

Date: 2026-07-18
Status: PASS, pending independent exact-candidate acceptance

## Scope

- Standalone repository: `simai/docara`, baseline
  `ba7724ae3d9e2b99388098637b81a35a2646e6a4`.
- Larena package repository: `larena/docara`, baseline
  `8437fdae95fbfe024135fb1c10f8361ebc0e3422`, candidate
  `771f7dd81a9653a7bc3146ef9b1451976a2e70c5`.
- Evidence checkout for the package:
  `/Users/rim/Documents/GitHub/larena-docara`, detached at the exact candidate.

No production, publication, push, release, tag, repository archive or consumer
migration was performed.

## One-source architecture

Standalone Docara owns raw `docara.json`, inherited `_section.json`, page
sidecars, Markdown directives, schemas, merge precedence and
`ResolvedPagePlan`. Larena does not contain a second implementation of those
rules. It imports only the generated
`.docara/resolved-page-plans.json` artifact with a required external SHA-256
receipt, then maps the verified result to Larena layout contracts.

## Exact export parity

The portable fixture was extracted from standalone commit
`a814aa0079182e48cc5c510c3cffc221280b1c5f` into a fresh temporary directory
and built with PHP 8.4. The later accessibility-only renderer change does not
change the portable plan export.

Result:

- schema: `docara.resolved_page_plans.v1`;
- bytes: `97114`;
- SHA-256:
  `ed3c3f7f83ce39e24fe502d8c6f3cf1607b784d0bbdf59c25c826f18032679af`;
- `cmp` against the committed Larena fixture: exit `0`.

## Larena verification

- focused portable import suite: 23 tests / 144 assertions, PASS;
- full `composer quality:gate`: 70 tests / 874 assertions, PASS;
- PHPStan, lint, evidence contract and scope check: PASS;
- complete baseline-to-candidate `git diff --check`: PASS;
- worktree status after the gate: clean.

The adapter requires the full export receipt, stores it as
`sourceExportSha256`, rechecks canonical plan hashes and the exact Framework
lock, and renders normalized component props again through the real Larena
Smart Registry. Negative coverage includes plan, trace, lock, manifest,
component, placeholder, path, URL/output, duplicate identity and receipt
tampering. Compatibility coverage includes virtual `@defaults` provenance and
literal `DOCARA_COMPONENT_...` documentation text.

## Accessibility preflight

The current standalone worktree was built into a disposable local directory
and inspected in a real Chromium browser at desktop and 390 x 844 mobile
viewports.

Verified:

- skip link is the first keyboard target and has a visible 3 px outline;
- Enter on the skip link moves focus to `main#docara-main`;
- no positive `tabindex` exists;
- the active documentation link has `aria-current="page"`;
- the hydrated `sf-button` exposes a real light-DOM `button`, is reachable by
  Tab and has a visible 3 px focus outline;
- the theme toggle is reachable by Tab and keeps focus after Enter and Space;
- system, light and dark modes expose distinct visible text and accessible
  labels describing the next action;
- desktop and mobile have no horizontal overflow;
- browser console: 0 errors, 0 warnings.

This is executor preflight evidence, not the independent Tester or HCS verdict.

## Known nonclaims

- no production or release readiness;
- no public package publication or licensing clearance for the exact Smart
  source projection;
- no archive/delete readiness for `docara-template` or `docara-mix`;
- no Larena database import/apply, audit mutation or round-trip export claim.
