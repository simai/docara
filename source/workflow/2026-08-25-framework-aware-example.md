# Workflow: Framework-aware native Example preview

Date: 2026-08-25
Status: completed

## Outcome

Sandboxed native HTML/CSS/JavaScript Example previews can render against the
same pinned Framework styles, theme and direction as the owning documentation
page. This enables repository-owned examples to replace external Playground
iframes without copying support CSS into every Markdown page.

## Contract

- The parent shell discovers only pinned stylesheet links marked with
  `data-docara-framework-asset`.
- Each measurement message carries those resolved stylesheet URLs plus the
  current light/dark theme and text direction.
- The sandbox creates managed stylesheet links, applies the environment and
  remeasures after styles load.
- The iframe remains sandboxed with `allow-scripts` and no same-origin access.
- Theme and direction changes are propagated to existing previews.

## Evidence

- PHP lint and renderer contract pass.
- Full ui-doc migration validates 221 exact HTML sources and 7 exact JavaScript
  sources.
- Full ui-doc build: 939 pages.
- Static verification: 1692 HTML pages, 359545 local references, zero broken.
- Browser: the preview loads two pinned Framework stylesheets, renders Framework
  cards, follows `theme-light` and `theme-dark`, and retains automatic height.

## Boundaries

No commit, push, tag, release or deploy was performed.

## Control-plane diagnostics

- Repository Mirai Graph and `scripts/project-context.php check` pass.
- Project memory is closed as completed; no active implementation track remains
  and the next entry is `explicit_user_decision`.
- Project Technology remains blocked by the installed Node 25
  `libsimdjson.30.dylib` failure and missing accepted target binding.
- The task-specific Human-Centered Simplicity review passes; the canonical
  checker is unavailable because the generated Federation runtime bundle is
  absent, so no canonical PASS is claimed.
