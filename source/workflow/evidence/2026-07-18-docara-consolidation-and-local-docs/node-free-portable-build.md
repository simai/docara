# Node-free portable build proof

Date: 2026-07-18
Candidate: `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`
Disposable root: `/private/tmp/docara-nodefree-4a312c1.qe9Z5U`

## Boundary

The proof ran the Docara PHP entrypoint with `PATH=/usr/bin:/bin`. In that
environment `node`, `npm`, `yarn`, `pnpm`, and `bun` were each checked with
`/usr/bin/which`; every lookup exited `1` and none was resolvable. The
disposable site contained only Markdown, JSON, the Simai Framework lock, and
generated static output; it contained no package manifest or JavaScript build
configuration.

## Commands and outcome

```text
env PATH=/usr/bin:/bin php docara init --portable --no-interaction
exit 0: 10 portable scaffold files copied

env PATH=/usr/bin:/bin php docara build production
exit 0: 3 HTML pages generated

env PATH=/usr/bin:/bin php scripts/verify-static-build.php build_production
exit 0: 3 HTML pages, 7 local references, 0 broken
```

The production build and verifier were executed twice and both commands exited
`0` on each pass. Both content-tree hashes were:

```text
1f87d8a1d1c981cde386c8fbb896b68e1b82897cfce9606fae57f73479c5e15c
```

## Claim boundary

This proves the portable end-user build path is PHP-only for the bundled
starter. It does not claim that the maintainer asset-development path is
Node-free: maintainers intentionally use the frozen Yarn/Vite contract.
