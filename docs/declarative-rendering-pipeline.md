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
provided by tests, receipts and the machine-readable component inventory rather
than a second preview site or generated public page path.

## Composition Recipe snapshot

The optional file-backed Recipe adapter assembles an approved Recipe with the
pinned SIMAI Framework resolver. It stores the resulting Document, HTML and
dependency receipt as one immutable snapshot under the project-owned `.docara`
directory. The active pointer changes only when its expected revision still
matches. Rollback selects an earlier verified snapshot and advances that
revision, so a stale process cannot overwrite a newer publication.

This snapshot lifecycle does not add a second renderer. It is a standalone
input and publication adapter around the same Framework Recipe and Document
contracts. A failed compilation leaves the current active snapshot unchanged.
