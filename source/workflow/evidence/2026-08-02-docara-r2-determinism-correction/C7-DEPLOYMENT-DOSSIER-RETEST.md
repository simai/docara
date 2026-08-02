# C7 — live delta and disposable cutover/rollback retest

Status: `pass`

The live tree was read only. Its digest remains
`b98ea2f66b733c5146360af68c1fe15b55aa099b33957fe52813772d93ce836f`
with 322 files and 206 HTML. The new candidate has 305 files, 206 HTML and
digest `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`.

Exact inventory: 112 changed, 19 removed and 2 added paths. No public route was
removed. The 19 removals are the retired generated catalog/example receipts,
their duplicated catalog assets, and one `.DS_Store`; additions are
`.docara/backlinks.json` and `.docara/component-index.json`.

In a same-filesystem disposable mirror, the repository cutover helper accepted
the exact current/candidate digests, performed current -> candidate, passed
103/103 HTTP smoke, and rolled back to the exact current digest. A final
preflight confirmed the restored baseline. Any live execution remains blocked
on explicit user approval and a fresh read-only digest preflight.

No file under `/Users/rim/Sites/docara.test`, no Caddy state, and no existing
backup or staging directory was changed. Existing retention constraints and
stop thresholds from the R2 dossier remain applicable.
