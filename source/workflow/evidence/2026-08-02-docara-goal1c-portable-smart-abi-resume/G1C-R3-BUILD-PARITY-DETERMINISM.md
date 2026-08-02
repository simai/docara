# G1C-R3 — build parity and determinism

Status: PASS at verified governance revision

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
`diff -qr` returned zero deltas before the required public contract wording
update. After that update the final two builds remained byte-identical: 103
pages, 305 files, 206 HTML, ledger SHA-256
`021f223c8aa4edf369ed4ba57628862ec192b775883e521e4174579075b94424`.
Each static run checked 21,430 local references with `broken=0`.

The candidate-to-pre-doc comparison has 104 raw changed paths because the
search index digest is embedded in public HTML. After normalizing only the
proven `search-index.json?docara_v=<sha256>` query, exactly four intentional
paths remain: the updated Smart authoring page, its two search indexes and the
resolved-page-plan receipt. Unexpected deltas: 0. Raw final HTML hashes:

| Route | HTML SHA-256 |
| --- | --- |
| `/ru/components/alert/` | `025b67a3d4ddb4dbbed55902f487a59d4d4c9dce343ef971bf7c7b8926a8b0d5` |
| `/ru/components/button/` | `6725203ba27ea0133f8818e691ee02656531bd5973d9acd656ecd30e7e12dd73` |
| `/ru/development/smart-components/` | `d39bab5f5487f4d4008ea8a7b21d5376aaf621050ef62b2725d5ff37cf0c3a86` |

Single-page rebuild of every route above preserved the complete 305-file
ledger byte-for-byte.
