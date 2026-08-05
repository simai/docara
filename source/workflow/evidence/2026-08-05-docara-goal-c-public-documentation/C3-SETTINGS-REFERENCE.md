# C3 — settings guides and schema-derived reference

## Outcome

- `/ru/settings/` plus all 13 section-10 task routes have physical Markdown owners.
- `schema_reference` is a typed build-owned derived view over exact package
  schemas. It renders field path, scope, required state, type, declared default,
  validation keywords and exact schema provenance pointer.
- Missing schema defaults are labeled honestly as inheritance/runtime-derived;
  prose does not invent a second default contract.
- Full builds publish `.docara/schema-reference.json`; isolated rebuild rejects
  a changed schema receipt and requires a fresh full build.

## Evidence

```text
PortableSchemaReferenceProjector + Goal C docs + catalog:
19 tests, 1578 assertions, PASS

Disposable full build:
129 routes, 359 files, 258 HTML
static references: 33,464, broken=[]
schema receipt sources: 5
schema receipt SHA-256: 42f58acb61573a0c278c6fba7ab093998dc0e22be2c6c149c6e836817f5d38e8
```

Disposable build root: `/tmp/docara-goalc-c3.NpYkrW` (ephemeral evidence only).

Rollback: revert the C3 commit. Existing settings runtime and old authoring
routes are unchanged; no redirect is required.

