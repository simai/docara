# B5 exact-candidate acceptance

Date: 2026-07-22
Verdict: PASS
Candidate: `c537e17f61f890fdbf5635c83ee642109bf730a4`

## Acceptance boundary

The candidate was tested from a clean detached checkout and from exported
package trees. Executor worktree dependencies and generated output were not
used as acceptance inputs. No public push, merge, tag, package publication or
production deployment was performed.

## Exact checkout

- Composer clean install: PASS.
- Composer strict validation: PASS. The ServBay Composer PHAR emitted upstream
  PHP 8.4 deprecation notices but exited successfully.
- Pint: PASS.
- PHPUnit: `307 tests, 4149 assertions`, PASS.
- Full change range `git diff --check`: PASS.
- Five JSON language packs parsed successfully.
- Federation process checker: PASS with no blockers.
- Manual secret hygiene: no tracked `.env`, private-key-like path or private
  key marker.

## Source archive

- `git archive` excluded tests, workflow, output, Git metadata and local state.
- Runtime dependencies installed with `--no-dev`.
- Fresh `init`, production `build` and `verify-static`: PASS.
- Static verification: 58 HTML pages, 820 local references, zero broken.
- Two production builds were byte-identical.
- Build digest:
  `74fc9af0d0c87527d8da172906381be3a407590d85fd640dd88d39eb5f713b29`.
- `init --update` preservation was covered by the exact test suite and the
  earlier isolated product smoke.

## Composer distribution

- Composer ZIP contains 414 entries.
- Archive SHA-256:
  `6ac3a6b3147fdd603655c68df6bda53d844b32c3a6238b2f81ade021db80e711`.
- No `.env`, Git/workflow/QA metadata, test tree, vendor tree, caches or
  generated build directories were present.
- Runtime dependencies installed with `--no-dev`.
- Fresh `init`, production `build`, `verify-static` and deterministic rebuild:
  PASS.
- Result digest equals the source archive digest.

## Documentation

- Exact candidate built 86 authored pages into 190 HTML files.
- Static verifier checked 10,480 local references with zero broken references.
- Two builds were byte-identical.
- Documentation digest:
  `1acd0573e0e05adc924cfb9b845eb8785296f5e7b50179e75454b2e29c64a053`.

## Browser acceptance

The exact Composer distribution was served locally and exercised with a real
Chromium browser.

- `/` redirected deterministically to `/ru/`.
- Desktop 1440x1000 rendered the header, documentation navigation, content and
  reading controls with accessible landmarks.
- Search opened as a dialog, focused the searchbox, returned one result for
  `руковод` and exposed two semantic `mark` highlights.
- Escape closed the dialog and restored the page.
- Mobile 390x844 replaced the desktop navigation with the accessible
  `Открыть разделы документации` control while preserving content and page
  navigation.
- Browser console: zero errors, zero warnings.

## Simplicity and legacy boundary

- Current product source: 131 files.
- Current starter: 20 files, portable-only.
- Public CLI: `init`, `build`, `serve`, `verify-static` plus Symfony help/list
  facilities.
- Jigsaw/Mix runtime, legacy starter snapshots, transition publisher,
  declarative preview output and template mirror are absent.
- Remaining references to `config.php` and `init --portable` are negative
  migration/update-safety assertions; current Smart templates legitimately use
  Blade as their view technology.
- Obsolete preview translations were removed from all packaged language packs.

## Canonical skill and federation

- Docara skill commits: `b839bd5` and `0aa77d0`.
- Skill smoke and repository-local Mirai Graph contract gate: PASS.
- Skill graph sync: PASS.
- Federation fast verification remains partial only because the installed
  environment has a pre-existing symlink mismatch across all 25 installed
  skills. The canonical Docara skill and this product candidate are clean; no
  installed federation state was changed in this workflow.
- The generic repository-hygiene checker recognises Docara but still classifies
  the two tracked `source/memory/**/CURRENT.yaml` continuation files as
  Larena-only. This is a central checker classification gap, not a product or
  package finding. The files are intentionally retained; the exact package
  excludes all `source/` content and the explicit secret checks pass.

## Readiness matrix

| Surface | Verdict |
| --- | --- |
| Local source candidate | PASS |
| Git source archive | PASS |
| Composer distribution archive | PASS |
| Portable fresh install/build/update/verify | PASS |
| Deterministic output | PASS |
| Documentation build | PASS |
| Desktop/mobile browser smoke | PASS |
| Canonical Docara skill alignment | PASS |
| Public release | NOT PERFORMED |
| Production deployment/readiness | NOT CLAIMED |

The candidate is ready for a separate controlled release workflow. It is not a
production-deployment claim.
