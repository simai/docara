# Rendering pipeline

Docara has one rendering pipeline:

```text
Markdown + inherited JSON settings
→ validated page plan
→ parsed content and component nodes
→ layout and region composition
→ registered templates and Smart renderers
→ static HTML and immutable assets
→ receipts and static verification
```

Content, configuration, composition and presentation remain separate. Markdown
contains authored text and component calls. JSON selects layouts, regions,
presets and component parameters. Registered templates own HTML. The builder
only orchestrates validated objects and deterministic publication.

Product-owned `docara.*` Smart components remain in Docara. Framework-owned
`ui.*` components are admitted through the pinned Framework contract. Both use
the same manifest and prop-validation rules.

There is no alternate page renderer or runtime mode. Development inspection is
provided by tests, receipts and the generated component catalogue rather than a
second preview site.
