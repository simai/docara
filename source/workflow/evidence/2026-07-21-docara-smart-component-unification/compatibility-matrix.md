# Smart component compatibility matrix

| Requested ID | Canonical ID | Status | Implementation |
| --- | --- | --- | --- |
| `docara.brand` | `docara.brand` | canonical | one definition |
| `docara.header` | `docara.brand` | deprecated alias | same definition and templates |
| `docara.navigation` | `docara.navigation` | canonical | one definition |
| `docara.toc` | `docara.toc` | canonical | one definition |
| `docara.outline` | `docara.toc` | deprecated alias | same definition and templates |

The section IDs `docara.header` and `docara.outline` are intentionally retained
in the typed Section namespace. They are recipes that call `docara.brand` and
`docara.toc`; they are not duplicate Smart implementations.

Alias resolution is exposed in plan provenance with requested ID, canonical
ID, deprecated flag and reason. New examples and section definitions use only
canonical Smart IDs.
