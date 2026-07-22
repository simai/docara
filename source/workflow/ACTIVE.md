# Active workflow: Docara scrollbar side correction

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-scrollbar-side-correction`
Process model: `full_qa`
Current state: `review_ready`
Target state: `accepted`

## Current goal

Keep the left navigation scrollbar one Framework pixel token from its divider,
place the contents scrollbar on the physical right edge, and draw the active
contents marker directly on the physical left divider.

## Result

Navigation divider spacing uses `var(--sf-px)`. The contents scroll container
uses physical right-side scrolling, so the earlier scrollbar-width runtime
compensation is removed. The active marker stays on the left divider with
`0 px` measured displacement.

Focused and full tests, exact build, static verification, visible overflow
checks on both rails, desktop geometry and mobile layout pass. The build is
published to `https://docara.test/` with a rollback copy.

## Completion guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-scrollbar-side-correction/`.
Public push, merge, tag, package release and production readiness remain
excluded.
