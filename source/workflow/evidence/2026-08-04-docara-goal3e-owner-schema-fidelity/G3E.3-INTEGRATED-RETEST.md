# G3E.3 — integrated exact-candidate retest

Status: `ready_for_independent_audit`; not self-accepted.

Exact product candidate: `1e571b6e16ebc4520121aff0ae868de3b986dff3`.
Framework owner revision: `b3cdff87563ff78e7eddf044048a4b298fc69036`.

## Contract and regression

- Vendored and independently streamed owner schema SHA-256:
  `9d65a9b3d63567ef8a12dd43f5c3e24913e2659105b088778dc50476a9578037`.
- Owner-valid `family/children/constraints`: accepted.
- Owner-invalid nested slot property: rejected before admission.
- Unchanged `project.audit-card` scaffold: owner schema PASS, then
  `docara.smart_admission.v1` PASS.
- Focused SDK/provider/MCP/scaffold: 44 tests / 367 assertions.
- Full PHPUnit: 442 tests / 8,086 assertions.
- Exact Docara/SF5 cross-host: 1 test / 44 assertions, byte-identical.
- Pint, Composer strict/audit, 366 PHP files, 466 JSON files, 35 YAML files,
  project-context and candidate-range diff: PASS.

## Public build

Two full builds produced 104 routes / 307 files / 208 HTML. Full, second full
and selected Alert output share ledger
`e39061c0c952556a5fa82803c58f5dc2270855318f4d56346f3d8af627c7d331`.
Alert HTML is
`e1803412dc2ed849afc2f74711831ab2309df9f39f90c2034d2db0c43a281131`.
Static verification checked 21,842 local references with `broken=0`.

## Package and consumers

Two independent clean clones produced the same unpublished package:

- ZIP: `ca21bce847e991c715dfe2033aa923d48c19554b85f29050d026dd110b2362a9`;
- external manifest: `84528d29bfaa1ae19c20b37bf901b13f13778d71ca7e2940846f9c41cf5f4081`;
- checksum file: `4f21eef704a2281e2a592855df1f0770ac47a6f0aa2361d72539b2e2e94bcb89`;
- verifier: PASS, 742 files.

Two fresh Composer dist consumers used lock
`0b38ea01726f99318a636a8b083a6c2cc087516a276019b6010b44cdf1c570c0`.
Neither contains package `.git` or Node. Both initialized, verified and built
the same 168-file full/single tree at
`48deda0a0de047c17812a638e3931fa7c0173cfdc00af501b7caa63cb2cd8aec`.

## Fresh browser QA

All Smart/region/layout scenarios passed 24/24 through PreviewKernel/PageBuilder.
Each authoritative verifier returned 8/8; a11y, console errors/warnings,
overflow and visual diff are zero; keyboard/focus and reduced motion pass.

| Subject | Plan | Reference | Report | Artifact |
| --- | --- | --- | --- | --- |
| `smart:ui.alert` | `4f7f7f122f993a73b4315206346b2381066e5125cc5ec99217f06529b2049171` | `ead35fada0212507b2630edd8127d29101eceec9cc5bd33ff9733b0cc13fcc7e` | `af2c767d28ebbe8870eb4d5ef72e7a386d09bc4ecb1c62790810b4428643482b` | `847c86293c87a304259c82607e978a6b2967d58a67840d2fac3509294651a80d` |
| `region:main` | `10adc2dd14982b48a050cbd2e8f6301b3a70daf8d2431e221855ff463346dc3e` | `4feb4e3c4ba36383d5645568e8ff5013f522f6d5cba9dc2801d4150866c4dab6` | `04e15eeb42dfa83e3d7c4d4bdad32734ea74fbb232951766d2365ccbf6a1351d` | `f24d7ef284ed4a09f770c7d2caff2bdc65c2975b6f9dd9ba37388bf755fa872f` |
| `layout:docara.docs` | `30085b3df95c5ed9d04ef296917c44011877aae38648d65a96df83f864fc099d` | `f10509ec5a64a5212c5260ae3171772875299bc4fb099a1475d99e221739f32b` | `0830c0ede876394b5cb3ffd3491e885f9f31d3751dbf1ce9de03543e1fe9fd8d` | `00858ba5c20f5794d634c847ed3b135e3f8de3378883b721d6e0a578ec190be1` |

No external repository, site, merge, push, tag, release or deploy was changed.
