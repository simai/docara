# G3E.0 — RED and immutable owner pin

Input HEAD: `c82c15edd113fe224334c0fff1ab2af5c581cf23`.

The accepted pin resolves in the read-only owner repository:

```text
git -C /Users/rim/Documents/GitHub/bx-simai.main show \
  b3cdff87563ff78e7eddf044048a4b298fc69036:docs/developer/specifications/schemas/smart.manifest.schema.json
```

Independent SHA-256: `9d65a9b3d63567ef8a12dd43f5c3e24913e2659105b088778dc50476a9578037`,
equal to `resources/contracts/sf5/smart/v1/source.json`.

The rejected local public schema SHA-256 was
`8d737b2fde5e351abada664e5618b2a43df6f7ae5b4358d76e987be188a917a0`.
Two semantic divergences were reproduced:

1. An owner-valid manifest containing top-level `family`, `children` and
   `constraints` was rejected by the local schema.
2. `slots.main.unexpected=true` was rejected by the owner schema but accepted
   by the local schema.

This proves that the rejected file was neither the owner blob nor a faithful
public projection. The correction vendors the byte-exact blob and keeps any
Docara cross-field restrictions in a separately identified admission policy.

