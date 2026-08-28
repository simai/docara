# Workflow: restore native Markdown margin collapsing

Date: 2026-08-27
Status: completed
Owner: Docara / Development

## Goal

Restore predictable vertical rhythm in ordinary Docara documentation by keeping
the article in normal block flow when `layout.content.gap` is `0`, without
breaking the explicit non-zero vertical-stack mode.

## Done When

- default documentation and landing articles no longer render as flex columns;
- adjacent Markdown block margins collapse according to CSS block-flow rules;
- `layout.content.gap=1..8` still opts into the existing flex stack and exact
  Framework gap utility;
- focused tests, Docara documentation build, ui-doc build, static verification
  and browser evidence pass;
- graph, project memory and project technology are synchronized;
- no commit, push, tag, release or public deployment occurs.

## Result

- `resources/publisher/templates/page.php` now emits a normal block article for
  the default zero gap and conditionally emits `flex flex-col gap-N` only for an
  explicit non-zero gap.
- A regression test covers the non-zero opt-in, while the main portable-site
  test asserts that default docs and landing output contain no prose flex
  container.
- Public configuration documentation now states the block-flow and opt-in stack
  semantics; the unreleased changelog records the bug fix.
- On the affected ui-doc page, the article computes to `display:block`. The
  preceding paragraph has `12px` bottom margin, the following H2 has `24px` top
  margin, and the measured physical gap is `24px`, proving collapse rather than
  the previous `36px` sum.

## Verification

- PHP syntax: pass for the publisher template and modified test.
- Focused portable-site tests: 2 tests, 641 assertions, pass.
- Framework-native surface tests: 8 tests, 72 assertions, pass.
- Docara documentation: 127 source pages built; 261 HTML files and 32,983 local
  references verified; zero broken.
- ui-doc: 912 source pages built; 1,665 HTML files and 356,272 local references
  verified; zero broken.
- HTTPS/browser smoke on the affected ui-doc route: pass.

The existing PHP configuration warning about line 145 in the ServBay PHP ini
remains external to this change; all commands exited successfully.

## Boundaries

- Existing unrelated pager changes in the same working tree were preserved.
- Translation tracking remains report-only and was not remediated.
- No Git publication, package release or public deployment was performed.
