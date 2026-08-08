# Docara v2 independent main-readiness evidence

Date: 2026-08-08
Verdict: `PASS_WITH_NOTES`
State: `ready_for_main_convergence`
Product/runtime candidate: `d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f`
Audited governance HEAD: `7120f8e2a26200f8666acedfeb13021011b5572b`

## Independent result

The accepted local Framework/Alert candidate was reproduced independently from
the exact repository state. The audit confirmed the single Markdown -> typed
Document IR -> registry/Gateway/LayoutComposer/PageBuilder path, exact local
Framework and icon admission, deterministic full/single output, static integrity
and clean package consumption. No second parser, renderer, registry, Gateway,
LayoutComposer or PageBuilder was found.

The verdict is `PASS_WITH_NOTES`, not a release claim: Docara still has to
consume the final accepted Framework `main`, converge the 13 `main`-only commits
without weakening the v2 architecture, pass the integrated candidate matrix and
receive green GitHub CI. Tag, release and deploy remain outside this verdict.

## Reproduced evidence

- full PHPUnit: `511 tests / 11,547 assertions`, failures/errors/skips `0`;
- two clean full builds and a representative single-page build: `652` files,
  canonical tree digest
  `db628b95db1087878f46c087297c289c153cdc1ef9675f474f358189d04b8521`;
- static verification: `261 HTML / 32,965 local references / broken=0`;
- two release-package builds: `997` files and byte-identical ZIP SHA-256
  `9f792eda04e87569084fcbc61c92e8bd8c37223098b2c4321693a2bff9226ed1`;
- exact package source revision:
  `7120f8e2a26200f8666acedfeb13021011b5572b`;
- clean tracked worktree and no product/runtime/public-guide edits during this
  governance acceptance transition.

Canonical build-tree digest remains sorted file SHA-256 records formatted as
`<sha256><two spaces><relative path><newline>` before the final SHA-256.

## Accepted boundary and next action

The independent local runtime audit is closed. The only current product action
is `framework_main_convergence_then_docara_repin`, followed by the separately
verified Docara `main` convergence defined in the external coordination workflow
`/Users/rim/Documents/GitHub/larena-workspace/source/workflow/2026-08-08-docara-v2-release-and-branch-cleanup.md`.

No S4/Goal D is created. No tag, release, deployment or external site write is
authorized by this evidence.
