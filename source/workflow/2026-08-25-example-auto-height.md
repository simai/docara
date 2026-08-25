# Workflow: Example auto height

Date: 2026-08-25
Status: completed

## Outcome

Native HTML/CSS/JavaScript Example previews no longer reserve a fixed 32rem
iframe. The sandboxed preview reports its rendered body height to the Docara
shell, which bounds and applies that height to the owning iframe.

## Scope

- Shared Example iframe markup and its sandbox-local measurement runtime.
- Shared shell listener and narrowly scoped iframe sizing CSS.
- Renderer, publisher-output and browser acceptance assertions.
- External internal-preview iframes keep their existing compact/default/tall
  sizing contract.

## Acceptance

- Short Result content has no large reserved empty area.
- HTML and CSS source tabs keep their natural content heights.
- Returning to Result restores the measured preview height.
- The sandbox remains `allow-scripts` only and the parent accepts a height only
  from the exact iframe window that sent the message.

## Evidence

- PHP lint passes for all changed PHP and test files.
- Direct renderer contract contains the frame marker and both resize messages.
- Full ui-doc build: 939 pages.
- Static verification: 1692 HTML pages, 359545 local references, zero broken.
- Browser route `/ru/utilities/animation/animation-duration/`: Result iframe
  159px and complete Example 258px; HTML 449px; CSS 1481px; returning to Result
  restores 258px.
- `scripts/project-context.php check`: success.

## Control-plane diagnostics

- Repository Mirai Graph verification passes with a current manifest and no
  blockers.
- Project Technology sync remains unavailable outside this batch because the
  installed Node 25 runtime cannot load `libsimdjson.30.dylib` and no accepted
  target provider is bound.
- The standalone Human-Centered Simplicity checker cannot load the missing
  repository-local federation runtime bundle. The task-specific review and
  tester evidence are recorded above, but the unavailable central contract is
  not represented as a passing canonical check.

## Boundaries

No commit, push, tag, release, deploy, active-site mutation or persistent
backup was performed.
