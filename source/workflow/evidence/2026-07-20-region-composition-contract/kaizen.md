# Kaizen

Date: 2026-07-20

## Lessons

1. Layout structure and author presentation values must not share identical
   reset semantics. Registered `key` and complete regions are invariants;
   `max_width` and region overrides are author values.
2. A schema-valid Smart ID is not enough. Region, Smart and data binding must
   be checked together by runtime.
3. `enabled: false` should remove semantic markup, not leave empty `aside` or
   `footer` containers.
4. Default composition belongs in one resolver and is reused by portable
   configuration and direct compiler callers.
5. The best demonstration compares one configured page against an unchanged
   default page and verifies both DOM structures.
6. The repository should eventually provide a stable test wrapper selecting
   ServBay PHP and UTC; the Homebrew PHP link is not currently reliable.

## Documentation assessment

Audience paths affected:

- author: configuration, inheritance, regions demonstration;
- extension developer or AI: declarative pipeline and schema reference;
- maintainer: portable format and runtime restrictions.

Technical verifier: PASS. Paths, schema literals, error code, build commands,
DOM behavior and provenance claims were checked against live code/output.

Russian style review: PASS_WITH_NOTE. English contract literals remain where
they are exact identifiers; surrounding instructions use direct Russian.

## Remaining

- add a separately designed `docara.footer` only when real footer content and
  accessibility requirements are known;
- register another layout only through a separate owner-reviewed goal;
- switch the primary publisher only after full shell migration and visual
  acceptance.
