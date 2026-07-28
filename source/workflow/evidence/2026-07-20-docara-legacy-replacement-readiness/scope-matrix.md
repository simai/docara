# Scope matrix

| ID | Surface | Reference | Target | Priority | Status |
| --- | --- | --- | --- | --- | --- |
| S-01 | Header/search/settings | legacy `/en/` | portable `/` | P1 | implemented; exact acceptance pending |
| S-02 | Four-level navigation/active trail | legacy nested menu | portable hierarchy demo | P1 | implemented; exact acceptance pending |
| S-03 | Breadcrumbs/TOC/previous-next | legacy article | portable article | P1 | implemented; exact acceptance pending |
| S-04 | Desktop content density | legacy article shell | portable `/start/` | P1 | implemented; exact acceptance pending |
| S-05 | Mobile navigation and outline | legacy off-canvas menu | portable mobile shell | P1 | implemented; exact acceptance pending |
| S-06 | Code rendering/copy | legacy code block | portable start/catalog | P1 | implemented; exact acceptance pending |
| S-07 | Legacy capabilities | legacy docs/source | portable migration contract | P1 | ledger complete; tester pending |
| S-08 | Legacy routes/404 | legacy generated route corpus | portable redirects | P1 | implemented; exact acceptance pending |
| S-09 | Locale/version | legacy locale path/switch | portable contract | P1 | implemented; exact acceptance pending |
| S-10 | Landing/catalog | N-A or legacy missing | portable routes | regression | working-tree PASS |
| S-11 | Framework assets/contracts | legacy runtime | pinned portable lock | regression | working-tree PASS |
| S-12 | Build/static/determinism | legacy build as reference only | exact portable archive | P1 | working-tree PASS; exact candidate pending |

Allowed adaptation: portable JSON + Markdown, PHP-only output, modal search and
settings, deeper semantic navigation and Simai Framework presentation may
differ from legacy. Mandatory invariants are discoverability, orientation,
readability, safe migration and deterministic output.
