# Batch 7 — live component catalogue plan

Date: 2026-07-19
Accepted base: `68a960ff1debde48664aa8541413dbef208612ee`
Status: `READY_WITH_PLAN`

## Product outcome

Authors receive one generated catalogue at `/components/catalog/` and one
generic detail route shape at `/components/catalog/<id>/`. The pages are
derived at build time from the Batch 6 EffectiveComponentCatalog plus exact
example fixtures. No client-side registry, fetch dependency or second
Framework registry is introduced.

The index remains compact and grouped. A detail page progressively reveals:

1. purpose and readiness;
2. live rendered example;
3. exact Markdown call syntax;
4. parameters, states and limitations;
5. source and Framework provenance.

Catalogue details participate in the existing search, breadcrumbs and
previous/next contracts. Individual details do not make the primary left tree
unmanageably long.

## Required component closure

Batch 6 has 11 supported records. Batch 7 adds `docara.columns`, producing 12
supported records after acceptance.

`docara.columns` is a typed portable layout recipe:

- one `:::columns` container;
- two to four regions;
- regions are separated by top-level thematic breaks (`---`);
- splitting uses parsed CommonMark block structure, never regular expressions
  over source text;
- nested portable/Framework directives are rejected;
- output is deterministic, responsive and composed from pinned Framework
  layout utilities;
- the requirement-only record is deleted and a new typed record is added;
  requirement state is never mutated into executable readiness in place.

## Test-first boundary

Required checks include:

- exact catalogue count, stable order, route uniqueness and deterministic
  output;
- one generic template rather than one hand-written page per record;
- every supported record has an executable fixture and rendered example;
- unsupported/requirement records show limitations and never execute;
- invalid fixture, missing parameter metadata and unknown renderer fail closed;
- two, three and four columns render; one, five, nested directives and malformed
  separators fail closed;
- generated catalogue pages enter search, breadcrumbs and previous/next;
- keyboard, 390/768/1440 responsive, light/dark and overflow browser matrix;
- complete exact-archive tester and Human-Centered Simplicity/source/security
  verdict before local publication.

## Boundaries

No Framework owner repository is changed. No public release, default-branch
mutation, repository retirement, runtime service or second content language is
part of this batch.
