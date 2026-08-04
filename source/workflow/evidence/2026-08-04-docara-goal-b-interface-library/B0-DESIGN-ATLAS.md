# B0 — Design Atlas contract

Date: 2026-08-04
Status: `in_progress`
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

## Next checkpoint

Implement the registry-derived Atlas schema/service and container contract,
then prove deterministic CLI/JSON/MCP parity and invalid-contract diagnostics.
