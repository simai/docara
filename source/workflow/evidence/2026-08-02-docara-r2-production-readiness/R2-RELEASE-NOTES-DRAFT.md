# Docara 2.0.0-rc.2 release notes draft

Status: planned, not published

Candidate source: `56a2abf8bad05923f689141afc0bb045aa4d6734`

Docara 2 introduces one content-first publication path. Each public route has
one physical Markdown owner, shared locale UI labels live in
`content/<locale>/lang.json`, and full/single-page builds use the same typed
in-memory IR, renderer registry, Smart gateway and PageBuilder.

Highlights:

- deterministic portable Composer package and immutable Framework tuple;
- safe `init`, previewable/atomic `update` and exact rollback;
- Markdown front matter and explicit missing-page policy;
- 103-route Russian documentation site with full/single parity;
- removal of generated public prose, public language packs and parallel page
  publication paths;
- fail-closed diagnostics, path/source boundaries and artifact verification.

Known limits:

- the full documentation corpus is Russian; EN/AR fixtures prove the engine
  locale contract but are not complete translations;
- production deployment is a separate approved action;
- supported PHP contract is `^8.2`; exact R2 environment evidence is listed in
  the production-readiness packet.
