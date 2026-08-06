# S1-C1 pipeline/container correction — integrated evidence

Date: 2026-08-06
Status: `ready_for_independent_audit`
Exact product candidate: `80b8102632c922ec44d16947456babeab6d15e25`
Correction boundary: `f99ce6a653aeca9fc2ccf5434fc094b7cb8ca66e`
Rejected first candidate: `45276f63422e8b8465b33e415d3fc302dfeac570`

## Outcome binding

- `MarkdownCompiler` compiles variable-length container fences and admitted
  nested typed/Smart children once into `ContainerNode` IR.
- `DocumentRendererRegistry` recursively renders each child once and aggregates
  its component HTML, assets, hydration, provenance and Framework call receipt.
- `ContainerContractValidator` is the single registry-driven validator for
  slot, minimum/maximum children, order, depth and capability.
- the former Surface-only `extractSurfaceSmartChildren` reparse/render path and
  `surface-smart-*` surface have zero runtime references.
- file-backed invalid child/count/depth/order/fence/props diagnostics preserve
  the exact safe source path plus line and column.

The permanent real-project test builds the physical public Surface guide through
the docs/site `ProjectRuntime` and `PageBuilder`. Its nested
`project.product-configurator` is present once in IR and once in rendered
artifacts; its CSS and JavaScript are published once and its project provenance
and hydration remain attached.

## Contract and security matrix

Focused Surface/document/compiler/PageBuilder/documentation tests:
`48 tests / 2,256 assertions`, PASS. The complete focused correction contour
before the final prose-only browser warning fix was `98 tests / 883 assertions`,
PASS. Permanent tests accept Markdown, Grid, Card and project Smart children,
including exact bounds 1 and 64. They reject empty and 65 children, invalid
slot/order/depth, nested Surface, Hero/Showcase/Promo, shell/unsupported Smart,
malformed/unclosed/mismatched fences and invalid props with stable codes and
exact locations. Traversal, protocols, missing media, symlink escape, hardlink
and case mismatch remain fail-closed.

Zero-reference command:

```bash
rg -n 'extractSurfaceSmartChildren|surface-smart-' src tests resources docs/site stubs
```

Result: no matches.

## Exact verification

- full PHPUnit on ServBay PHP 8.4.20: `491 tests / 10,444 assertions`, PASS;
- Pint `--test`: PASS;
- Composer `validate --strict`: PASS; emitted only Composer-owned PHP 8.4
  deprecation notices;
- affected PHP lint, tracked JSON/YAML parse, graph/context and diff checks:
  PASS;
- exact SF5 cross-host: `1 test / 45 assertions`, HTML byte equality at
  `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`,
  warnings `[]`, stderr empty; report SHA-256
  `79372b7aa5decb9a1f12c07b5f02604ad83c9cedd75661890457d75761b521b4`.

## Public build determinism

Fresh exact-candidate roots `build_s1c1-final-a`, `build_s1c1-final-b` and
`build_s1c1-final-single` each contain 393 files. Both full builds produce 133
routes / 266 HTML; static verification checks 35,581 local references with
`broken=[]`. A selected rebuild of `/ru/components/surface/` preserves the
complete tree byte-for-byte.

The canonical algorithm is exactly
`sha256(sorted("<file-sha256>  <relative-path>\n"))`, matching
`scripts/atomic-static-cutover.php::treeDigest()`. All three roots equal:

`935fd289f9f9e4f95f010239f4897edf901d07b5de705a87ed10beca82192bda`.

The former reported `129eef82…` and the independently observed pre-correction
`51718be2…` are rejected historical evidence, not current candidate identities.

## Browser proof

The exact `build_s1c1-final-a` tree was served at disposable
`http://127.0.0.1:8876` only. Landing desktop LTR/light proves the Hero outer
rectangle `x=0,width=1906` while its parent content rectangle is
`x=121,width=1664`, with no horizontal overflow. The Surface guide proves
Surface `x=481,width=1040` equals the docs `main` rectangle.

The nested configurator is present once and its JavaScript is loaded once.
Selecting “Расширенная аналитика” changes the local total from `2 500 ₽` to
`3 700 ₽`. Desktop light/LTR, desktop dark/RTL and mobile 390x844 dark/RTL have
console errors=0, warnings=0 and overflow=false. Search Escape closes the dialog
and returns focus to the search trigger. Screenshot hashes:

- landing desktop light/LTR:
  `4c7adecd9ba69a79d24b5687f1a0a46336f709bfef25f391161e27304c845049`;
- Surface desktop light/LTR:
  `93646c969ea15320eb52d8121a8645310eb7e05e3fc8644c1668f2db4e26a7b7`;
- Surface desktop dark/RTL:
  `254d6ec8316dda2cc63b5e6c13ddd9a6c548ae12f7cb93470b72e3e5d006b63d`;
- Surface mobile dark/RTL:
  `61defadf31cd653e951fdfa122c3116d174b8b0e8718468fea1a6f7f278638e0`.

The landing `shell` fence was changed to the equivalent supported `bash`
language so the final exact candidate produces no Highlight.js warnings; it
does not change Hero or Surface semantics.

## Deterministic package and fresh consumer

Two independent `git clone --no-local` roots built the same unpublished
`2.0.0-alpha1` package from the exact candidate:

- ZIP SHA-256:
  `440931e0406b993bfa19daebc4d4f28508a8e0c2acb112721bc4e6d166fa00e9`;
- release manifest file SHA-256:
  `44b3f87bc82f1c6c23381058ff6c794b2484c39d56ca0620abe60334631cae97`;
- checksum file SHA-256:
  `46a823802699923cca6648ceb254d8c1e411efde8e8a403f80e143ac20cf6b50`;
- files: 869; both repository verifiers PASS.

The first verifier attempt used obsolete named arguments and returned usage;
the documented positional manifest command was then run in both clones and
passed. This was a command correction, not an artifact defect.

A fresh Composer dist consumer installed exact reference `80b8102…`, initialized
78 starter files, and passed doctor (Smart 17, Design 18, Binding 3, schemas 38),
39-page full build, selected Alert build, and static verification at 78 HTML /
3,931 references / `broken=[]`. Output contains 198 files and neither package
`.git` nor `node_modules`. Composer printed only tool-owned PHP 8.4 deprecations.

## Nonclaims and rollback

Goal S1 is executor-complete but not independently accepted. S2, Hero background
media, homepage art direction, release review, merge, push, tag, release,
deployment and external site/repository writes are not performed or claimed.
Rollback is revert of product commits `80b8102` then `e03d8bd`, returning to the
recorded correction boundary; disposable build/package/consumer/browser roots
are not source.
