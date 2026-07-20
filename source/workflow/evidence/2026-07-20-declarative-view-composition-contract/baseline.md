# Baseline

- Repository: `simai/docara` consolidation worktree.
- Branch: `codex/docara-consolidation`.
- Candidate base: `5523e9519fbb6a3765025e8d1042e4edcf62b12b`.
- Worktree before this workflow: clean.
- Accepted legacy renderer SHA-256:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
- Current declarative model already separates registered Layout, Section,
  Block and Smart definitions, but layout/section structure still renders
  through fixed PHP templates and region calls copy nested block internals.
- Current `ResolvedRenderPlan` exposes regions and blocks but not normalized
  calls, named slots, safe View Trees or diagnostics.
- Federation route selected Docara as owner. Larena, Simai Framework, Dev and
  Tester are applied as companion contracts because the goal is explicitly a
  Larena-compatible frontend/runtime prototype with acceptance evidence.
