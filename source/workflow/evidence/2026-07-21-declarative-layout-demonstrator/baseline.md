# Baseline

- Candidate parent: `dd62e5b1c178b6750d61e667014bf6d6143e0f6f`.
- Branch: `codex/docara-consolidation`.
- Accepted legacy renderer:
  `src/PortableSite/PortableHtmlRenderer.php`.
- Accepted legacy SHA-256:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
- Current primary plans: `59`, all published by
  `docara.declarative_page_publisher.v1`.
- Current main routes without navigation:
  `/landing/` (intentional preset) and `/authoring/regions/` (bad public demo).
- Confirmed defect: `/authoring/regions/` emits an empty visible sidebar
  wrapper of `288px` after `sidebar.enabled=false`.
- All current primary routes returned HTTP `200` before the change.
- Preflight action gate:
  `source/output/action-gates/action-gate-report-20260720224127.json`.
- Local-only boundary: no push, merge, tag, release or production deployment.
