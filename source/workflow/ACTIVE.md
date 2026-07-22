# Active workflow: Docara scrollbar a2 correction

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-scrollbar-a2-correction`
Process model: `full_qa`
Current state: `review_ready`
Target state: `accepted`

## Current goal

Keep the independent left-navigation and right-contents scrollbars one
Framework `a2` token (`2 px`) from their respective inner column dividers and
draw the active contents marker directly on the right divider.

## Result

Divider spacing uses `var(--sf-a2)`, while existing content padding remains
inside the scroll containers. The active contents marker compensates for the
platform scrollbar width and stays directly on the divider with `0 px`
measured displacement.

Focused and full tests, exact build, static verification, visible overflow
checks on both rails, desktop geometry and mobile layout pass. The build is
published to `https://docara.test/` with a rollback copy.

## Completion guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-scrollbar-a2-correction/`.
Public push, merge, tag, package release and production readiness remain
excluded.
