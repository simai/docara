# A4 — integrated executor acceptance

Date: 2026-08-04
Status: `PASS`
Exact Goal A product candidate: `8c04160ab50549b060fb933cf80f86193cd92113`
Baseline: `d748eca04cd09e79ed6e2079a56b077265bcf905`

This is executor evidence, not independent acceptance.

## Tests and static checks

- focused Binding/Design/Preview/QA/security: 76 tests / 490 assertions, PASS;
- permanent identity-dispatch guard: 5 tests / 56 assertions, PASS;
- full PHPUnit on the final product/governance state: 452 tests / 8,225
  assertions, PASS;
- exact SF5 cross-host: 1 test / 44 assertions, byte-identical, PASS;
- Pint, Composer validate strict and audit: PASS; Composer emitted only its
  known tool-owned PHP 8.4 deprecation notices;
- PHP lint: 340 files, PASS;
- tracked/project JSON: 597 files, PASS; YAML: 34 files, PASS;
- project context: generate/check PASS, issues `[]`;
- graph: 1 goal / 12 stages / 15 batches / 4 metrics / 8 mappings, warnings=0,
  blockers=0;
- `git diff --check`: PASS.

## Build and parity

Two independent full builds each produced 104 routes, 307 files and 208 HTML.
Their complete trees are byte-identical:

`8b7fdb611647e545c6dabe11ed9e31a43a655f36e87739be5fc44dddd6ca25f2`

Both static verifications checked 21,844 local references with `broken=[]`.
A selected `/ru/components/alert/` build preserved the same complete ledger.
Candidate Alert HTML is
`23f4f52e645e61060afd88abd36012c8566540e058923338b12380d0ec328e40`.

This exact-candidate ledger was refreshed after the final public-guide commit;
the reproducible commands and metadata revision proof are recorded in
`A6-EXACT-CANDIDATE-BUILD-REFRESH.md`. The earlier ledger
`2e1ecaa1da0d5d0303b65b450d8655e16992377c7f26055f7713a9afad5d9d42`
is historical pre-final-doc evidence only and is not evidence for the exact
Goal A product candidate.

Against the A1 default-parity build, normalizing only the content-addressed
search-index query left exactly five intended differences:

- the two updated public guides;
- the locale and root search indices derived from those guides;
- the internal resolved-page plan/provenance receipt.

Every other public HTML and asset is byte-identical. Normalized Alert HTML is
identical on both sides with SHA-256
`c74e0e8855a8cd7da8595d0ef111af40f2d0ad74e432c81054daa8cf2e21909e`.

## Architecture and deletion evidence

- `DeclarativePageCompiler::boundProps()` has zero references and is removed;
- active compiler/composer/preview/discovery sources contain none of the
  built-in binding or Smart IDs; permanent structural regression enforces it;
- concrete IDs exist only in provider-owned descriptors, package artifacts,
  fixtures, tests and documentation;
- one BindingRegistry was added for one responsibility; the accepted single
  SmartComponentGateway, NodeRendererRegistry, layout-composition path and
  PageBuilder remain unchanged as architectural owners;
- old short binding names remain only explicit package-internal storage aliases
  for byte-compatible built-in Section artifacts. They are not public authored
  IDs and are intentionally retained for rollback compatibility.

## Browser and security

The 24-scenario matrix and content-addressed files are indexed by
`A2-NAVIGATION-VARIANTS.md`. All capability, ownership, prop, schema,
traversal, symlink and collision negatives passed in focused/full tests.

## Rollback

The repository rollback boundary is the Goal A baseline. Each A0-A4 change is
a separate commit and no external site, release artifact or user content was
mutated.
