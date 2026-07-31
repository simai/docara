# Docara component detail human-centered redesign

Date: 2026-07-28
Status: completed
Workflow ID: `2026-07-28-docara-component-detail-human-centered-redesign`
Track: docara-consolidation

## Goal

Turn every public component page into concise human documentation instead of a
projection of the internal registry.

## Product contract

- public routes use short component names such as `/components/alert/`;
- Framework-owned `ui.*` records remain internal and are not duplicated in the
  author-facing Docara catalogue;
- a page contains a title, one useful explanation, a combined result-and-code
  example, readable parameter descriptions and only non-empty important notes;
- machine markers, hashes, repository paths, states, registry families and
  provenance remain in receipts and tests, not in the public article;
- parameter documentation is a vertical definition list rather than a wide
  registry table;
- component examples show copy-ready authoring syntax, without verification
  markers or catalogue-only headings;
- article rhythm comes from Framework typography; authored content gap is a
  declared layout setting and is `0` for the bundled Docara documentation;
- Alert icons use the registered Framework icon component.

## Evidence and boundaries

- reference reviewed: `https://retype.com/components/badge/`;
- target: `https://docara.test/ru/components/alert/` and all sibling pages;
- evidence: `source/workflow/evidence/2026-07-28-docara-component-detail-human-centered-redesign/`;
- the installed Docara skill was explicitly excluded by the user as stale;
  live repository code and schemas are the source of truth;
- no merge, tag, release or public deployment is included.

## Acceptance

- alert icons are visible in light and dark themes;
- no public detail page contains the removed technical sections;
- all public catalogue links resolve to short routes exactly once;
- result and code remain readable at desktop and mobile widths;
- parameters do not overflow horizontally;
- PHPUnit, deterministic build, static verification and browser checks pass;
- the verified build is installed on `docara.test`.

## Result

PASS. Public component pages now use short routes, concise result-and-code
examples, vertical parameter descriptions and only meaningful notes. Alert
icons render through the registered Framework icon component. The bundled
documentation resolves authored content gap to `0`. Full PHPUnit, deterministic
build, static verification and desktop/mobile browser checks pass. The exact
verification record is stored in
`source/workflow/evidence/2026-07-28-docara-component-detail-human-centered-redesign/verification.md`.
