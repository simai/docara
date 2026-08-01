# Alert route owner map

Revision: `f911db16ba07aa6735f09ab2a63370bfd2fa608f`

Route: `/ru/components/alert/`

Output: `ru/components/alert/index.html`

Current inventory kind: `generated_projection`

## Active current path

```text
resources/component-catalog/typed/docara.alert.json
  + resources/component-catalog/source-metadata.json
  + resources/language-packs/ru.json#/components/docara.alert
  + resources/component-catalog/examples/docara.alert.ru.md
  -> EffectiveComponentCatalogBuilder
  -> AuthoredComponentPageIndex (no authored Alert source; generation allowed)
  -> PortableComponentCatalogProjector
  -> PortableMarkdownRenderer::renderAlert
  -> synthetic content/ru/components/alert.md page record
  -> PortableSiteBuilder legacy adapter/layout
  -> ru/components/alert/index.html
  -> /ru/components/alert/
```

The synthetic page path is metadata, not an existing source file. At this
revision `docs/site/content/ru/components/alert.md` does not exist.

The Russian language-pack record owns the visible catalog prose. The example
file owns five examples: informational/default, clear/default,
success/default, warning/outlined and danger/flat.

## Target path after a separately accepted implementation

```text
docs/site/content/ru/components/alert.md
  -> PageSourceLocator / early route selection
  -> MarkdownCompiler (typed in-memory Document IR)
  -> DocumentRendererRegistry
  -> existing SmartComponentGateway content mode
  -> existing PageBuilder
  -> existing layout composer
  -> ru/components/alert/index.html
  -> /ru/components/alert/
```

Behavior-only metadata may remain in the typed component definition. Page
prose must not be projected from a language pack, manifest, config or example
file after the route is accepted.
