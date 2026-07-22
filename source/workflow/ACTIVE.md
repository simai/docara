# Active workflow: Docara scrollbar divider alignment

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-scrollbar-divider-alignment`
Process model: `full_qa`
Current state: `review_ready`
Target state: `accepted`

## Current goal

Keep the independent left-navigation and right-contents scrollbars one
Framework `b2` token from their respective inner column dividers without
disturbing content spacing, directionality or mobile layout.

## Result

Generic outer padding was removed from both rails. Divider spacing now uses
`var(--sf-b2)`, while existing content padding lives inside the scroll
containers. The right rail keeps its native scrollbar at the logical divider
and restores the document direction for content.

Focused and full tests, exact build, static verification, visible overflow
checks on both rails, desktop geometry, mobile layout and console checks pass.
The build is published to `https://docara.test/` with a rollback copy.

## Completion guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-scrollbar-divider-alignment/`.
Public push, merge, tag, package release and production readiness remain
excluded.
