# Acceptance: Docara component reference and automatic metadata

Date: 2026-07-28
Verdict: PASS
Candidate baseline: `ecfc8b72f34a020b1f7374e11eb5b33c0838aabe` plus the bounded dirty-worktree implementation recorded by this workflow.

## Product result

- Effective catalog: 33 records.
- Supported detail pages: 28.
- Explicit gap pages: 5.
- Every supported record has one generated localized route and menu item.
- Detail pages contain purpose, authoring syntax, parameters, variants,
  live examples, copy-ready calls and derived package/source/Git metadata.
- Public authoring is expressed as Markdown plus Docara components; Framework
  identifiers remain implementation provenance.

## Automated verification

- PHPUnit: 333 tests, 6,531 assertions, PASS.
- Production build: 107 canonical pages.
- Static output: 232 HTML documents.
- Local references checked: 20,646.
- Broken references: 0.
- Reproducibility: two consecutive builds, 297 files each, directory diff 0.
- `git diff --check`: PASS.
- Built tree and deployed tree: directory diff 0.

## Browser acceptance

- Representative catalog and `docara.alert` detail page opened successfully.
- Light and dark themes: PASS.
- Desktop and 390 px mobile: PASS.
- Body visibility fail-safe: computed opacity 1.
- Left navigation and right outline collapse on mobile.
- Horizontal document overflow: false after break opportunities were added to
  immutable source and Git revision values.

## Local deployment

- URL: `https://docara.test/ru/components/catalog/`.
- Backup: `/Users/rim/Sites/docara.test/.docara-backups/component-reference-20260728-175407`.
- Deployment is local test-site publication only.

## Nonclaims

- No default-branch merge.
- No tag or package release.
- No public or production deployment.
- No production-readiness claim.
