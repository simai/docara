# Automatic Framework asset planning evidence

Status: completed and accepted locally

- The repository baselines and protected dirty work are recorded in the workflow.
- Reuse inventory confirms the existing Framework Contract Registry,
  `distr/rule/rule.json`, Loader and HTTP cache are sufficient owners.
- No second registry, Loader fork, SPA router, HTML cache or project-wide
  skeleton is admitted.
- Generic planner: `/Users/rim/Documents/GitHub/ui/scripts/plan-framework-assets.py`.
- Generic planner tests: 8 passed, including dependency order,
  `data-sf-require`, no-match compatibility, invalid regex/dependency,
  symlink, hardlink and unknown requirement failures.
- Docara exact production plan: 127 page receipts and 29 deduplicated
  content-hashed stylesheets; `utility.full.css` is absent from optimized HTML.
- Docara focused verification: 80 tests and 2,139 assertions passed. The
  working-tree runtime projection packet is
  `e46df2dfdcfc5124c9b0ca0fb319c1ce23a0f581ffb0676f8d23f852ce1e7804`.
- The final Docara documentation build produced 127 authored pages; static
  verification inspected 261 HTML outputs and 35,025 local references with
  zero broken records.
- Static verifier unit coverage remains fail-closed for stale per-page plans,
  generated CSS hash drift, preload order and asset projection integrity.
- The unchanged ui-doc content and dependency locks produced 912 pages. Static
  verification inspected 1,665 HTML outputs and 370,385 local references with
  zero broken records.
- Browser acceptance covers cold/warm desktop, mobile, light/dark and changed
  font geometry. The worst observed page CLS is `0.00134`; the corrected
  hydration control is `0`, and header/sidebar/outline/main are not shift
  sources.
- Local HTTP smoke passed for the landing, modifiers and background-attachment
  routes. The temporary rollback was removed after browser smoke and no
  permanent backup was created in `/Users/rim/Sites`.
- ServBay CLI PHP 8.4 is now configured with a valid timezone and a 2 GiB
  memory limit. A fresh 912-page build completed without a command-line memory
  override, followed by zero-broken static verification, HTTP smoke and browser
  smoke. Exact results are in `post-servbay-memory-rebuild.json`; the earlier
  memory note is closed.
- Exact machine-readable results are recorded in `verification-summary.json`,
  `browser-acceptance-matrix.json`, `browser-final-hydration-geometry.json`,
  `browser-start-shift-sources.json` and `http-smoke.txt`.
- Project Technology synchronization and verification pass. The central final
  response route still misclassifies this SF5 product change as a skill change
  and requires an unconfigured target binding; the independent process resolver
  also fails on a historical 2026-07-30 launch YAML. These are control-plane
  graph gaps, not product acceptance failures, and no historical evidence was
  rewritten to hide them.
