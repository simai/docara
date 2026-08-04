# Goal 3E — Framework owner schema fidelity correction

Status: `in_progress`

## Immutable input

- Handoff HEAD: `c82c15edd113fe224334c0fff1ab2af5c581cf23`.
- Rejected Goal 3D product candidate: `ba89bccf8e2ad11ed7c72d89d380e924aaaf17d8`.
- Framework owner revision: `b3cdff87563ff78e7eddf044048a4b298fc69036`.
- Owner schema path: `docs/developer/specifications/schemas/smart.manifest.schema.json`.
- Owner schema SHA-256: `9d65a9b3d63567ef8a12dd43f5c3e24913e2659105b088778dc50476a9578037`.

## Outcome

`schema smart` publishes the byte-exact Framework-owned Smart manifest schema.
Docara admission rules remain a separately named consumer policy and never
masquerade as a second public ABI. Scaffold, providers, CLI, JSON and MCP use
the same neutral identity and validation chain.

## Batches

1. Preserve the two semantic RED cases and exact pin/hash evidence.
2. Vendor the exact owner schema; separate owner-schema validation from Docara
   admission policy.
3. Bind scaffold, all providers, discovery and tests to that chain.
4. Synchronize public docs, specification, graph and handoff.
5. Repeat focused/full/security/build/package/consumer/browser verification.

## Stop conditions

- The exact owner schema cannot be consumed without an owner ABI decision.
- A second public dialect/runtime or weakened validation/security is required.
- A user change overlaps the implementation surface.

The untracked content/design/settings proposal is user-owned and excluded from
this goal.

