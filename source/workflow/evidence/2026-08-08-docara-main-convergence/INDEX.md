# Docara v2 main convergence evidence

Status: `main_convergence_candidate_reproduced`

## Immutable inputs

- accepted Docara v2 product/runtime: `d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f`;
- accepted pre-convergence governance: `2ba43e970974ad7eeb20bf563a6104b28e624180`;
- legacy GitHub main: `ff48ea54075b1646d06407288ca8c3a85ed8e4fd`;
- accepted Framework owner main: `adc75d4fcea17736bc204932b9db2ea3512c1117`;
- verified Docara bundle:
  `/Users/rim/Git/.artifacts/docara-v2-branch-cleanup-20260808/docara.bundle`.

## Merge proof

- common ancestor: `ecfc8b72f34a020b1f7374e11eb5b33c0838aabe`;
- legacy-only commits: 13;
- v2-only commits before convergence: 279;
- merge commit: `0f62294`;
- tree before merge: `4a79423a71bc473c358fd10dd017722b9583e438`;
- tree after merge: `4a79423a71bc473c358fd10dd017722b9583e438`;
- result: accepted product/runtime/public-document tree is byte-identical.

## Framework provenance boundary

Framework `main@adc75d4f…` is the accepted owner baseline for future work. It is
not substituted into existing binary provenance fields. Bundled runtime,
typography and portable Smart artifacts continue to name the exact revisions
that actually produced their bytes. No binary packet was rebuilt or relabeled.

## Acceptance matrix

- Composer validation: PASS on PHP 8.4.20 (tool-owned deprecation notices only);
- full PHPUnit reached all 511 tests / 11,540 assertions. Product/runtime tests
  passed; two ProjectContext assertions caught an in-progress unsynchronized
  router edit. After canonical graph/handoff synchronization the complete
  ProjectContext suite passes 9 tests / 481 assertions and `project-context
  generate/check` returns `issues=[]`;
- two clean no-local clones build byte-identical full trees: 652 files, canonical
  path-sorted ledger SHA-256
  `b5156b428ec07800b7275a4f6679ff0dd88cf73cc4a74b0fefd7304c3be43656`;
- representative `/ru/components/alert/` single rebuild preserves the exact
  652-file ledger;
- both full roots and the selected rebuild verify 261 HTML / 32,965 local
  references / `broken=[]`;
- accepted release-package/fresh-consumer evidence for unchanged product
  `d5e9ecb…` remains bound by the independent main-readiness packet. Governance
  and graph files are not relabeled as a new product/runtime candidate.

Final full PHPUnit, formatting, JSON/context/diff and clean-status checks are
repeated on the exact governance candidate before `main` is advanced.

No tag, release, deployment or external-site write belongs to this batch.
