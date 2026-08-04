# B0 — Design Atlas contract

Date: 2026-08-04
Status: `pass`
Entry HEAD: `3280a89cc21f2b4fcfc8e7539c673ca62a199446`

## Entry proof

- branch: `codex/docara-unified-architecture`;
- tracked worktree: clean;
- accepted Goal A product/runtime candidate: `8c04160…`;
- accepted exact public tree: `8b7fdb61…`;
- Goal B parent contract read from section 16 of the active track;
- current specification, shell contract, authoring contract, roadmap, graph,
  generated context and handoff inventoried before runtime edits.

## Inventory result

Accepted registries already exist for Design artifacts, Smart artifacts and
shell bindings. Discovery/CLI/MCP expose separate list/inspect projections but
there is no single serialized Atlas contract or freshness gate yet. Layouts and
Sections express region/slot/block compatibility, but do not yet expose the
complete count/order/max-depth child contract required by Goal B.

The replaceable publisher leaves `breadcrumbs`, `pager` and `search-dialog`
still resolve through `TrustedTemplateRegistry`; outer `page` and `head` are
application-owned and must remain there.

Repository search found no accepted `ui.input`, `ui.dropdown` or `ui.checkbox`
artifact/pin. B4 therefore remains an explicit external-dependency gate and no
local replacement is permitted.

## Implemented contract

- `DesignAtlasService` projects only the effective Design, Smart and Binding
  registries; unknown files do not enter the result;
- schema: `docara.design_atlas.v1` in
  `resources/schemas/design-atlas.schema.json`;
- serialized kinds: Layout, View, Section, Block, Smart, binding and preset;
- `owner` and `authoring_kind` are distinct fields; the vocabulary explicitly
  records that fence length has no typing semantics;
- Layout and Section containers expose child/slot/count/order/depth contracts;
  portable Smart containers use accepted manifest children/slots;
- Section instances are bounded to 64 blocks before registration;
- CLI `atlas`, JSON and MCP `docara_atlas` delegate the same application
  service and stable `OperationResult`.

## Focused verification

Executed from parent `0e0e4f3fa35b465a4bad7676a87b4955fd330f0d`
with ServBay PHP 8.4.20:

```text
vendor/bin/pint --test src tests/Unit/DesignAtlasTest.php
PASS

php vendor/bin/phpunit --filter 'DesignAtlasTest|McpAdapterTest|DeveloperDiscoveryCommandTest'
PASS — 11 tests, 101 assertions

JsonSchemaValidator(design-atlas.schema.json)
PASS

git diff --check
PASS
```

The initialized project fixture produced a stable registry-derived Atlas in
two consecutive calls. The exact fingerprint is content-addressed and is
recomputed by the regression rather than copied into this evidence file.

## Security and source-boundary outcomes

- an unregistered `design/README.txt` changes neither entries nor fingerprint;
- project-local `project.notice` is projected as project-owned without a new
  engine list;
- `docara.navigation:header` is projected as a registered preset independently
  of its owner field;
- container maxima are schema-bounded; unrestricted depth/count is rejected;
- no executable path, callback, renderer or Gateway is introduced by Atlas.

## Rollback

Revert the B0 implementation commit. No public output or project-owned source
is migrated by this checkpoint. The preceding router checkpoint remains
`0e0e4f3…`.
