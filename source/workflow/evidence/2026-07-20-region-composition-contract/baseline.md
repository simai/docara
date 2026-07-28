# Baseline

Date: 2026-07-20
Status: PASS

- Branch: `codex/docara-consolidation`.
- Starting revision: `39b8741`.
- Worktree was clean before this goal.
- Accepted legacy renderer SHA-256:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
- Existing declarative layout already named `header`, `sidebar`, `main`,
  `outline`, `footer`, but `DeclarativePageCompiler` selected header/sidebar/
  outline composition directly in PHP.
- `presentation.schema.json` exposed only `layout.max_width`; site, section and
  page descriptors could not configure region content or enablement.
- The declarative preview was already available next to the unchanged legacy
  publisher.

The outer `larena-workspace` contains unrelated `.gitignore` and
`.playwright-cli` changes. They were not touched. The Docara worktree remained
the only implementation surface.
