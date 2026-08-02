# C5 — independent dist consumer determinism

Status: `pass`

Two fresh Composer consumers installed the exact rc.3 ZIP as dist, without a
package `.git`, from one immutable consumer lock. The common lock SHA-256 was
`aa39557094868048b547972f536e339c1cdfa66375decd54a5870b12cf4288a0`.
Installation was separated in time, then every content source in consumer two
was deliberately assigned a different filesystem mtime. This perturbation is
the regression stimulus; no output was touched or excluded from comparison.

| Check | Consumer one | Consumer two |
| --- | --- | --- |
| representative source mtime | `315522000` | `1785672655` |
| public routes | 103 | 103 |
| output files | 305 | 305 |
| complete tree SHA-256 | `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46` | same |
| `_docara/page-metadata.json` SHA-256 | `88391e65bf59b94ae34c9d3cfb7ddf2287ff45759357ef96d2ba3b52292e4eff` | same |
| non-null `updated_at/revision/author` | 0/0/0 | 0/0/0 |
| static verifier | 206 HTML, 21,437 refs, broken=0 | same |

`diff -qr` across all outputs was empty. Full/single HTML for
`/ru/components/alert/` matched at
`a1ebce7ac2d236ab4890780af60f9781d33ee6da7c2fc43db109feaa0590562a`.
Both fresh initialized starter sites also passed build/static verification:
38 routes, 76 HTML, 3,742 references, broken=0.

This directly closes the audited `filemtime` defect: complete public identity,
including page metadata, now depends only on immutable inputs.
