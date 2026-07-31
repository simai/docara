# Acceptance: appearance settings and modal blur

## Candidate

- Worktree: `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`
- Served site: `https://docara.test/ru/`
- Framework mechanism: `sf-modal[overlay-class]` plus
  `backdrop-blur-none|small|medium|large`
- Authored default: `large`

## Automated evidence

- Focused PHPUnit: PASS (`5 tests`, `28 assertions`).
- Full PHPUnit: PASS (`331 tests`, `5137 assertions`).
- Production build: PASS (`90` authored pages).
- Static verification: PASS (`198` HTML pages, `14236` references, `0` broken).
- Generated and served trees: byte-for-byte file comparison PASS.
- `git diff --check`: PASS.

## Browser evidence

- Existing `Оформление` group contains independent `Тема` and
  `Размытие фона модальных окон` fields.
- Default `Максимальное` is selected.
- Changing to `Среднее` updates both modal `overlay-class` values to
  `backdrop-blur-medium`.
- Reload preserves `medium`; selecting `Максимальное` restores the authored
  default and removes the semantic divergence from site configuration.
- Search and side settings both use `backdrop-blur-large` after reset.
- Computed overlay in light and dark themes: black background and `blur(8px)`.
- Browser console: no warnings or errors.

## Scope decision

No additional visual group was introduced. Two related controls remain in one
`appearance` group and are distinguished by field legends and descriptions.
Framework source was not changed because the required public modal API and blur
utilities already exist in the pinned Framework tuple.
