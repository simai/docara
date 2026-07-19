# Batch 5 code, security and contract precommit review

Date: 2026-07-19
Input: mutable worktree
Verdict: `PASS`

## Independent findings and corrections

The first review attempt found two real P2 contract gaps before any candidate
was committed:

1. `:::features` accepted arbitrary block composition inside a list item,
   including headings, fenced code, multiple paragraphs and image-only
   content. That contradicted the typed-recipe decision and could act as an
   untyped layout escape hatch.
2. The shared directive scanner could attribute a portable marker-limit
   overflow to the Framework family because its preliminary marker count was
   not family-specific.

Both were corrected and independently retested:

- every feature item is exactly one Paragraph;
- the inline allowlist is Text, Code, Emphasis, Strong, Link, Newline and
  Strikethrough;
- Image, HTML and all block-level or multi-paragraph composition fail closed;
- CTA label content has a separate bounded textual allowlist and rejects
  Image, inline Code and HTML;
- visible-text validation requires a Unicode Letter, Number, Punctuation or
  Symbol, rejecting separator/control/format/combining/variation-only labels;
- portable and Framework marker limits are counted separately and return
  `MARKDOWN_BLOCK_LIMIT_EXCEEDED` and
  `FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED` respectively.

## Final independent verdict

The reviewer reran direct positive and negative security/limit repros and
returned `PASS`:

- manual security/limit matrix: 15/15 PASS;
- current focused unit slice: 69 tests, 256 assertions — PASS;
- P1/P2 findings remaining: zero;
- Pint and `git diff --check`: PASS;
- starter and documentation use the same pinned immutable Framework pair;
- Framework repositories and revisions are unchanged;
- no release or production-readiness claim is present.

The root sequential verification after all corrections passed:

- focused product slice: 101 tests, 768 assertions;
- complete suite: 468 tests, 2,435 assertions;
- Composer strict validation, Pint, PHP syntax and `git diff --check`;
- 47-page static build with 4,931 checked local references and zero broken.

This is a mutable precommit review. It does not accept an immutable candidate
or authorize local publication.
