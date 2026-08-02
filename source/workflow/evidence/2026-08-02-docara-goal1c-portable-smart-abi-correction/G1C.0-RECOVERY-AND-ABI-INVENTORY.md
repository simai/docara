# G1C.0 — recovery and ABI inventory

Status: `PASS`
Input revision: `531ccdbb3493a3109bfabe91bb3f2e00a17447ce`

## Recovery

- repository: `/Users/rim/Documents/GitHub/docara-unified`;
- branch: `codex/docara-unified-architecture`;
- input worktree: clean;
- rejected implementation: `34496d49ce366f1108d2aed37c0adda35f6e5f58`;
- Goal 2 and all live/release actions are excluded.

## Accepted contradictions

- pinned SF5 renders portable templates with array context values, but the
  committed fixture/starter and Docara registered-template strategy expect a
  Docara-only object `$view`;
- the prior cross-host hash has no reproducible harness and is rejected;
- search extraction and Framework admission still encode Alert/Button IDs;
- an available legacy DocumentParser initializes a fixed Alert/Button parser;
- RegionCompositionResolver's ID list belongs to the still-pending Goal 2.

## Preflight evidence

Federation action gate command:

```bash
source /Users/rim/.codex/simai-workspace/install.env
"$KIT_ROOT/scripts/federation-gate-exec.sh" --task "Goal 1-C: correct portable SF5 Smart ABI, component-ID dependencies, tests, docs, graph and evidence in docara-unified; commits only, no deploy/release/external writes" --repo /Users/rim/Documents/GitHub/docara-unified --phase preflight --json
```

Result: PASS for `pre_commit_safety_gate`,
`release_context_boundary_gate`, `repo_hygiene_gate` and
`runtime_naming_gate`; blockers zero. Route selected disabled `docara`, so the
repository-local contract and documented fallback owners govern execution.

## Exact upstream inventory

All five records in `resources/contracts/sf5/smart/v1/source.json` match
`git show d6f90bba…:<path>` byte-for-byte:

- manifest schema `9d65a9b3…`;
- view schema `f7592ddd…`;
- preset schema `cbaa993e…`;
- `Smart.php` `d1dda732…`;
- runtime proof `bf7276a9…`.

The template variables are arrays/scalars named `id`, `smart`, `manifest`,
`view`, `preset`, `props`, `childrenHtml`, `slot`. G1C.1 records the discovered
exact-host implementation contradiction for resolved view data and slot.
