# Track: docara-shell-resource-preload-correction

## Purpose

Correct first-paint shell instability by extending the existing Framework
planner, runtime lock, receipt and verifier without a second cache or registry.

## Boundaries

- In scope: static shell assets, truthful preloading, natural geometry,
  verification and local `ui-doc.test` refresh.
- Out of scope: content changes, global skeletons, fixed header height, public
  deployment and Git publication.

## Related Tracks

- `docara-stable-shell-loading` (historical route superseded by this correction)
