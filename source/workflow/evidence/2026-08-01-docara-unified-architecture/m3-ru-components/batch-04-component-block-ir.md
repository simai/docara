# Batch 04: generic component-block IR

Date: 2026-08-01

Verdict: PASS

Parent SHA: `185db8b145dc3ed8f4b6876d37c07a850a3f738b`

Candidate binding: the commit that first contains this evidence file.

## Contract

`ComponentBlockNode` is the only typed block-component node. It records:

- author alias and canonical component id;
- parsed props;
- raw fenced source and inner Markdown body;
- ordered typed child nodes;
- physical file, opening line/column and closing line.

`MarkdownCompiler` resolves fenced aliases through the existing
`ComponentAliasRegistry`. The parser is generic; no Alert-specific class,
branch, compiler or page pipeline exists.

The current Alert example source compiles to five `component_block` nodes,
five headings and five supporting paragraphs. Its five HTML comments are
preserved as separate source paragraphs and are not component props/prose
configuration.

## Typed snapshot

The focused snapshot asserts the complete `toArray()` form of a warning /
outlined block, including:

- `alert -> docara.alert`;
- exact props;
- block span `3:1–7`;
- heading span `4:1–4`;
- paragraph span `6:1–6`;
- ordered typed children.

## Negative matrix

| Case | Error | Location |
| --- | --- | --- |
| unknown alias | `DOCUMENT_COMPONENT_ALIAS_UNKNOWN` | physical opening |
| no closing fence | `DOCUMENT_COMPONENT_BLOCK_UNCLOSED` | physical opening |
| empty body | `DOCUMENT_COMPONENT_BLOCK_CONTENT_REQUIRED` | physical opening |
| nested component fence | `DOCUMENT_COMPONENT_BLOCK_NESTED_FORBIDDEN` | nested opening |
| no registered renderer | `DOCUMENT_IR_RENDERER_UNKNOWN` | physical opening |

## Verification

```text
php vendor/bin/phpunit tests/Unit/MarkdownCompilerTest.php
PASS — 5 tests, 28 assertions

php vendor/bin/phpunit tests/Unit/PageBuilderTest.php tests/Unit/MarkdownCompilerTest.php
PASS — 6 tests, 96 assertions
```

- PHP lint: PASS;
- Pint: PASS after formatting;
- `git diff --check`: PASS;
- public content, resources, dependencies and locks: unchanged;
- rollback: revert the single batch 04 checkpoint commit.

## Nonclaims

No block renderer/template, Alert page migration, runtime parity or M3.2 PASS
is claimed. A block deliberately fails with `DOCUMENT_IR_RENDERER_UNKNOWN`
until batch 05 registers the shared renderer and gateway path.
