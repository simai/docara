# G3D.1 — authoritative Smart schema and neutral provenance

Date: 2026-08-04
Status: green implementation checkpoint
Exact product/docs candidate: `ba89bccf8e2ad11ed7c72d89d380e924aaaf17d8`
Rollback boundary: `f39cd3c61b0510d3092831001e7eb88ac5c459d1`

## Public schema to scaffold proof

From a disposable copy of `stubs/portable`:

```text
php docara schema smart --json
php docara scaffold smart project.audit-card --dry-run --json
php vendor/bin/phpunit tests/Unit/SmartSdkPortableContractTest.php
```

- schema result SHA-256:
  `d3e8a4e0d7c08d20e7a9bed785be0b1b08ddc1e9970623d6ac68fa768d108a32`;
- authoritative schema: `portable-smart-manifest.schema.json`;
- schema file SHA-256:
  `8d737b2fde5e351abada664e5618b2a43df6f7ae5b4358d76e987be188a917a0`;
- scaffold plan id:
  `f7d1f23c6558713ff9d93786ade95980b092c73962e46b2fb41f4ce9d9f06808`;
- scaffold plan file SHA-256:
  `6ade04184754a91f30962a568ea6dd9f7862f45c074aff92622eab0013d93c7f`;
- unchanged `smart/project.audit-card/manifest.json` SHA-256:
  `ad052664e7734ce68ff4454aa41542db2b596300bac7f4caffda5212c5341867`.

The focused contract test validates that exact scaffold manifest and every
effective Smart portable manifest with the schema returned by `schema smart`.
No dialect conversion occurs between scaffold and validation.

The returned public identity is:

```json
{
  "contract_id": "sf.smart_artifact_abi",
  "schema_version": "1.0.0",
  "compatibility_id": "sf-smart-artifact-abi-v1",
  "source_revision": "b3cdff87563ff78e7eddf044048a4b298fc69036",
  "storage_compatibility_alias": "sf5.smart.artifact.v1"
}
```

## Provenance matrix

The real CLI JSON result and the optional MCP adapter return equal application
results. Human output is a projection of the same result. All inspected
definitions expose the neutral identity above and keep adapter/template facts
separate:

| Smart | Provider | Provider adapter | Template ABI |
| --- | --- | --- | --- |
| `docara.brand` | `docara.package` | `docara.legacy-smart-manifest.v1` | `docara.legacy.object-view.v1` |
| `docara.navigation` | `docara.package` | `docara.legacy-smart-manifest.v1` | `docara.legacy.object-view.v1` |
| `docara.toc` | `docara.package` | `docara.legacy-smart-manifest.v1` | `docara.legacy.object-view.v1` |
| `docara.preferences` | `docara.package` | `docara.legacy-smart-manifest.v1` | `docara.legacy.object-view.v1` |
| `ui.alert` | `framework.lock` | `docara.legacy-smart-manifest.v1` | `docara.legacy.object-view.v1` |
| `ui.button` | `framework.lock` | `docara.legacy-smart-manifest.v1` | `docara.legacy.object-view.v1` |
| `project.notice` | `project.project` | `portable.manifest.direct` | `sf5.smart.template.v1` |

The golden projection SHA-256 is
`8e3c67fe56526a0a2b8d54593cbf977927698da50047871562f8f68e1a6ca1b4`.
The historical name appears only as `storage_compatibility_alias`; neither
`contract` nor `legacy_adapter` is emitted as a public artifact identity.

## Negative and zero-reference proof

- requesting `declarative-smart-manifest.schema.json` now fails with
  `SCHEMA_NOT_FOUND`;
- invalid strategy/hydration combinations fail with
  `SCHEMA_VALIDATION_FAILED`;
- active `src/`, `resources/`, public docs and README contain no reference to
  the retired public schema name and no legacy contract identity field;
- project write-root, symlink, hardlink, traversal, collision, dry-run and
  hash-bound apply tests remain in the focused/full suites.

No component-id branch/list, renderer, Gateway, LayoutComposer or PageBuilder
was added.
