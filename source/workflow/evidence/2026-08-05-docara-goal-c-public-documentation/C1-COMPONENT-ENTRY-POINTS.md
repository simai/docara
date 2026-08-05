# C1 — six component entry points

Implementation checkpoint: pending commit at capture time; exact commit is the
commit containing this file.

## Outcome

- Six new physical owners exist under `content/ru/components/`.
- `atlas_index` is a build-owned typed derived view. It filters only the
  admitted `DesignAtlasService` result and never owns prose.
- Full build stores `.docara/design-atlas.json` with the exact Atlas fingerprint.
- An isolated page rebuild fails if its accepted full-build Atlas differs from
  current registries.
- Native Markdown guide maps all six enabled profile capabilities; raw HTML is
  explicitly fail-closed.
- Container guide names all machine child-contract fields and valid/invalid
  nesting outcomes.

## Focused verification

```text
PortableAtlasIndexHydrator + EffectiveComponentCatalog + ComponentIndex:
14 tests, 1129 assertions, PASS

Markdown/Atlas/Framework/project-demo focused matrix:
77 tests, 498 assertions, PASS

Disposable full build:
110 routes, 320 files, 220 HTML
Atlas entries: 44
Atlas fingerprint: 7ee7d11ba60c1ec0b1c86198868beed5bfe851211e33bf290bf5854dbf51dea8
Static verifier: 24,449 local references, broken=[]
```

Disposable build root: `/tmp/docara-goalc-c1h.XoEF7H` (ephemeral evidence,
not a source and not committed).

## Rollback

Revert the C1 commit. The preceding C0 workflow commit `d25e89d` remains the
recovery boundary; no existing route was moved or removed.
