# Workflow: Docara 2 audit corrections

Date: 2026-07-22
Status: in-progress
Track: `docara-consolidation`
Goal: close the four blocking findings from the repeat release audit.
Process model: `docara_documentation_site_publication`
Launch record: `source/workflow/2026-07-22-docara-2-audit-corrections.launch.yaml`

## Done When

- the documented `docara init [path]` contract works from a clean package and
  current-directory mode remains compatible;
- Composer archives made from one exact SHA have identical contents before and
  after local dependency installation;
- canonical and active installed Docara skills describe only Docara 2 and pass
  the Skill Sync Gate;
- `https://docara.test/` is served from the exact corrected candidate after a
  recorded backup and has a tested rollback path;
- automated PHP 8.2/8.4, distribution, generated-docs and desktop/mobile
  acceptance are green;
- a fresh reverse-outcome audit records the final verdict.

## Constraints And Risks

- preserve all user content during `init --update`;
- do not publish a package, create a public release, push the Docara product
  branch or merge; the canonical Docara skill may be pushed only after its
  dedicated gate so federation maintenance can install its immutable SHA;
- do not manually rewrite federation release symlinks;
- do not expose `.env` or other secret values;
- do not remove the legacy local site until the corrected site has an exact
  backup, rollback path and browser acceptance;
- product source, canonical skill source, installed federation runtime and
  local ServBay site are separate ownership surfaces.

## Batch Plan

| Batch | Goal | Verification | Status |
|---|---|---|---|
| B1 | Implement `init [path]`, align docs and reproducible archive | focused tests, full PHP matrix, two-state archive comparison | completed |
| B2 | Correct canonical Docara skill and install through federation maintenance | skill-change detect, graph sync, federation verify, route check, installed-content smoke | completed |
| B3 | Replace local `docara.test` with exact candidate safely | backup manifest, rollback rehearsal, build digest, HTTP/browser desktop/mobile smoke | planned |
| B4 | Independent integrated acceptance | clean clone/package tests, docs verifier, browser regression, reverse audit | planned |

## Allowed Surfaces

- Docara product worktree code, tests, README, documentation, Composer metadata,
  changelog/version only when required by shipped behaviour;
- this workflow, launch record and evidence under `source/workflow/evidence/`;
- canonical `/Users/rim/Documents/GitHub/ai-codex-skill-docara` only after its
  canonical-write action gate;
- active federation installation only through supported maintenance/install
  commands;
- `/Users/rim/Sites/docara.test` only after backup/rollback preflight.

## Forbidden Actions

- broad deletion or cleanup of the legacy site;
- manual symlink replacement inside `~/.codex`;
- package publication, GitHub release, tag, Docara product push or merge;
- hidden changes to other `ui-*`, Larena or legacy Docara repositories;
- readiness claims based only on the current worktree.

## Evidence

Store batch evidence under:
`source/workflow/evidence/2026-07-22-docara-2-audit-corrections/`.

## Stop Conditions

- `init --update` changes a user-owned file;
- optional target-path semantics introduce path escape or non-empty-directory
  overwrite;
- archive contents still depend on ignored/untracked files;
- federation maintenance cannot install the corrected skill safely;
- backup or rollback for `docara.test` cannot be verified;
- exact candidate and served site differ.

## Progress

### B1

- Status: completed
- Done: `init [path]` implemented for relative and absolute paths while `.`
  remains the default; update preservation and file-target refusal are covered;
  README/site docs match the executable contract; PHP 8.2 and 8.4 both pass
  310 tests / 4162 assertions; Composer archive contents are identical before
  and after dependency installation and contain no `composer.lock`.
- Evidence: focused suite 23 tests / 628 assertions; deterministic archive has
  421 entries and aggregate digest
  `9661367be773478e01704d7d0adaf2704edf15fb69a726c060d8f77cf2308f93`.

### B2

- Status: completed
- Canonical skill: `0aa77d09eec9f045683a9dcc91a17f126c820504`
  pushed to `simai/ai-codex-skill-docara` after its action gate.
- Skill Sync Gate: source detection, graph sync, canonical smoke, graph
  contract gate and route check passed.
- Installed runtime: release digest
  `13166cc4cbef0a6fe4f1fc55897bec6c036622af755832f619b6ec07122f2ffe`;
  installed and canonical `SKILL.md` SHA-256 both equal
  `69b843029fbf5ea590948679c4fb508541d1877d85a63250f75b019b3f32c74b`.
- Installed skill mentions neither Jigsaw nor Mix and exposes the verified
  `init [path]` contract; all 226 federation route scenarios pass.
- Next: commit the product candidate, then deploy that exact archive to the
  local ServBay site with backup and rollback rehearsal.

## Final Result

- Result: pending
- Verification: pending
- Remaining: B3-B4
- Follow-up: public release remains a separate gated workflow.
