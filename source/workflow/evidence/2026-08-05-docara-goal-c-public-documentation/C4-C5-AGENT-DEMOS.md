# C4-C5 — agent journey and useful extension demos

## C4

`/ru/development/agent-journey/` documents discover → plan → preview → dry-run
→ hash-bound apply → validate. Human, JSON and optional MCP are explicitly
mapped to the same services; read-only default, `--allow-writes`, project-root
containment, stale-plan rejection and recovery are visible.

## C5

The docs project now consumes byte-identical project demo artifacts from the
accepted starter surface under project-owned `smart/` and `design/` roots. The
new `/ru/project-demos/` route renders install builder, product configurator and
footer through the existing project provider/Gateway/LayoutComposer/PageBuilder.
`/ru/examples/extensions/` projects Framework/project support from Atlas and
preserves the ui.list-item text-only and related-surface nonclaims.

## Focused and build evidence

```text
Goal C docs + project demos + Framework wave + discovery/MCP:
24 tests, 331 assertions, PASS

Disposable full build:
132 routes, 391 files, 264 HTML
static references: 35,044, broken=[]
project Atlas entries: 6
Atlas fingerprint: cfbff8325d7793d729ae9be19559af74606787bd1deebc7c85e86422c67aa071
project.install-builder, project.product-configurator, project.footer-links: rendered
```

Disposable build root: `/tmp/docara-goalc-c5.gseYWy` (ephemeral evidence only).

Rollback: revert the C4-C5 commit. Accepted package/Framework packets and engine
runtime remain unchanged; no external repository or site was written.
