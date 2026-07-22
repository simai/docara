# Workflow: Docara scrollbar side correction

Date: 2026-07-22
Status: review ready
Baseline: `546f645`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## Outcome

- Left navigation scrollbar is one Framework pixel token from its divider.
- The contents scrollbar is on the physical right edge of the contents column.
- The active contents marker remains on the physical left divider.
- Native scrollbar width is not duplicated as a visual gap.

## Implementation decision

- Use `var(--sf-px)` rather than a custom value for the navigation gap.
- Keep the contents scroll container `direction:ltr` so its native scrollbar is
  consistently on the right. Restore the document direction on its child.
- Use a physical `left` marker because the requested layout has fixed physical
  roles: active marker left, scrollbar right.
- Remove runtime scrollbar-width compensation from `docara.toc`; it is no
  longer needed when the scrollbar is not between the marker and content.

## Graph gap

The federation route helper attempted an install repair and stopped on existing
skill-symlink mismatches instead of returning a task route. No skill install or
symlink was changed. Raw Docara, SF5 and UX owner sources plus this repo-local
workflow are used as the safe fallback.

## Readiness

- source and focused tests: PASS, `37 tests`, `810 assertions`;
- full PHPUnit: PASS, `623 tests`, `5567 assertions`;
- exact documentation build and static verification: PASS, `271` HTML pages,
  `20512` local references, no broken references;
- desktop geometry with overflowing navigation and contents: PASS;
- mobile regression and console: PASS;
- local publication and rollback: PASS.

## Measured result

- Menu gap from the inner divider edge: `1 px` (`--sf-px`).
- Menu outer distance: `2 px`, consisting of the divider itself plus the
  requested `1 px` gap.
- Chrome native scrollbar width: `14 px`; it is no longer duplicated as gap.
- Contents scrollbar: physical right edge.
- Active contents marker: physical left divider, `0 px` displacement.
- Mobile horizontal overflow: `0 px`.

The verified build is served at `https://docara.test/`. Rollback copy:
`/Users/rim/Sites/docara.test/.docara-backups/scrollbar-side-20260722-132315/build_production.previous`.

## Exclusions

- no content, hierarchy, locale, URL or menu behavior change;
- no upstream Framework source change;
- no public push, merge, tag, release or production-readiness claim.
