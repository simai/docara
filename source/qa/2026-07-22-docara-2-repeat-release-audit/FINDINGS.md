# Findings

## F-001 — HIGH — Quick start documents a command that the CLI rejects

`init` accepts no positional arguments, while README, Russian documentation,
CLI reference and canonical Docara skill instruct the user to run
`docara init /path/to/project` or advertise `[path]`.

Reproduction from a clean clone:

```text
php docara init /tmp/docara-target
No arguments expected for "init" command, got "/tmp/docara-target".
exit 1
```

The supported workaround is to enter an empty target directory and invoke the
package executable from there. The public quick start is nevertheless broken.

Required correction: preferably implement an optional target path while
preserving current-directory behaviour, target confinement and empty-directory
checks. Alternatively remove `[path]` everywhere. Add a test executing every
published quick-start command against the real CLI.

## F-002 — HIGH — Composer archive is not reproducible from a fixed SHA

The repository ignores `/composer.lock`, but does not exclude it from
`composer archive`. From the same exact source tree:

- before `composer install`: 413 entries, no `composer.lock`;
- after `composer install`: 414 entries, includes ignored `composer.lock`;
- ZIP checksums differ; the only file-list delta is `composer.lock`.

Required correction: add `/composer.lock` to `archive.exclude` and add an
integration check proving identical archive contents before and after local
dependency installation. Decide separately whether dev-only files such as
`phpunit.xml`, `pint.json`, `.markdownlint.json` and `.env.example` belong in
the distributable package.

## F-003 — HIGH — The active installed Docara skill still teaches Jigsaw/Mix

The canonical skill repository describes Docara 2, although it shares F-001.
The actually installed federation release still instructs Codex to use
`source/docs/en`, `config.php`, `.env`, Jigsaw, npm/yarn and Laravel Mix.
Canonical and installed `SKILL.md` checksums differ. A previous federation
repair attempt stopped on `existing_symlink_mismatch` across the workspace.

Required correction: correct the canonical CLI example, run the supported
federation update/repair path, verify the installed checksum and execute a
Docara 2 smoke task through the installed skill.

## F-004 — HIGH — `https://docara.test/` is a stale legacy site

`/Users/rim/Sites/docara.test` is not the exact candidate. It contains Jigsaw
and Mix-era files and caches, and its `vendor/simai/docara` points to a dirty
legacy repository/branch. ServBay serves `build_production` from this project.

The served build contains 271 HTML files versus 190 in the exact candidate and
retains obsolete migration/development pages. Therefore it cannot be used as
release evidence even though the URL responds successfully.

Required correction: after product corrections, back up the site and replace
it with a clean exact-candidate install/build using an explicit rollback path.
Do not delete the legacy tree until a zero-dependency check and visual smoke
test pass.

## F-005 — MEDIUM — Goal continuation cannot restore the Docara track

The process resolver fails for a normal continuation request because project
memory stores a descriptive sentence as `current_focus` and then treats it as
a track path. `TRACKS.yaml` contains the correct workflow, but the resolver
reports missing track/current workflow.

Required correction: normalize `current_focus` to the stable track identifier
`docara-consolidation` (or correct resolver semantics) and cover goal
continuation with an automated process test.

## F-006 — MEDIUM — CI does not cover the supported PHP boundary or packaging

Composer supports PHP `^8.2`; manual tests pass on 8.2 and 8.4, while CI runs
only 8.3. CI also lacks the archive-state determinism check that would have
caught F-002.

Required correction: use a PHP 8.2/8.4 matrix and add distribution archive
acceptance.

## F-007 — LOW — Residual mixed-language product copy

The Russian site includes phrases such as `Переносимый author path` and
`portable author/build path`. These are not runtime defects but reduce product
polish.

## F-008 — LOW — Release archive contains avoidable development metadata

The package includes several development-only configuration files. Review the
distribution allowlist/exclude list to keep the portable product simpler.
