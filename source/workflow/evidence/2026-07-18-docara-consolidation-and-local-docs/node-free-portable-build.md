# Node-free portable build proof

Date: 2026-07-18
Candidate state: final pre-commit Docara integration tree
Disposable root: `/private/tmp/docara-nodefree-final.gEqMaj`

## Boundary

The proof ran the Docara PHP entrypoint with `PATH=/usr/bin:/bin`. In that
environment `node`, `npm`, `yarn`, and `pnpm` were each checked with
`/usr/bin/which` and were not resolvable. The
disposable site contained only Markdown, JSON, the Simai Framework lock, and
generated static output; it contained no package manifest or JavaScript build
configuration.

## Commands and outcome

```text
env PATH=/usr/bin:/bin php docara init --portable --no-interaction
PASS: 10 portable scaffold files copied

env PATH=/usr/bin:/bin php docara build production
PASS: 3 HTML pages generated

env PATH=/usr/bin:/bin php scripts/verify-static-build.php build_production
PASS: 3 HTML pages, 7 local references, 0 broken
```

The production build was executed twice. Both content-tree hashes were:

```text
1f87d8a1d1c981cde386c8fbb896b68e1b82897cfce9606fae57f73479c5e15c
```

## Claim boundary

This proves the portable end-user build path is PHP-only for the bundled
starter. It does not claim that the maintainer asset-development path is
Node-free: maintainers intentionally use the frozen Yarn/Vite contract.
