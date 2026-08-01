# Start here: Docara unified architecture

This is the only required entry point for a fresh task.

## 1. Verify the workspace

```bash
cd /Users/rim/Documents/GitHub/docara-unified
git branch --show-current
git rev-parse HEAD
git status --short
```

Expected branch: `codex/docara-unified-architecture`. The committed baseline
recorded by this handoff is `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`;
after the architecture commit, verify its parent is that exact revision.

Do not work in the dirty canonical checkout or in the old Larena-nested
worktree.

## 2. Read in this order

1. `source/handoff/docara-unified-architecture/STATUS.yaml`;
2. `docs/specification/README.md` and the five documents it links;
3. `graph/generated/ai-context/docara-unified.json`;
4. `graph/specs/batches/m0-code-map.json`;
5. `source/handoff/docara-unified-architecture/CONTEXT.md`;
6. `source/handoff/docara-unified-architecture/NEXT.md`.

Do not reconstruct the architecture from task history when these sources
already answer the question.

## 3. Execute only the ready batch

M0 mapping is preserved in the worktree and the architecture-contract
checkpoint resolves its recorded contradictions. After the checkpoint commit,
the ready implementation sequence is the bounded M1A/M1B plan in
`source/workflow/2026-08-01-docara-m1-m2-bounded-plan.md`; M2 starts only after
`docara.gate.badge_source_ready` passes.

For the contract checkpoint update:

- `docs/specification`, graph, roadmap and this handoff;
- preserve `graph/specs/implementation-mappings/*.json` from M0;
- `source/handoff/docara-unified-architecture/RESULT.md`;
- evidence under the workflow evidence root;
- `STATUS.yaml` only after the checkpoint validators and commit pass.

Target invariants now include `content/<locale>/lang.json` as the sole public
shared translation store, no public `resources/i18n` or `site.json`
compatibility, in-memory typed IR without mandatory page JSON/JSONL, and one
PageBuilder pipeline whose modes differ only by route selection.

## 4. Forbidden shortcuts

- do not use the installed stale Docara skill;
- do not move public prose into JSON, PHP projectors or component manifests;
- do not create a second parser, renderer or build path;
- do not mass-rewrite runtime during M0;
- do not delete legacy before parity evidence;
- do not merge to a default branch, tag, release or deploy;
- do not claim release or production readiness.

## 5. Historical task

The previous long task remains available as an archive of discussions,
screenshots and rejected alternatives. It is not required context for routine
work. If it reveals a missing accepted decision, record that decision in the
specification and graph before implementation.
