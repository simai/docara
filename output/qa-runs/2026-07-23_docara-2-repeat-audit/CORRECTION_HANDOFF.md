# Correction handoff

## Batch C1 — Restore the Docara 2 skill in immutable Federation releases

Scope: federation release selection/install metadata only. Canonical skill
source already contains the correct commit and is pushed.

1. Find why stable maintenance selected `c160b392…` after successful activation
   of `0aa77d09…`.
2. Pin/admit canonical `0aa77d09…` or a newer reviewed commit in the release
   source of truth.
3. Run the required Skill Sync Gate and immutable maintenance update.
4. Verify `/Users/rim/.codex/skills/docara/SKILL.md` checksum equals canonical,
   contains Docara 2, and has zero Jigsaw/Mix instructions.
5. Rerun route/technology packet and a disposable `init [path]` smoke.

Do not manually edit `runtime-current` or an immutable release directory.

## Batch C2 — Repair continuation state

1. Align top-level and track-local current records with the accepted correction
   workflow and terminal state.
2. Ensure continuation either restores the valid track or proposes the next
   release workflow without a failed execution contract.
3. Add a regression check for `продолжай работу над Docara`.

Do not reopen completed implementation batches or change product code.

## Batch C3 — External delivery

After C1/C2 acceptance:

1. add PHP 8.2/8.4 and archive-contract CI;
2. run exact candidate acceptance once more;
3. use the Git/release action gate;
4. push the product branch and create a reviewable PR;
5. do not tag/publish until independent release acceptance.

## Regression baseline

Preserve all green outcomes from this audit: 310 tests/4162 assertions on both
PHP versions, 413-entry stable package, 190-HTML deterministic docs build,
exact `docara.test` digest, 97-page link scan, responsive navigation/search and
existing rollback backup.

