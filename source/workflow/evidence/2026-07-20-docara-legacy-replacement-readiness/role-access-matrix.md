# Role and access matrix

| Role | Required scenarios | Access | State |
| --- | --- | --- | --- |
| Anonymous reader | navigate, search, read, copy code, theme, redirects | public local URLs | ready |
| Portable author | configure JSON/Markdown, build, verify | local repository/CLI | ready |
| Legacy migrator | map capability and old URL to portable outcome | legacy source/docs read-only | ready |
| Maintainer | run exact tests, inspect lock/provenance, publish local rollback-safe build | local repository and ServBay | ready; publication gated |

No authentication, user accounts, forms or persistent test data are in scope.
No external credential is required.
