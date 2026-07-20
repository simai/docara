# `bx-simai.layout` comparison

The Bitrix module was inspected read-only at revision
`384d29694aa6162f19dca72d54d796bb18d9f75b`. Its worktree already contained
unrelated uncommitted changes, so no files in that repository were modified.

## Architectural meaning carried into Docara

The useful platform-neutral chain documented by `bx-simai.layout` is:

```text
Page
-> Section instance
-> resolved data/content model
-> Section composition
-> Block placements grouped by slot
-> registered renderers/templates
-> Section view
-> HTML
```

The important contracts are not the Bitrix editor or `.layout.v2.php` storage
format. They are:

- a page stores Section instances rather than copying complete renderers;
- reusable Section and Block definitions remain registered separately;
- every instance has a stable ID;
- composition places Blocks into named slots before the Section view renders;
- templates receive normalized/resolved input rather than raw provider data;
- runtime binding uses instance IDs and declared keys rather than implicit
  global state;
- presentation templates remain presentation-only.

## Docara adaptation

Docara now applies the same idea in a smaller, static-site-safe form:

```text
Markdown + JSON references
-> Layout / Section / Block / Smart registries
-> ResolvedRenderPlan
-> Blocks grouped by named slot
-> safe Framework View Tree
-> registered PHP or Blade Smart leaf
-> static HTML
```

The adaptation intentionally does not copy Bitrix-specific capabilities:

- no Bitrix component calls, module loader, database provider or editor;
- no PHP arrays in authored page configuration;
- no callbacks, arbitrary template paths or executable bindings;
- no responsive coordinate-grid editor in this bounded contract.

For Docara, JSON is data and composition, the safe View Tree is the bounded
layout language, and registered Blade is available only for trusted complex
presentation leaves. This preserves the design intent of `bx-simai.layout`
without importing its platform coupling or unsafe author surfaces.
