# docara-new.test current accepted candidate deployment evidence

Status: `pass`

Product/runtime candidate:
`eb35f5c6f18e5eb9be69e91887b09486f5703136`

Deployment input HEAD:
`3d3006d1579f9b879d83f934b9bfca4fa88287cc`

Operational contract:
`source/workflow/2026-08-06-docara-new-test-current-candidate-deployment.md`

## Evidence index

- `RESULT.md` — human-readable outcome, exact identities, commands and rollback;
- `result.json` — machine-readable deployment result;
- action-gate reports remain in the repository runtime output contour; the
  final preflight reported no blockers and retained advisory production
  warnings because the target is an HTTP-served site.

## Result

The exact accepted candidate was built twice, verified, atomically published
and checked through the real HTTPS host. The active tree is
`44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4`;
the exact previous tree is retained as the rollback directory. `docara.test`,
Caddy configuration, external repositories, branches, tags and releases were
not changed.
