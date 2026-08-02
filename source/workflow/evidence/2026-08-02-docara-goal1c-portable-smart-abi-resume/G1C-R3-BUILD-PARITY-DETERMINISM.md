# G1C-R3 — build parity and determinism

Status: PASS at runtime implementation revision

Two disposable full builds each produced 103 pages, 305 files and 206 HTML
documents. Their complete sorted file/hash ledgers were byte-identical with
SHA-256 `4fd0d587dcbebd4ddeb42c5b100a74926b568a896d5104e4f6a09acff9ad4def`.
Static verification of each build inspected 21,430 local links/assets and
reported `broken=0`.

Representative `--page` rebuilds used the same PageBuilder. After each one,
the full 305-file ledger remained identical:

| Route | HTML SHA-256 |
| --- | --- |
| `/ru/components/alert/` | `c4c43b072493f15b1176e51a0508a3e0918e88df807fd2534800a0d199d53c35` |
| `/ru/components/button/` | `328131d0f5502cfde7f89cdf70edc4df26924a0ace3548d66795a4e98a599911` |
| `/ru/start/` | `c3002238b513ecfacd8feee2f5e8e1b1438cb7f50b3fefe9ef5eb77cdecb6475` |

An exported exact `7ea63d797cd0de3aa424ea0a9279abaf22775908`
baseline and the runtime candidate both produced 103 pages / 305 files;
`diff -qr` returned zero deltas. Final documentation-only deltas and the
post-governance build rerun are recorded in integrated acceptance.
