# C2 — complete local Material Symbols families

Date: 2026-08-07

State: `local_framework_runtime_ready_for_independent_audit`

Product candidate: `ad147f32ec9854e5bb97ea635b349b3ce803ed43`

## Reproduced defect and source decision

The C1 correction made the accepted Outlined family work for direct and custom
icon hosts, but `/ru/components/icon/` also demonstrates Rounded and Sharp.
Their CSS requested `Material Symbols Rounded` and `Material Symbols Sharp`
without publishing those font files. Browsers therefore rendered the ligature
strings: representative 16/20/24px hosts had scroll widths 32/41/73px.

The current Framework owner tree only contains legacy static Rounded/Sharp OTF
fonts. Aliasing those files would falsely claim the variable FILL/weight
contract used by the page. Docara therefore admits an exact local projection of
the official Material Symbols variable fonts from
`google/material-design-icons@50f0603134ce7b70b2d71b686cc13e8b57ccb74c`.
The projection is Docara-owned and source-pinned; no external Framework owner
repository or generated distribution was edited.

Exact projection:

- Rounded WOFF2 SHA-256:
  `3500043e8929d5140f34dff8f8687e1dd5fda3a33fff20bfcc96ecd0b2f99518`;
- Sharp WOFF2 SHA-256:
  `07120b2acb649946dd277ffeaefc745c576ef5bee05b4f2f07bd1f98b31fb6d8`;
- Apache-2.0 license SHA-256:
  `58d1e17ffe5109a7ae296caafcadfdbe6a7d176f0bc4ab01e12a689b0499d8bd`;
- path-sorted three-file packet SHA-256:
  `40e0591f40c190eddcabe6ad9c8ac385cb7501c54897fab5c993b7705ff0bd34`.

`FrameworkLock`, its JSON schema and `FrameworkManifestRepository` verify the
provider, revision, ordered paths, regular-file policy and every hash before
publication. `FrameworkAssetPlanner` emits the local font faces and readiness
checks for Outlined, Rounded and Sharp through the existing asset plan.

## Verification

- focused Framework typography projection: 6 tests / 310 assertions, PASS;
- full PHPUnit from a clean exact-candidate clone: 511 tests / 11,521
  assertions, one expected cross-host skip without an owner checkout, PASS;
- Pint, Composer strict, relevant PHP lint, lock/schema JSON parsing and
  project-context: PASS; Composer reports only tool-owned PHP 8.4 deprecations.

Permanent regression checks exact provenance/hashes, generated `@font-face`
rules and selectors, all-family readiness, packet fingerprinting and
fail-closed hash corruption.

## Exact builds and static verification

Clean exact-candidate clone: `/tmp/docara-icon-family-clone.Pu9Mcd`.

- full A: `build_icon-families-final-a`;
- full B: `build_icon-families-final-b`;
- representative single `/ru/components/icon/` rebuilt into full A;
- all three contain 655 files and share canonical digest
  `b6e0d9a7578b0af004a8fd1950ba26eab41010c561b9bcb938583fa7e74ab5ca`;
- each full static verifier reports 266 HTML, 36,128 local references and
  `broken=[]`.

Canonical digest formula: path-sort every regular file, concatenate
`<file-sha256><two spaces><relative-path><newline>`, then SHA-256 the complete
byte string.

## Deterministic package

Both package builds used:

```text
--revision=ad147f32ec9854e5bb97ea635b349b3ce803ed43
--version=2.0.0-alpha1
--tag=v2.0.0-alpha1-local-icon-families
```

The tag is only deterministic package input; no Git tag was created.

- ZIP SHA-256:
  `1cfd3acf4858e1d334b5eb8d6ee4a9bcabbd1f97e4ed65c7f103cb8e689afd4b`;
- release-manifest SHA-256:
  `19b6f321f863843cbb010c636965af159621332b740d6203e3320e0830e12380`;
- checksum-file SHA-256:
  `603fea8a4372aeba37bf67cc90ef4590720a0cf5cccc5f45b7a1383f3020965f`;
- file count: 1,000;
- both repository package verifiers: PASS.

## Validation site and browser proof

The SIMAI action preflight passed at
`source/output/action-gates/action-gate-report-20260807150926.json`.

- previous active digest:
  `0495614f1ca65c6c2ff5a498ea5897074bfb3568fd145930fdf6308401f472d4`;
- corrected active digest:
  `b6e0d9a7578b0af004a8fd1950ba26eab41010c561b9bcb938583fa7e74ab5ca`;
- active root: `/Users/rim/Sites/docara-new.test`;
- rollback backup:
  `/Users/rim/Sites/.docara-new.test-backup-before-icon-families-ad147f3-20260807`.

Atomic cutover and active-tree verification pass. Active static verification is
266 HTML / 36,128 references / broken=0. Both exact WOFF2 URLs and the Icon and
Button pages return HTTPS 200 with correct MIME types.

On `/ru/components/icon/`, the representative Rounded, Sharp and filled
Rounded icons have width/scrollWidth 16/16, 20/20 and 24/24 respectively; the
correct families are loaded and FILL=1 remains effective. On
`/ru/components/button/`, direct `arrow_forward` is 24/24. Both pages have zero
console errors/warnings, zero failed or external requests and no horizontal
overflow.

Rollback uses `scripts/atomic-static-cutover.php rollback` with the exact
active/backup names and the two digests above. `docara.test` was not changed.

## Nonclaims

This is an authorized local validation-site correction, not a Framework or
Docara release. No merge, push, tag, publication, production deployment or
external owner write occurred. It does not claim offline support for unrelated
Framework surfaces or admit additional components.
