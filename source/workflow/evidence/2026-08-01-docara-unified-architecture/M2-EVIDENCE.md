# M2 badge vertical-slice evidence

Date: 2026-08-01

Status: PASS for the bounded `components/badge` vertical slice

## Revision binding

- branch: `codex/docara-unified-architecture`;
- exact base revision: `b45896ea9284bb4fb2090fcacac7681c49e00aa5`;
- candidate revision: the commit containing this evidence file;
- rollback boundary: revert that single candidate commit;
- release, production, default-branch merge, tag and deploy: not claimed and not performed.

## Implemented target slice

- `MarkdownCompiler` compiles
  `content/ru/components/badge.md` into a typed in-memory `DocumentIr`;
- the IR contains 35 nodes: 4 headings, 4 paragraphs, 3 tables,
  4 code blocks, 4 examples and 16 component calls;
- every node has physical file, line, column and end-line provenance; the first
  badge call is at line 8, column 1 and the last is at line 75, column 1;
- the test-only snapshot is
  `tests/fixtures/document-ir/badge.json`, SHA-256
  `d5750dbc5af48ad77a63797b7978d58da993b99d05cac24861721a43562eab8a`;
- `badge` resolves through `ComponentAliasRegistry` to `docara.badge`;
- all 16 calls pass through `DocumentRendererRegistry` and the content mode of
  the existing `SmartComponentGateway`; all return provenance and no assets;
- `InlineComponentRenderer::badge` no longer exists;
- `PageBuilder` is the only authored-page build seam used by
  `PortableSiteBuilder`; full and `--page` modes differ only in route selection;
- the badge target branch produces `PageBuilderResult` with IR and render
  artifacts in memory. Other routes remain on the retained rollback adapter.

## Fail-closed coverage

Focused tests prove that unknown alias, prop, slot and node types fail with the
physical Markdown source location. They also prove the exact registry type set,
the alias mapping, absence of the old hard-coded badge method, 16 gateway
artifacts and exact content HTML.

Command:

```text
/Applications/ServBay/package/php/8.4/current/bin/php vendor/bin/phpunit tests/Unit/PortableMarkdownRendererTest.php tests/Unit/MarkdownCompilerTest.php tests/Unit/PageBuilderTest.php
```

Result: PASS, 58 tests, 368 assertions.

Full PHPUnit result on PHP 8.4.20: PASS, 359 tests, 7350 assertions.

Changed PHP formatter check with the explicit PHP 8.4 runtime: PASS.

## Build and parity evidence

Commands were run in the disposable runtime
`/tmp/docara-contract-checkpoint.ffpiVM` with the candidate sources copied in:

```text
php ../../docara build m2
php ../../docara verify-static m2
php ../../docara build m2 --page=/ru/components/badge/
```

Results:

- full build: 103 pages, 321 files;
- static verification: 206 HTML documents, 18,866 local references,
  0 broken references;
- `diff -qr build_m1b build_m2`: no differences across the complete tree;
- badge HTML SHA-256:
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- direct `PageBuilderResult::contentHtml` SHA-256:
  `e4f3670e53b1da50fc9cf08268dc6c40c08c05dc9168643d9e8d371d0be7318f`;
- the isolated build selected one page and left badge HTML and the complete
  build tree byte-identical to the preceding full build;
- no `*document*ir*` or `.jsonl` file exists in the 321-file public output.

## Browser evidence

The built page was served from the disposable `build_m2` directory and checked
with a real Chromium session. The accessible snapshot contains all 16 badge
instances, four example tabsets, three tables, headings, breadcrumbs, page
transitions and the table of contents. Console result: 0 errors, 0 warnings.

Full-page captures:

- `output/playwright/m2-badge/desktop-light.png` —
  `e081968a9fc4e292a8428800f53411a680f515bda3d725ee8eca16256745ae11`;
- `output/playwright/m2-badge/desktop-dark.png` —
  `07e29c6027ac7f8c9bed2fafe301e9a23dedd51cad938a0ff1f2588e1ca81ecc`;
- `output/playwright/m2-badge/mobile-light.png` —
  `0b28d0bed85b74be3e1cdaf11c2627a84ad2bae4776d8f2b1ed00203a11da96f`;
- `output/playwright/m2-badge/mobile-dark.png` —
  `c23c1fe4eb7a7b490a0506703b744c6ff0638a62316298e91a5b4692395d20e7`.

Visual result: PASS for Russian LTR at 1440x1000 and 390x844 in light and dark
themes. RTL remains a later locale-wide acceptance gate and is not claimed by
this Russian-only slice.

## Graph and handoff verification

- all graph JSON files parse: PASS;
- official project graph validator: PASS, 1 goal, 6 stages, 7 batches,
  4 metrics, 6 implementation mappings, 0 warnings, 0 blockers;
- graph file refs and Markdown anchors: PASS;
- changed local Markdown links: PASS;
- all 6 implementation mappings have code, test, evidence and deletion gates:
  PASS;
- `STATUS.yaml`: PASS;
- `git diff --check`: PASS.

## Gate verdict and nonclaims

`docara.gate.vertical_slice` passes for the bounded badge route. This proves the
target IR, registry, gateway and PageBuilder shape without claiming global
source ownership or migration coverage. The 44 generated legacy routes and all
non-badge rendering paths remain available for parity and rollback; no legacy
path was deleted.
