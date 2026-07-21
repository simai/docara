# Baseline: language-independent Docara

Date: 2026-07-21
Branch: `codex/docara-consolidation`
HEAD: `bd9d42a87e41c2914c9ab7bcb8ff2fdf3b3d3f3f`
Worktree before workflow: clean
Action gates: env policy, repository hygiene and source policy PASS

## Confirmed defects

- `component-catalog-entry.schema.json` requires a `presentation.ru` object and
  rejects other locale keys.
- `PortableComponentCatalogProjector` and
  `PortableDeclarativeExampleProjector` select hard-coded Russian or English
  copy.
- the site, section and page schemas use a partial locale regex that excludes
  valid tags such as `zh-Hans`.
- `PortableSiteBuilder` selects one build locale and rejects pages belonging
  to another locale.
- page output carries `lang`, but the shell has no locale registry, fallback
  graph, first-class direction, language switcher or alternate-link contract.

These are product contract defects, not documentation-only gaps.
