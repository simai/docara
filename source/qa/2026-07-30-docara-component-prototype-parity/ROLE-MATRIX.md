# Role and access matrix

| Role | Responsibility | Access |
|---|---|---|
| Executor | Inventory, implementation and local build | local worktree and local test site |
| Domain owner | SIMAI Framework contract | source-first; no manual generated edits |
| QA | Automated and browser acceptance | read-only evidence plus local test execution |
| Release owner | Merge, tag, package and public release | out of scope |

The user explicitly authorised local implementation. Release actions remain a
separate gate.
