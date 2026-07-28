# Batch 1 core/starter evidence

Date: 2026-07-18
Baseline: `f51debe3e1def82d2dcf611bf820c4517cd65f8f`
Candidate: historical Batch 1 checkpoint; final candidate is recorded in the
exact-candidate verification evidence
Scope owner: `docara` + `dev`

## Implemented

- Added strict `navigation.order` validation as a non-negative 32-bit integer.
- Applied inherited page order during portable navigation sorting. Explicit
  `navigation.order` values sort numerically, pages with no value use a
  separate missing state and follow every explicit value, then ties use the
  home URL and URL.
- Added starter examples proving a page-sidecar order, a section-sidecar order,
  and a hidden ordered landing page.
- Added one canonical template mirror generator. It reads all site payload only
  from `stubs/portable`, adds generated README/.gitignore metadata, and writes a
  deterministic SHA-256 manifest.
- Added separate PHP export and drift-verification entrypoints. Export requires
  an exact clean commit with exactly one SemVer release tag, refuses a
  non-empty destination, and verification fails on missing, changed,
  unexpected, unsafe-mode, or linked files.
- The generated mirror contains no `package.json`, Vite config, webpack config,
  npm, or Yarn project surface. End-user instructions use Composer and PHP only.

## Historical checkpoint verification

The following values describe the bounded Batch 1 checkpoint, not the final
candidate. Final counts and hashes are intentionally centralized in
`exact-candidate-verification.md`.

```text
/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/phpunit --colors=never tests/Unit/PortableConfigurationTest.php tests/PortableSiteBuilderTest.php tests/PortableInitCommandTest.php tests/Unit/TemplateMirrorTest.php
PASS: 42 tests, 334 assertions

/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/pint --test <changed PHP files>
PASS

git diff --check
PASS

php scripts/export-template.php <empty temp directory> <exact released SHA>
PASS: 13 generated files

php scripts/verify-template.php <generated temp directory>
PASS

Generated manifest payload records: 12
Generated Node project files: 0
```

## Human-Centered Simplicity engineering review

| Changed surface | Outcome or invariant | Decision | Evidence |
| --- | --- | --- | --- |
| `navigation.order` schema field | Authors control navigation without a second menu registry | retain | positive and negative schema tests |
| Portable navigation sort | The declared order changes real output deterministically | retain | inherited/overridden/tie sorting test |
| Starter order examples | Fresh projects explain the contract through working data | retain | portable init and build tests |
| `TemplateMirror` | `docara` is the single starter source; external template can be generated | retain | export, hash manifest, and drift tests |
| Export/verify PHP wrappers | Automation needs stable, Node-free entrypoints | retain | subprocess tests and direct smoke |
| Composer script aliases | Duplicate command/configuration surface | remove | direct PHP scripts remain sufficient |

Simplest complete alternative retained: copy `stubs/portable` directly and add
only generated repository metadata. No second template directory, secondary
starter registry, Node toolchain, or overwrite/update mode was introduced.

## Boundaries

- `BasicScaffoldBuilder` still initializes portable projects only from
  `stubs/portable`.
- No external repository, documentation corpus, Framework component runtime,
  release, tag, or remote mirror was changed.
- External `docara-template` synchronization remains blocked until the final
  Docara candidate is committed, published with exactly one release tag, and
  Composer resolves that release to the same SHA.
