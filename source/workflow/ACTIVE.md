# Active workflow: Docara outline visibility and stability

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-outline-visibility-and-stability`
Process model: `full_qa`
Current state: `review_ready`
Target state: `accepted`

## Current goal

Show the active contents marker visibly on the physical left divider and keep
a short contents list stationary below the header during page scrolling.

## Result

The divider is rendered inside the rail, so the marker stays within the
contents clipping boundary and is visibly blue. The sticky top now matches the
header offset: measured contents top is `72 px` both before and after a
`650 px` page scroll. Long contents retain native right-side scrolling.

Focused and full tests, exact build, static verification, visible overflow
checks on both rails, desktop geometry and mobile layout pass. The build is
published to `https://docara.test/` with a rollback copy.

## Completion guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-outline-visibility-and-stability/`.
Public push, merge, tag, package release and production readiness remain
excluded.
