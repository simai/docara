# Docara v2 main convergence evidence

Status: `integrated_retest_in_progress`

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

Composer validation, full PHPUnit, project-context/graph checks, deterministic
full/full/single builds, static verification, package/fresh consumer and clean
tracked status are recorded here before `main` is advanced.

No tag, release, deployment or external-site write belongs to this batch.
