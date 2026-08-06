# Goal S1 integrated acceptance evidence

Status: executor evidence complete; independent audit pending

Exact product candidate:
`45276f63422e8b8465b33e415d3fc302dfeac570`

Entry boundary:
`8e4b71c58065a1f49382dd8077809363e0eed873`

## Runtime and security

- `SurfaceRuntimeTest`: 5 tests / 43 assertions; typed props, Atlas contract,
  capability-owned children and traversal/remote/symlink/hardlink/case
  negatives pass with zero outside mutation.
- focused catalog/build contour: 78 tests / 2,291 assertions.
- focused final Surface/build/docs contour: 57 tests / 1,111 assertions.
- broad structural scans find one renderer registry, one Smart Gateway, one
  LayoutComposer and one PageBuilder; the shared pipeline has no Surface
  component-ID dispatch.
- nested Surface/Hero/Showcase/Promo and invalid slot/count/order/depth are
  rejected before render. No arbitrary CSS/class/PHP/callback/template/path is
  accepted.

## Exact build and preview

Two independent full roots each contain 133 routes, 393 files and 266 HTML
files. Both trees and a representative single rebuild of
`/ru/components/surface/` have the complete-tree digest:

`129eef82bdcefc29174115cc51e9e2698cdf110ac8f66e6c67e36ea128547d42`

Static verification for each full root: 35,574 local references, `broken=[]`.
The isolated page preview is explicitly non-production
(`accepted_build_receipt=false`) and reports:

- HTML SHA-256 `8aa30fe84eed297315c2b247ded61bc070b858a0a7be72ae8f271597ca523754`;
- canonical SHA-256 `5a723e24dfdc61b2be43f0437f5d20923e5836f99aaae6886253f69c4f3dfbed`;
- published tree SHA-256
  `4581c69b0e41c7e68cb3b7e2e677e9b6f4b2a5787b4b8e7097e459062ec99129`;
- renderer path
  `markdown>typed-ir>registry>gateway>layout-composer>page-builder`.

The baseline and candidate Hero section are byte-identical at SHA-256
`5a5e8996aae6dad9ed410070d06cdc277c193e02dd659c00796818e711bd2a49`.

## Browser evidence

Exact candidate served from the disposable build root at
`http://127.0.0.1:8765`:

- landing 1920 light: outer Hero `0..1920`, inner `128..1792`, H1 and adjacent
  content start at `176`, horizontal overflow `0`;
- docs 1920 light: `main`, direct full Surface and its inner content all equal
  `488..1528`; sidebar `176..464` and outline `1552..1744` are not overlapped;
- docs 390 dark RTL: viewport and scroll width are both `390`; Surface remains
  bounded to mobile main;
- decorative image has `alt=""` and `aria-hidden="true"`;
- search keyboard/Escape returns focus to the trigger; console errors and
  warnings are zero.

Screenshot SHA-256:

- landing 1920 light:
  `bb0b26baa9b2786cbb53d3723140b0490249cda74fc57718664ab1dfcedd4cf0`;
- docs Surface 1920 light:
  `5f53a257611f1d4bf3d6534ba17ca50faef2be47664414eb61bb2f53a9625531`;
- docs Surface 390 dark RTL:
  `48ef3bf4672d938493b8a39f8caf545da2024bdc9e3722381c1202d6bc313e84`.

The RTL check explicitly applies `dir=rtl` to the exact production page; it
does not claim a complete translated documentation locale.

## Regression, package and consumer

- full PHPUnit on the exact product candidate with the accepted SF5 adapter:
  489 tests / 10,324 assertions; the final governance/context contour after
  the state-driven roadmap fixture update is 489 / 10,356;
- exact SF5 cross-host report: source `b3cdff87563ff78e7eddf044048a4b298fc69036`,
  HTML byte-equal at `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`,
  warnings `[]`, stderr empty;
- Pint and Composer strict validation pass; Composer audit reports zero
  advisories and zero abandoned packages (tool-owned PHP 8.4 deprecations are
  non-product output);
- two detached clean worktrees build byte-identical unpublished 866-file ZIPs:
  ZIP SHA-256
  `9754cbc0579fef70f92fb2b7749c31c9c5eca7a6c0ce67651542dfd71ce56eb1`,
  release-manifest file SHA-256
  `a24bc2721410dcd6c147e641fb61c85f9fc41ffffa2db63baf263f3f92458dbd`;
  both repository verifiers pass;
- one fresh Composer dist consumer has no package `.git` or `node_modules`,
  passes doctor/full/single/static, and preserves a 198-file ledger
  `6b6738dfa44b6cc18e14cc986006fe3cf613567a12281734541fdfd6e12aa34e`
  with 78 HTML / 3,931 references / `broken=[]`.

Package version `2.0.0-alpha1` and planned tag parameter
`v2.0.0-alpha1-s1` are test inputs only. No tag or release exists.

## Rollback and nonclaims

Rollback is a normal revert of S1 commits to entry boundary `8e4b71c…`; no
external repository or site was changed. There was no legacy Surface runtime
to delete. Goal S1 does not claim Hero background media, homepage art-direction
changes, S2/S3, interactive asset contribution from a nested project Smart
inside a documentation source example, release or deployment. Independent
acceptance is not claimed by this executor.
