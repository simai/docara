# Design Atlas contract

Status: Goal B implementation contract (`docara.design_atlas.v1`).

## Source and projection

Design Atlas is a deterministic read-only projection of the effective
`DesignRegistry`, `SmartRegistry` and `BindingRegistry`. It is not an
authoring file and cannot register an artifact. The CLI, JSON output and
optional MCP tool call the same `DesignAtlasService` and return the same
`OperationResult`.

```bash
docara atlas
docara atlas --json
```

The projection contains Layout, View, Section, Block, Smart, binding and
preset entries. Every entry includes owner, provider, support state,
capabilities, schema and provenance. `owner` identifies who controls the
artifact; `authoring_kind` describes how authors may use it. These fields are
independent. Markdown fence length has no typing semantics.

## Containers

An entry is a container only when its admitted registry descriptor provides a
bounded child contract. The serialized `container_contract` states:

- admitted child kinds or IDs;
- admitted slots;
- minimum and maximum child count;
- ordering rule;
- maximum nesting depth.

Layouts derive the contract from registered regions. Sections derive it from
registered slots and allowed blocks. Portable Smart containers derive it from
the accepted owner manifest. Unknown files and prose cannot add Atlas entries.

## Integrity

The result has fingerprints for all three registries and one canonical Atlas
fingerprint. Ordering is stable by `kind` and `id`; timestamps and absolute
paths are absent. A consumer must validate the result against
`resources/schemas/design-atlas.schema.json` and must not treat a copied or
hand-edited Atlas result as registry input.
