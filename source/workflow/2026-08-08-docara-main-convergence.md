# Docara v2 main convergence

Date: 2026-08-08
Status: `docara_main_converged_ui_doc_migration_next`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `migrate_ui_doc_content_onto_docara_v2_then_converge_ui_doc_main`

Next roadmap goal: `ui_doc.content_migration` (`authorized_in_progress`, authorized=`true`)

Execution progress: `docara_main_convergence_integrated_retest`.
Planned terminal next action after acceptance:
`migrate_ui_doc_content_onto_docara_v2_then_converge_ui_doc_main`.

## Outcome

The exact Docara v2 product/runtime candidate
`d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f` is independently accepted with
`PASS_WITH_NOTES`. The already-completed local Framework runtime audit is
closed. Product/runtime/public-guide output is unchanged by this governance
transition.

## Ordered execution

1. Converge the accepted Framework contract branches into Framework `main` and
   verify exact packages and consumers. Complete: `simai/ui-loader@adc75d4f…`,
   two consecutive green hosted validation runs.
2. Bind the accepted Framework owner baseline truthfully. Complete: the current
   owner/source baseline is `simai/ui-loader@adc75d4f…`; existing Docara runtime,
   typography and portable Smart packets retain their own exact immutable source
   revisions because they were not rebuilt in this history-convergence batch.
3. Resolve the existing Docara `main` delta without weakening the v2
   architecture, then run the full integrated acceptance matrix.
4. Only after Docara `main` is accepted, migrate the useful `ui-doc` content
   onto the final runtime.

The detailed inventory, backup, rollback, stop conditions and cross-repository
sequence are owned by
`/Users/rim/Documents/GitHub/larena-workspace/source/workflow/2026-08-08-docara-v2-release-and-branch-cleanup.md`.

## Legacy main history decision

GitHub `origin/main@ff48ea54…` and the accepted v2 branch diverged after
`ecfc8b72…`: 13 legacy-main commits versus 279 v2 commits. A trial content merge
attempted to restore retired templates, language packs, legacy publishers and
tracked build/output artifacts. It was aborted. Normal merge commit
`0f62294…` uses Git's `ours` tree strategy to connect the histories while
preserving the accepted v2 tree exactly: tree SHA-1 before and after is
`4a79423a71bc473c358fd10dd017722b9583e438`.

This is a history convergence, not a claim that obsolete legacy files remain
supported. The exact evidence is in
`source/workflow/evidence/2026-08-08-docara-main-convergence/INDEX.md`.

## Boundary

Ordinary merge and push are authorized only for the accepted convergence
candidates. No force-push, rebase/history rewrite, tag, release, deployment or
external site write is authorized.
