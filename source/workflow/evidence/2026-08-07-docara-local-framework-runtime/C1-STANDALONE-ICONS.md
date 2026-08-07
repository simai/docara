# C1 — standalone icon font correction

Date: 2026-08-07

State: `local_framework_runtime_ready_for_independent_audit`

Product candidate: `f07572fb15a5e2a71f3ab3e9207b4c9d54336b06`

## Reproduced defect and correction

On the deployed `/ru/components/button/` page, custom `<sf-icon>` hosts used the
local font, while direct `<i class="sf-icon">arrow_forward</i>` hosts requested
the unregistered `Material Symbols Outlined` family. The host width was 24px,
but its rendered ligature text occupied 84px and overlapped the button label.

The correction registers the existing exact local WOFF2 under the canonical
Framework family and applies it generically to admitted outlined `.sf-icon`
hosts. Rounded and sharp families remain excluded because this projection owns
only the outlined font. The readiness runtime uses the same selector/family.
No external owner artifact, template, renderer, Gateway or PageBuilder changed.

Permanent test: `tests/Unit/FrameworkTypographyProjectionTest.php` first failed
against the child-only `Docara Material Symbols` rule and then passed against
the generic canonical projection.

Full PHPUnit on ServBay PHP 8.4.20: 510 tests / 11,534 assertions, PASS.
Focused projection: 5 tests / 295 assertions, PASS. Pint, Composer strict,
PHP lint and candidate-range diff checks pass; Composer prints only its own
PHP 8.4 deprecation notices.

## Browser proof

Clean exact-candidate root:
`/tmp/docara-iconfix-b.4w7bJE/build_iconfix-b`.

On `/ru/components/button/`:

- both direct `arrow_forward` hosts: family `Material Symbols Outlined`, width
  24px, scrollWidth 24px, loaded=true;
- custom-element icon children use the same family; visible children have
  width/scrollWidth 24px;
- the canonical font face is loaded;
- document overflow=false; console errors=0; console warnings=0.

The former 84px direct ligature is the RED baseline, not an accepted state.

## Exact builds and static verification

- full A: `/tmp/docara-iconfix-a.6Fj0cg/build_iconfix-a`;
- full B: `/tmp/docara-iconfix-b.4w7bJE/build_iconfix-b`;
- representative single: `/ru/components/button/` rebuilt into full A;
- all three: 649 files, canonical digest
  `0495614f1ca65c6c2ff5a498ea5897074bfb3568fd145930fdf6308401f472d4`;
- each full static verifier: 266 HTML, 36,128 local references, broken=[].

Canonical digest formula: path-sort every regular file, concatenate
`<file-sha256><two spaces><relative-path><newline>`, then SHA-256 the complete
byte string.

## Package and fresh consumer

Both builds used exact arguments
`--revision=f07572fb15a5e2a71f3ab3e9207b4c9d54336b06`,
`--version=2.0.0-alpha1`, `--tag=v2.0.0-alpha1-local-icons` (evidence
parameter only; no Git tag exists):

- ZIP SHA-256:
  `6007983971e16fa606f74d55634f77b6d0f6396d524418b8f34573637c0e857f`;
- manifest SHA-256:
  `d2b89bf9c74e93500aa95dc8e94910daf3357150e8557ce3b6f061d2b67f1761`;
- checksum file SHA-256:
  `e5da9888e2a051bb300eba0bf23bd2a173f0f9abf80fadd72eb3eda8e6ac89a5`;
- file count: 997; both repository verifiers PASS.

Fresh dist consumer `/tmp/docara-iconfix-consumer.SnpkYV` has no package `.git`
or `node_modules`; init, doctor, 39-page full build, representative single and
static verification pass. Its full/single tree has 454 files at digest
`92e3336f7485fc04cd662d8f76e4cf80a6f0bebea6dd25e3e399f13c1ab9279e`;
static reports 78 HTML / 4,087 references / broken=[].

## Validation site and rollback

Action preflight:
`source/output/action-gates/action-gate-report-20260807142342.json`.

- previous active digest:
  `4b35a9211184aab6b267dfd8a523eddc3bf8bde9ca69c37ccc6acbce594512b2`;
- corrected candidate digest:
  `0495614f1ca65c6c2ff5a498ea5897074bfb3568fd145930fdf6308401f472d4`;
- active basename: `docara-new.test`;
- rollback backup basename:
  `.docara-new.test-backup-before-local-icons-f07572f-20260807`;
- preserved candidate basename on rollback:
  `.docara-new.test-candidate-local-icons-f07572f-20260807`.

Atomic preflight and cutover both PASS. The active tree independently reports
649 files at the corrected digest; static verification is 266 HTML / 36,128
references / broken=[] and the HTTPS route sweep is 266/266 status 200.
The deployed browser reproduces the clean-build glyph metrics (direct and
custom hosts use the loaded local canonical family, direct width/scrollWidth
24/24), has no overflow and emits zero console warnings/errors. All 98 observed
page/runtime requests are same-origin HTTPS responses with status 200,
including the exact local WOFF2.

Rollback uses `scripts/atomic-static-cutover.php rollback` with those exact
basenames and the two digests. `docara.test` is outside this operation.

## Nonclaims

This is an authorized local validation-site correction, not a release. No
merge, push, tag, publication, production deployment or external owner write
occurred. Independent read-only audit remains the only next action.
