# C1 independent audit intake

Input HEAD: `96e0c82900d7689c3045ac22d950c76129bff674`

Verdict received: `CORRECTION_REQUIRED`.

The auditor reproduced two different complete public trees from independent
fresh consumers of the same immutable `04c18c95…` ZIP/lock. The only reported
content difference was `_docara/page-metadata.json`: public `updated_at` came
from extraction-time `filemtime` outside Git. Observed tree hashes were
`01b4c9ae…` and `56c6b5be…`, not the dossier value `457790d4…`.

Decision: withdraw `rc.2` as an actionable candidate, reopen R2 and fix the
immutable-input contract. The historical artifact and evidence are not
rewritten. Live `docara.test` remains unchanged at the independently reported
tree digest `b98ea2f66b733c5146360af68c1fe15b55aa099b33957fe52813772d93ce836f`.

