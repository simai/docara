# Active workflow: Docara neutral outline marker

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-neutral-outline-marker`
Process model: `full_qa`
Current state: `review_ready`
Target state: `accepted`

## Current goal

Keep the active contents marker visible while replacing the primary action
color with a quieter neutral Framework state token.

## Result

The divider stays on `--sf-outline-variant`; the active marker now uses neutral
`--sf-outline`. Browser evidence confirms the marker in both themes, unchanged
`2 x 36 px` geometry, `0 px` divider displacement, bold active text and no
horizontal overflow. Focused and full tests, exact build, static verification
and local publication pass with rollback.

## Completion guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-neutral-outline-marker/`.
Public push, merge, tag, package release and production readiness remain
excluded.
