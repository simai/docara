# QA report: Docara 2 repeat audit

## Verdict

**CORRECTION_REQUIRED** для целостного контура и передачи разработчику.

Сам Docara 2 и `docara.test` имеют `PASS`. Главный blocker — активный
установленный skill, который после обновления Федерации снова описывает старую
Jigsaw/Mix-модель. Второй release blocker — исправленный product candidate
отсутствует на GitHub.

## Target

- repo: `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`;
- branch: `codex/docara-consolidation`;
- audit HEAD: `d239d9c97f32193385ac16212183e095338ac3f9`;
- exact product candidate: `0d2a528c4bd5cff5b4986ff60e0abd668d328f47`;
- URL: `https://docara.test/`;
- safety: local read-only audit.

## Acceptance summary

| Layer | Result | Evidence |
| --- | --- | --- |
| Composer/Pint | PASS | strict validate, formatter, no advisories |
| PHP 8.2 | PASS | 310 tests, 4162 assertions |
| PHP 8.4 | PASS | 310 tests, 4162 assertions |
| CLI init/update | PASS | relative/absolute target, user file preserved |
| Composer archive | PASS | 413=413 files, same digest, no lock |
| Composer-dist smoke | PASS | install/init/build/verify |
| Docs build | PASS | 190 HTML, 10 480 refs, deterministic |
| Site sync | PASS | clean build and served build identical |
| Links/404 | PASS | 97 pages, 0 failures; unknown route 404 |
| Browser desktop/mobile | PASS | search/highlight/nav/responsive/console |
| Rollback evidence | PASS WITH NOTES | backup + prior rehearsal; no live swap now |
| Active installed skill | FAIL | old `c160b39…` Jigsaw/Mix contract |
| Workflow continuation | FAIL | track/workflow not recovered |
| GitHub product branch | NOT READY | remote is 41 commits behind |

## Findings

### F-001 — HIGH — Active stable Docara skill regressed to Jigsaw/Mix

Expected: active skill matches canonical `0aa77d09…` and describes Docara 2.

Actual: `/Users/rim/.codex/skills/docara/SKILL.md` resolves to component
revision `c160b392…`, two commits behind canonical, and instructs Codex to use
Jigsaw, `source/docs`, `config.php`, npm/yarn and Mix-era paths.

Evidence: `active-skill-current.txt`, `active-skill-current-legacy.txt`,
`docara-skill-install-history.txt`. History proves a successful corrected
activation followed by an automatic rollback to the old revision.

Impact: Codex can recreate legacy architecture or give wrong maintenance
commands even though the product itself is corrected.

Retest: activate a release whose component revision is exactly `0aa77d09…` or
newer; compare hashes; require zero legacy refs; rerun route and one Docara 2
smoke task.

### F-002 — MEDIUM — Continuation state is internally inconsistent

Expected: a normal continuation request recovers the accepted track or clearly
starts the next workflow from its terminal state.

Actual: top-level `CURRENT.yaml` points to the correction closure, while
track-local `CURRENT.yaml` still points to the older portable-only candidate.
The resolver reports `track_workflow_not_found`, `active_workflow_not_found`
and a failed technology execution packet.

Impact: autonomous `продолжай` is unreliable and may fall back to generic or
obsolete guidance.

Retest: align project/track current records, run project-memory guard, then
resolve `продолжай работу над Docara` and require one coherent next action with
no fail status.

### F-003 — MEDIUM — CI does not test the declared PHP boundary

Composer declares PHP `^8.2`; manual 8.2 and 8.4 pass. GitHub Actions runs only
PHP 8.3 and does not execute the before/after archive determinism scenario.

Impact: future regressions at the supported boundaries can merge unnoticed.

Retest: CI matrix 8.2/8.4 plus package archive contract.

### F-004 — LOW — Mixed-language visible copy remains

The Russian home page still shows `Переносимый author path`. Runtime is not
affected, but the product does not yet look fully polished.

### R-001 — HIGH RELEASE BLOCKER — Exact product candidate is not on GitHub

Local branch is 41 commits ahead of
`origin/codex/docara-consolidation`; remote SHA is `a913dce6…` and does not
contain product candidate `0d2a528…`.

This was an explicit boundary of the earlier correction workflow, not hidden
source drift. Nevertheless another developer cannot install or review the
accepted result until a separate gated push/PR operation is completed.

## Not run

- Safari/Firefox/real devices;
- full WCAG audit, penetration and load testing;
- live rollback swap (prior rehearsal and current artifacts inspected);
- push, PR, tag, package publication or production deploy.

## Final acceptance readiness

Local product use: **ready with an exact local checkout**.

Codex-driven development: **not ready until F-001 and F-002 are closed**.

External developer review/public release: **not ready until R-001, CI and
release gates are closed**.

