# Docara code-block visual contract

Date: 2026-07-26
Status: accepted_local_consumer
Framework workflow:
`/Users/rim/Documents/GitHub/ui-control/source/workflow/2026-07-26-highlight-compact-code-surface.md`

## Result

Docara consumes the canonical SIMAI Framework Highlight component without a
local renderer or CSS override. The code header uses Framework spacing tokens
`1/3` vertically and `1` horizontally, the language label and copy action use
size `1/2`, the copy action is an accessible icon-only button, and the inner
Highlight surface is transparent.

## Immutable consumer tuple

- `ui-loader@06b48dd8090ece81657d98570e47eaf0cb9fe938`;
- `ui@9c32ae3fe54b3b92a9ff7e9addb40a670b0e034f`;
- `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7`;
- `sf-v5.3.2-9c32ae3f-ab896dc7`;
- Core archive SHA-256:
  `1b3c4545e510ef4090d387029f437b626d86135112d025dc073498b949c79095`.

## Verification

- full PHPUnit matrix: `331` tests, `5127` assertions — PASS;
- static verifier: `198` pages, `14236` references, `0` broken — PASS;
- local page: `https://docara.test/ru/authoring/markdown/` — HTTP `200`;
- build and served SHA-256 match:
  `57d0eb0a5c50b1a48faa9f910f5efb4a9ceea14488e570e3c4208b87cc1878d2`;
- browser acceptance: three code blocks, syntax highlighting, three language
  headers, three `aria-label="Copy"` icon buttons, transparent inner
  background, light/dark and no native scrollbar reservation;
- component catalog renders both “Пример” and “Вызов” through one Highlight
  surface; the call shows the literal Markdown fence without a nested frame;
- long lines use the horizontal managed `sf-scrollbar` overlay contract;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/2026-07-26-native-code-horizontal-scrollbar`.

## Simplicity review

Primary outcome: a compact code surface built from existing Framework
contracts. Changed surface: Highlight and Clipboard owner components plus the
immutable Docara lock. Necessary additions: no new product component or
setting. Removal: redundant inner background and visible Copy label.
Alternative rejected: a Docara-local template/CSS patch because it would
duplicate Framework ownership. Complexity delta: neutral to lower.
Accessibility protected by the native button, explicit accessible name and
unchanged keyboard behavior. Verdict: PASS.
