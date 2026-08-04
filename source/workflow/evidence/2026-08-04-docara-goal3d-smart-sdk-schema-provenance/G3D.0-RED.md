# G3D.0 RED: public Smart schema and provenance drift

Exact source: `f39cd3c61b0510d3092831001e7eb88ac5c459d1`
PHP: `/Applications/ServBay/package/php/8.4/8.4.20/bin/php`
Disposable project: fresh copy of `stubs/portable`

## Schema versus scaffold

Commands:

```bash
docara schema smart --json
docara scaffold smart project.audit-card --dry-run --json
```

The public result selected
`declarative-smart-manifest.schema.json`, SHA-256
`2d2bdd373a20044671427c3fd87ac868ffaecf78b0e984ff76cef31913c6f710`.
It requires legacy `schema/key/version/owner_package` fields and restricts keys
to `docara.*`.

The scaffold plan id was
`f7d1f23c6558713ff9d93786ade95980b092c73962e46b2fb41f4ce9d9f06808`;
its plan-file SHA-256 was
`6ade04184754a91f30962a568ea6dd9f7862f45c074aff92622eab0013d93c7f`.
The unchanged generated portable manifest uses
`schemaVersion=1.0`, `kind=smart`, `code=project.audit-card`; manifest SHA-256
`ad052664e7734ce68ff4454aa41542db2b596300bac7f4caffda5212c5341867`.

Direct validation with the schema returned by `schema smart` failed exactly:

```text
SCHEMA_VALIDATION_FAILED:[SCHEMA_VALIDATION_FAILED] JSON value at [/] is missing required property [schema].
```

## Provenance drift

`inspect smart <id> --json` reported:

| Smart | Provider | Public contract fields |
| --- | --- | --- |
| `docara.brand` | `docara.package` | `contract=sf5.smart-artifact.v1`, `template_abi=docara.legacy.object-view.v1` |
| `docara.navigation` | `docara.package` | same legacy identity |
| `docara.toc` | `docara.package` | same legacy identity |
| `docara.preferences` | `docara.package` | same legacy identity |
| `ui.alert` | `framework.lock` | same legacy identity |
| `ui.button` | `framework.lock` | same legacy identity |
| `project.notice` | `project.project` | `contract_id=sf.smart_artifact_abi`, `contract_schema_version=1.0.0`, `contract_compatibility_id=sf-smart-artifact-abi-v1`, `storage_compatibility_alias=sf5.smart.artifact.v1`, `template_abi=sf5.smart.template.v1` |

The RED proves that homogeneous effective registry entries expose two public
contract vocabularies even though every invocation already goes through the
same registry and Gateway.

## Expected GREEN

- `schema smart` returns the neutral portable manifest schema and validates the
  exact scaffold manifest without transformation.
- Every effective Smart exposes the same neutral contract identity.
- Legacy storage alias, provider adapter and template ABI remain separate,
  truthful fields rather than competing public dialect identities.
