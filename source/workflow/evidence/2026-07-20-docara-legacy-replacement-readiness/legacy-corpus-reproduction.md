# Legacy corpus reproduction

Date: 2026-07-20
Mode: read-only

## Served HTML tree

Source:

`/Users/rim/Sites/docara-legacy.test`

Normalization:

1. find every regular `*.html`;
2. make its path relative to the served root;
3. map root `index.html` to `/`;
4. map every other `*/index.html` to `/*/`;
5. sort UTF-8 route strings by code point;
6. join with LF and include a final LF;
7. calculate SHA-256.

Result:

- route count: `48`;
- canonical SHA-256:
  `b56d73df2da3059fbca3808124c81baade25f09513cd7f90f4f053fd7ca2fcfa`.

## Retained Markdown tree

Source:

`/private/tmp/docara-main-legacy-build/stubs/site/source/docs/en`

Normalization:

1. find every regular `*.md`;
2. remove the source root and `.md`;
3. map root `index.md` to `/en/`;
4. for every other file, start with `/en/<relative-without-extension>/`;
5. if the final filename equals its parent directory name, remove that
   duplicate final segment: for example, `collections/collections.md` becomes
   `/en/collections/`;
6. preserve every other final filename:
   `collections/collections-categories.md` becomes
   `/en/collections/collections-categories/`;
7. use the same UTF-8 sort/LF/SHA-256 procedure.

Result:

- route count: `47`;
- canonical SHA-256:
  `e57931bbec8b47119a9cbd799538f0275014a7381650b4e24c8293f1f9e0f9c9`.

## Reconciliation

The 48 served routes are exactly `/` plus the 47 source-derived English
routes after the same-name terminal-segment collapse above. Set comparison
between the transformed source routes, served routes and
`redirect-corpus.json` has:

- missing routes: `0`;
- extra routes: `0`;
- duplicate decisions: `0`.

The path
`/en/content-translation-engine/markdown‑aware-translation/` retains U+2011
and is compared as UTF-8 without ASCII substitution.
