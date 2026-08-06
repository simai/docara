# S1-C2 relative-depth contract correction — integrated evidence

Date: 2026-08-06
Status: `ready_for_independent_audit`
Exact product candidate: `ac53ea4d372a47dc8278b595accca9e7b85c66a3`
Entry governance boundary: `5c3a181ff1f641bd239eedc3ef62d39c469015fd`
Rejected S1-C1 candidate: `80b8102632c922ec44d16947456babeab6d15e25`

## Reproduced RED and corrected contract

The permanent PageBuilder regression uses the exact canonical source:

```markdown
::::::surface
:::::grid {columns=1}
::::card
Body.
::::
:::::
::::::
```

On `80b8102…` it failed with
`DOCUMENT_CONTAINER_DEPTH_EXCEEDED` at
`content/ru/components/surface-contract.md:3:1`. On the exact candidate it
produces nested typed IR `surface -> grid -> card` and renders all three nodes.

`max_depth` now has one schema/Atlas/runtime meaning:
`relative_subtree_root_level_1`. Every container is level 1 of its own subtree.
Surface therefore sees depth 3/3, while Grid independently sees depth 2/2. A
registry fixture lowering Grid to `max_depth=1` rejects Card at the same exact
file/line/column. Global directive, marker and resource limits are unchanged.

The validator resolves allowed Smart capabilities from the current parent
descriptor through `TypedComponentDefinitionRepository`; neither validator nor
direct renderer contains a component-ID, namespace or fixed
`content.embeddable` dispatch. The same admission semantics serve direct
rendering and the production MarkdownCompiler/PageBuilder path.

## Test and validation results

- focused Surface/compiler/Preview/catalog/docs contour:
  `108 tests / 1,959 assertions`, PASS;
- full PHPUnit on ServBay PHP 8.4.20:
  `493 tests / 10,465 assertions`, PASS;
- exact SF5 cross-host with the accepted owner checkout:
  `1 test / 44 assertions`, PASS;
- Pint `--test`, Composer `validate --strict`, Composer audit, PHP lint for 402
  files, tracked JSON parse for 544 files and YAML syntax parse for 34 files:
  PASS;
- Composer emitted only its pre-existing PHP 8.4 deprecation notices. An
  attempted `audit --locked` correctly rejected the repository because it does
  not own a Composer lock; the applicable unlocked audit reported no security
  advisories.
- candidate and worktree `git diff --check`: PASS.

Existing permanent tests retain exact 1/64 acceptance and empty/65 rejection,
slot/order/fence/props/capability/path/symlink/hardlink/case negatives, plus the
Surface -> project.product-configurator exactly-once asset, hydration and
provenance proof. Searches for `extractSurfaceSmartChildren` and
`surface-smart-` still return no runtime references.

## Build, preview and static verification

Fresh roots `build_build_s1c2-final-a`, `build_build_s1c2-final-b` and
`build_build_s1c2-final-single` each contain 393 files and 266 HTML files. Both
full builds have 133 routes. Static verification of each full build checks
35,581 local references with `broken=[]`; the selected rebuild of
`/ru/components/surface/` preserves the complete tree byte-for-byte.

The canonical digest algorithm is
`sha256(sorted("<file-sha256>  <relative-path>\n"))`. All three roots equal:

`90bf637819d38456d745578011d2bf4f1e6e5cb6b349ebc230ee569da91f8b26`.

The S1-C1 ledger `935fd289…`, first-candidate `129eef82…` and independently
observed pre-correction `51718be2…` are historical/rejected, not current proof.

A fresh disposable project full build followed by
`docara preview page --page=/ru/components/surface/ --json` reports the accepted
production path
`markdown>typed-ir>registry>gateway>layout-composer>page-builder`, target-only
dependency closure, `accepted_build_receipt=false`, 120 published preview files,
page HTML SHA-256
`2a955687fc2866620bf6931249ac0e28153d123a8f3ce6a993bf72c8585a8b59` and
canonical preview SHA-256
`8418b569957584965130c3e161084ddf8e137a245b8a6cf0e794760aa8c8d2bc`.

## Browser proof

The exact `build_build_s1c2-final-a` tree was served only at disposable
`http://127.0.0.1:8878`.

- Surface desktop light/LTR: Surface width equals docs `main` width, nested
  configurator count=1, overflow=false, reduced motion honoured;
- the admitted checkbox changes the local total from `2 500 ₽` to `3 700 ₽`;
  its JavaScript asset has one exact published URL;
- mobile 390x844 dark/RTL: overflow=false, console errors/warnings=0;
- search Escape closes the dialog and returns focus to the labelled trigger;
- accessibility smoke: one H1, zero unnamed buttons, decorative Surface media
  has `aria-hidden=true`, 324 focusable controls/links discovered;
- landing 1920 light/LTR: Hero rectangle is `x=0,width=1906`, its parent content
  is `x=121,width=1664`, and overflow=false. Existing Hero markup/semantics were
  not changed by S1-C2.

Screenshot SHA-256:

- landing desktop light/LTR:
  `c92a36be4ce61727a738c1896a6d9053b38466cfa23869c37cea80df90562f84`;
- Surface desktop light/LTR:
  `e5af0f3d391ea50df148f8c65b72fa7b93e36759b91532212a73f60c339b4bbd`;
- Surface mobile dark/RTL:
  `482617b424af9a74adee6b16fa3673d33e68f6193019e75e8a8802997e7b7a3e`.

## Package and fresh consumer

Two independent `git clone --no-local` roots built byte-identical unpublished
`2.0.0-alpha1` packages from the exact candidate:

- ZIP SHA-256:
  `3433618e283d1e3f764abbfcec6d689f640ed7f6fa93426fdcecd6cf5f1e21cb`;
- release manifest file SHA-256:
  `562a935e40e021b67aa768320eabbfcdc20606e09a8fd73b23f25c21b2f15ced`;
- checksum file SHA-256:
  `8c7e4024741a14d4eb1995fe13a64c2a8420af8a4b44bd28e47df28c27180a62`;
- files: 869; both repository verifiers PASS.

A fresh Composer dist consumer installed the exact ZIP from a package
repository, initialized 78 starter files, passed doctor (Smart 17, Design 18,
Binding 3, schemas 38), built 39 routes, passed selected Alert equality and
static verification at 78 HTML / 3,931 references / `broken=[]`. Its 198-file
tree digest is
`a92a2d63520c3cd33205c989c0606af44c930b53ca0868d4f0a494d6bcde4314`;
package `.git` and `node_modules` are absent.

## Rollback and nonclaims

Revert the governance commit and then product commit `ac53ea4…`; revert the
workflow checkpoint `215a9f0…` only if the historical correction record itself
must be removed. This returns to governance boundary `5c3a181…`. Disposable
build/package/consumer/preview/browser roots are not source.

Goal S1 is executor-complete and ready for a new independent audit, not
self-accepted. S2, Hero background media, homepage art direction, release
review, merge, push, tag, release, deploy and external repository/site writes
were not performed or claimed.
