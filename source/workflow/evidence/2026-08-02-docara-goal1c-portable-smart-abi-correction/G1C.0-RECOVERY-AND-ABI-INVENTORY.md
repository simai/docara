# G1C.0 — recovery and ABI inventory

Status: `IN_PROGRESS`
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

The exact upstream paths, blob hashes and render ABI will be appended after
read-only `git show` inventory and before runtime implementation.

