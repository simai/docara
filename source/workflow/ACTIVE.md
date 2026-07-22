# Active workflow: Docara Smart search trigger

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-smart-search-trigger`
Process model: `general_delivery`
Current state: `review_ready`
Target state: `review_ready`

## Current goal

Replace the manually assembled header search control with the admitted
Framework `sf-button` Smart component without regressing search behavior,
keyboard access or responsive rendering.

## Result

The header now invokes the admitted `sf-button` Smart component with size `1`,
outline/on-surface semantics, the Framework search icon and the keyboard hint
through the right slot. Focused/full tests, exact build, static verification,
rollback-safe local publication and desktop/mobile browser acceptance pass.

## Completion guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-smart-search-trigger/`. Public push,
merge, tag, package release and production readiness remain excluded.
