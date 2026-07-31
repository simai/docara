# Docara media landing acceptance

Date: 2026-07-23
Branch: `codex/docara-consolidation`
Candidate: current working tree
Scope: media-rich landing, typed media components, generated catalog and local
`docara.test` publication.

## Outcome

- `/ru/landing/` is a full-width landing assembled from registered Markdown
  components and Simai Framework utilities;
- ordinary Markdown remains bounded by the selected content width;
- `hero`, `features`, `logos`, `showcase`, `columns` and `promo` render
  responsive media without author-defined classes or dimensions;
- the generated component catalog contains executable `docara.showcase` and
  `docara.promo` examples;
- publisher CSS and runtime URLs carry SHA-256 cache versions, so a newly
  published shell cannot be hidden by a stale browser cache.

## Automated verification

- full PHPUnit: PASS, 319 tests and 4559 assertions;
- focused publisher/static suite: PASS, 56 tests and 1090 assertions;
- Pint: PASS;
- Composer strict validation: PASS;
- `git diff --check`: PASS;
- production build: PASS, 90 canonical pages;
- static verifier: PASS, 198 HTML pages, 10,908 local references, 0 broken;
- deployed-tree comparison: PASS.

Composer emitted only deprecation notices from the ServBay Composer PHAR under
PHP 8.4; `composer.json` itself is valid.

## Browser acceptance

- desktop `1440 × 900`, dark/system theme: PASS;
- desktop light theme: PASS;
- mobile `390 × 844`, light theme: PASS;
- mobile `390 × 844`, dark theme: PASS;
- hero becomes one column on mobile and both actions remain usable;
- desktop hero uses two equal columns and its image no longer overflows;
- generated `docara.showcase` example: PASS;
- generated `docara.promo` example: PASS;
- final browser tab: `https://docara.test/ru/landing/`.

## Publication and rollback

- action gate: PASS;
- action-gate evidence:
  `source/output/action-gates/action-gate-report-20260723204016.json`;
- exact local destination:
  `/Users/rim/Sites/docara.test/build_production`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/20260723-235042`;
- deployed manifest SHA-256:
  `76659c7669085cf40385071bd43dec43c6ec34fa20db36170ae29cb65aeb45db`.

## Verdict

PASS for the local candidate and local demonstration site. No commit, push,
merge, tag, package publication or public/production release was performed.
