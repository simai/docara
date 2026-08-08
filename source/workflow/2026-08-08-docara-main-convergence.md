# Docara v2 main convergence

Date: 2026-08-08
Status: `ready_for_main_convergence`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `framework_main_convergence_then_docara_repin`

Next roadmap goal: `docara.main_convergence` (`authorized_in_progress`, authorized=`true`)

## Outcome

The exact Docara v2 product/runtime candidate
`d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f` is independently accepted with
`PASS_WITH_NOTES`. The already-completed local Framework runtime audit is
closed. Product/runtime/public-guide output is unchanged by this governance
transition.

## Ordered execution

1. Converge the accepted Framework contract branches into Framework `main` and
   verify exact packages and consumers.
2. Repin Docara to that immutable Framework `main` identity.
3. Resolve the existing Docara `main` delta without weakening the v2
   architecture, then run the full integrated acceptance matrix.
4. Only after Docara `main` is accepted, migrate the useful `ui-doc` content
   onto the final runtime.

The detailed inventory, backup, rollback, stop conditions and cross-repository
sequence are owned by
`/Users/rim/Documents/GitHub/larena-workspace/source/workflow/2026-08-08-docara-v2-release-and-branch-cleanup.md`.

## Boundary

Ordinary merge and push are authorized only for the accepted convergence
candidates. No force-push, rebase/history rewrite, tag, release, deployment or
external site write is authorized.
