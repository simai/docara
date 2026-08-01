# Batch 05: generic component-block registry and gateway

Date: 2026-08-01

Verdict: PASS

Parent SHA: `de272384c12d5d3d1d87f1a584dcaaa5a4a667a0`

Candidate binding: the commit that first contains this evidence file.

## Runtime path

```text
ComponentBlockNode
  -> DocumentRendererRegistry[component_block]
  -> ComponentBlockDocumentNodeRenderer
  -> SmartComponentGateway::renderComponentBlock()
  -> ContentComponentRenderer
  -> structural manifest + template
```

This extends the existing content mode of the same Smart gateway. It does not
add an Alert pipeline, renderer registry, PageBuilder or content registry.

`ContentComponentRenderer` shares alias/prop/default/enum/normalization logic
between inline and block components. Slot kind selects plain escaped text or a
validated document body. The manifest contains only machine structure; the
title and supporting text come from Markdown children.

## Parity and negative evidence

- info/default, clear/default, success/default, warning/outlined and
  danger/flat match the accepted legacy Alert artifact after normalizing its
  single trailing newline;
- role, icon, class, title and supporting HTML are unchanged;
- unknown prop, invalid enum, missing allowed heading and missing supporting
  content fail closed with the physical block opening location;
- registry provenance records canonical smart id, alias, `component_block`
  node type, definition/template and source span;
- declared component assets: none, matching the current route-local behavior.

## Verification

```text
php vendor/bin/phpunit tests/Unit/MarkdownCompilerTest.php \
  tests/Unit/PageBuilderTest.php tests/Unit/DocumentationContractTest.php
PASS — 20 tests, 1,178 assertions

php ../../docara build m3-gateway-full
PASS — 103 pages, 321 files

php ../../docara build m3-gateway-single --page=/ru/components/badge/
PASS — 1 selected page

diff -qr build_m3-gateway-full build_m3-gateway-single
PASS — no differences

php ../../docara verify-static build_m3-gateway-full
PASS — 206 HTML pages, 18,866 local references, 0 broken
```

- PHP lint, JSON parse, Pint and `git diff --check`: PASS;
- dependency/lock files: unchanged;
- public page content and language packs: unchanged;
- rollback: revert the single batch 05 checkpoint commit.

## Nonclaims

The Alert route is still generated at this checkpoint. No source ownership,
language-pack/allowlist reduction, browser parity or M3.2 completion is
claimed until batch 06 passes.
