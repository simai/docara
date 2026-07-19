# Batch 7 — exact Human-Centered Simplicity/source/security verdict

Date: 2026-07-19
Base: `9048e38eb045a4b829197c6f6ac7339e603d0fb1`
Candidate: `a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
Tree: `ec09ea5249a43c712729cbb74ab03e736987a353`
Verdict: `PASS`

## Complete-diff inventory

All 78 files in the exact base-to-candidate diff were reviewed:

- documentation: 3;
- component catalogue: 42;
- portable router and schemas: 3;
- static verifier: 1;
- workflow/evidence: 4;
- product PHP: 14;
- starter stubs: 2;
- tests: 9.

No changed file was excluded from the judgement.

## Product and simplicity judgement

- one effective catalogue remains the source: generated pages consume
  `docara.effective_component_catalog.v1` directly;
- Russian presentation is co-located under `presentation.ru` in the same
  canonical record, not copied to a second registry;
- JSON owns facts and limitations, one generic projector owns index/detail
  composition, and the existing HTML renderer owns the page shell;
- generated pages inherit layout, search, reading, TOC and navigation; details
  stay out of the primary left tree while retaining breadcrumbs, TOC and
  previous/next;
- full-surface links, native disclosures, focus-visible styles and a local
  overflow wrapper preserve simple semantic interaction;
- the portable result remains PHP-built and fully static.

Protective complexity is justified by exact example/asset confinement,
schemas, lifecycle gates, deterministic receipts and independent static
reconstruction. It prevents false readiness and artifact drift rather than
adding author-facing concepts.

## Framework and supply chain

- Core and Smart revisions remain exact at `7e836d8a…` and `dd786bba…`;
- no Framework lock, manifest or owner asset was changed;
- no consumed `main`, `master` or `latest` reference exists;
- layout and overflow classes used by the projector exist in the exact pinned
  `utility.full.css`;
- only bounded consumer narrowing was added;
- canonical-registry, all-components, production-readiness and public-release
  nonclaims remain false.

## Fail-closed and security review

- supported entries require an executable demo; unavailable entries require a
  complete non-executable gap;
- example and asset paths reject traversal, symlinks, hardlinks, malformed
  UTF-8 and containment escape;
- the receipt binds catalogue, source examples, rendered fragments, contract
  DOM and exact inventory;
- the static verifier reconstructs the trusted catalogue and rejects missing,
  extra, stale, split, hash and DOM drift;
- failed or declined builds stop `serve`;
- host/port inputs are validated, shell arguments are escaped, traversal is
  denied and the static router never executes PHP.

## Independent checks

- targeted PHPUnit: 200 tests, 2416 assertions, PASS;
- production build: PASS;
- static verifier: 60 HTML pages, 6477 local references, zero broken;
- 22 changed JSON files parse: PASS;
- 25 changed PHP files lint: PASS;
- `git diff --check`: PASS.

## Bounded limitations

- `ui.alert:type=success` is correctly excluded because of the exact pinned
  stylesheet defect;
- enhanced code/copy remains unavailable until a pinned accessibility and
  localization contract exists;
- the generic projector is now 952 lines and may later be split into
  presentation helpers after the product contract stabilizes;
- binding the developer preview to an external interface can expose
  diagnostic `.docara` receipts and therefore remains developer-only.

None of these limitations blocks the current authoring job or exact candidate.
This verdict does not claim independent tester acceptance, publication,
release or completion of the wider Goal.
