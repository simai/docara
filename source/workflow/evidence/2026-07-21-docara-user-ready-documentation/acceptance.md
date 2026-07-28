# Acceptance: user-ready documentation for declarative Docara

Date: 2026-07-21
Verdict: `PASS`
Scope: documentation, executable examples, local publication and reader routes

## Requirement matrix

| Requirement | Evidence | Verdict |
| --- | --- | --- |
| Remove stale and contradictory instructions | retired candidate and one-language claim absent from reader surfaces | PASS |
| Current installation path | remote branch resolves to immutable `0f10afd…`; README/start/CLI pin it | PASS |
| Executable JSON examples | every JSON fence parses; key files pass product schemas | PASS |
| Executable shell examples | shell fences contain no syntax placeholders; reference notation uses `text` | PASS |
| Safe update guide | `build/update.md` covers backup, Composer, non-overwriting init, diff, verify and rollback | PASS |
| Branding guide | full site config, asset rules, inheritance and theme verification | PASS |
| Multilingual tutorial | documented `ru/en/ar` project builds all roots and RTL output | PASS |
| Language-pack guide | partial `fr-CA` pack passes schema and resolves missing message through `en` | PASS |
| Page JSON optional | start and project-files explicitly state Markdown builds without a sidecar | PASS |
| Composition developer path | Layout, Section, Block, View Tree and Smart registration surfaces listed | PASS |
| Docs map and changelog | entrypoints, `docs/README.md` and `docs/changes.md` updated | PASS |
| Local site publication | source and target digest match; HTTP smoke passes ten reader routes | PASS |
| Browser acceptance | desktop/mobile layout, active nav, mobile menu, search and console checked | PASS |

## Automated verification

- exact final PHPUnit: `607 tests, 5254 assertions`, PASS;
- changed PHP files Pint: PASS;
- `git diff --check`: PASS;
- authored inventory: 64 Markdown pages, exactly one H1 each;
- documentation build: 164 HTML pages;
- static verifier: 18,768 local references, zero broken;
- duplicate builds `build_a` and `build_b`: byte-identical;
- HTTP routes: `/`, `/start/`, three new authoring guides, update,
  composition extensions, starter mirror, Vite assets and CLI all return 200;
- served tree digest:
  `98d419a958e40893bdf3e8e6fc0063a8e49026d7042c51d799290e3e0001984c`.

## Browser evidence

- desktop `1440x900`: multilingual guide has active navigation, sidebar,
  outline, four code examples and no page-level horizontal overflow;
- mobile `390x844`: sidebar and outline columns collapse, hamburger dialog
  opens, active quick-start item remains visible, code scroll stays contained;
- search query `брендирование` returns the new branding guide;
- final authoring entrypoint exposes all three new author routes;
- browser console contains no warnings or errors.

## Publication and rollback

Published target:
`/Users/rim/Sites/docara.test/build_production`

Rollback copy:
`/Users/rim/Sites/docara.test/.docara-backups/build_production-pre-final-docs-20260721-133121`

The source and served directory manifests have the same aggregate digest.

## Bounded baseline observations

- `/opt/homebrew/bin/php` is locally broken because it still links ICU 73;
  verification used ServBay PHP 8.4.20, the active local-site runtime.
- Repository-wide Pint reports existing formatting debt in three untouched
  runtime files: `PortableRedirectPublisher.php`, `PortableSiteBuilder.php`
  and `LanguagePack.php`. All PHP files changed by this goal pass Pint.

Neither observation weakens the documentation acceptance and neither was
silently modified outside this goal.

## Nonclaims

This PASS does not claim a stable Composer release, public release,
production readiness, all Simai Framework components ready or a project-level
plugin API for arbitrary executable composition definitions.
