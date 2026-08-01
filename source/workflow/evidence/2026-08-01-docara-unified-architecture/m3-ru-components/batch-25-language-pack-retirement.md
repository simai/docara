# Batch 25 — Russian public language-pack retirement

Date: 2026-08-02

Parent: `fc3bd32c3453d863e278c7a61f07e794ccaeb1c7`

Candidate: commit containing this record

Verdict: PASS

## Result

- `resources/language-packs/ru.json` no longer contains public page prose,
  component presentation records, or reusable site UI copy;
- reusable declarative-example labels moved to the sole public locale source,
  `docs/site/content/ru/lang.json`;
- the `/ru/components/` legacy allowlist entry and all eight remaining
  zero-reference localized catalog examples were retired;
- the portable starter now owns its component index, Badge page, and Russian UI
  labels physically, so `docara init` does not depend on package RU page prose;
- a full/single build with all supported authored component owners exits the
  catalog projector before translator, presentation, and example projection;
- legacy generated projections remain covered in English for non-migrated
  locales and rollback compatibility. No second renderer or PageBuilder exists.

## Zero-reference and boundary proof

Before deletion, repository search found no references to any of the eight
localized example paths outside `resources/language-packs/ru.json`. After the
change:

```text
resources/language-packs/ru.json: messages=0, components=absent
resources/legacy-public-source-allowlist.json: /ru/components/ entry=absent
resources/component-catalog/examples/*.ru.md: 0 files
```

The exact old hashes, replacement owners and rollback command are recorded in
`old-to-new-map.json`. Package-owned English catalog projections and independent
CLI/system messages were not removed or mixed into site content.

## Product parity

```text
php ../../docara build m3-b25-full
PASS — 103 selected pages

php ../../docara verify-static build_m3-b25-full
PASS — 206 HTML pages, 18,942 local references, 0 broken

php ../../docara build m3-b25-index --page=/ru/components/
PASS — 1 selected page

php ../../docara build m3-b25-badge --page=/ru/components/badge/
PASS — 1 selected page
```

- the complete Batch 25 output tree is byte-identical to Batch 21;
- component index full/single SHA-256:
  `38ef5ff864ade3bd043d9bb245a939205cb9698fd12e13791188afd889fae6b7`;
- Badge full/single SHA-256:
  `553aff3e1a0135fe244ee6f7d1c93f4bdf859eba550ebca41964fda558747f77`;
- declarative examples index SHA-256 remains
  `27de9132318fdd660f1b9e58191c56930c6bb8f60894670792441e35b693bcc5`.

## Tests and hygiene

```text
focused language/catalog/starter/boundary suite
PASS — 55 tests, 2,528 assertions, 0 warnings

full PHPUnit suite
PASS — 377 tests, 6,530 assertions, 0 warnings

git diff --check
PASS
```

The focused boundary test also replaces the package RU pack with forbidden
sentinel prose and proves that the component index, Badge page and examples
hashes do not change.

## Rollback

Revert the candidate commit. Deleted bytes remain reconstructable from parent
`fc3bd32c` and their SHA-256 values in `old-to-new-map.json`.
