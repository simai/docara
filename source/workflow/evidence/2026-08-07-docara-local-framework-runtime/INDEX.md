# Local SIMAI Framework runtime — exact evidence

Date: 2026-08-07

State: `local_framework_runtime_ready_for_independent_audit`

Product candidate: `08cf9eb6b9dbd0175b87854dc9ec9652ebccc773`

## Immutable owner projection

- owner source: clean `simai/ui@d1daa951dd08b94a9f209fd9f31a78d2b3779563`;
- source tree SHA-256: `8f28b6c5a09173f5502836506c64d2e0aa66c2099a4f95a1f26f47145981f7fe`;
- projected runtime: 117 files, source fidelity 117/117, missing=0,
  mismatched=0;
- packet SHA-256: `790b8014c4c1a0853e6a0650f30e0b4f33ab3b428f878b0fa010faf0c3f449c0`;
- manifest SHA-256: `8c917f69a678df084260ded24c5e39e78aaa4fc12c317bf98afaf11ee2a29a8e`.

The closure contains the exact Core/component/utility assets used by the
current Docara build, Inter, Material Symbols, Framework bootstrap, lazy theme
utility and all eight code-highlight languages authored by the project. The
manifest is verified before publication; changed, missing, symlinked,
hardlinked, colliding or escaping paths fail closed.

## Repository verification

- full PHPUnit: 510 tests / 11,513 assertions, PASS;
- Pint, Composer strict, PHP lint, JSON and candidate-range `git diff --check`:
  PASS (Composer prints tool-owned PHP 8.4 deprecation notices only);
- offline browser against a clean build with every external HTTPS request
  blocked: zero warnings/errors, zero external runtime requests, `sf-icon`
  defined, search/settings focus return, blur=none, overflow=false.

## Deterministic public builds

Named roots:

- `/tmp/docara-lfr-exact-a.ry3EVX/build_localruntime-a`;
- `/tmp/docara-lfr-exact-b.Sl4KoG/build_localruntime-b`.

Both full roots and representative single `/ru/components/button/` contain 649
files and share canonical path-sorted tree digest
`4b35a9211184aab6b267dfd8a523eddc3bf8bde9ca69c37ccc6acbce594512b2`.
Static verification for each full build reports 266 HTML, 36,128 local
references and `broken=[]`.

Canonical digest formula: sort by relative path, concatenate
`<file-sha256><two spaces><relative-path><newline>`, then SHA-256 the complete
byte string.

## Package and consumers

Two exact builds used `--revision=08cf9eb6b9dbd0175b87854dc9ec9652ebccc773`,
`--version=2.0.0-alpha1` and
`--tag=v2.0.0-alpha1-local-runtime` (evidence label only; no tag was created):

- ZIP SHA-256: `79a56ec4ea16d44dc6eb59e0df1b552897f156880b163c3e83d549838bc2db68`;
- manifest SHA-256: `f92cc2866ff35e44d0c2c6307eff0761cbc3c5bf95bb7c0205a392ab386b5a5c`;
- checksum file SHA-256: `945f8d944d90c8271baece2e63ee1976bf420eafb2e331ead5ffb8855caaa0d8`;
- file count: 997; both verifiers PASS.

Two fresh Composer consumers used identical lock SHA-256
`9257554e123e367044a91fd1cfadff45c18f69cd47cb19bfc7c6f2059739a3f8`.
Both pass init, doctor, full, selected `/ru/` build and static verification;
their 454-file trees are byte-identical at
`b46bbae54bba9c4764f62231e1fba9c75739870cc182de9870cb0435e2334971`.
Static reports 78 HTML / 4,087 references / broken=0. Package `.git` and
`node_modules` are absent.

## Authorized local validation cutover

The SIMAI action preflight passed and recorded
`source/output/action-gates/action-gate-report-20260807110841.json`.

- previous active digest: `e35f077cc1511b88270a28a8e616726543cbc67b5bc794b1a56e461364bc7934`;
- new active digest: `4b35a9211184aab6b267dfd8a523eddc3bf8bde9ca69c37ccc6acbce594512b2`;
- backup: `/Users/rim/Sites/.docara-new.test-backup-before-local-runtime-08cf9eb-20260807`;
- active: `/Users/rim/Sites/docara-new.test`;
- HTTPS smoke for every 266 generated HTML path: 266/266 return 200;
- active static verification: 266 HTML / 36,128 references / broken=0;
- real-browser `sf-icon` child width 24px, search/settings open and return
  focus on Escape, both overlays have backdrop blur `none`, overflow=false,
  external runtime requests=[], console warnings/errors=0.

Exact rollback keeps the current candidate under the former candidate name and
restores the backup using `scripts/atomic-static-cutover.php rollback` with
the two digests above. No write was made to `docara.test`.

## Scope and nonclaims

This is a local validation deployment, not a release. No merge, push, tag,
publication or production deployment occurred. The projection does not declare
unadmitted raw Framework components supported and is not a second runtime.

See also [human-centered simplicity](HUMAN-CENTERED-SIMPLICITY.md) and
[tester verdict](TESTER-VERDICT.md).
