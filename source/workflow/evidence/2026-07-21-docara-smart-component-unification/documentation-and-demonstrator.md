# Documentation and demonstrator

Updated author and maintainer surfaces:

- `/authoring/regions/`: area vs Section vs Smart ownership matrix;
- `/authoring/branding/`: `header` region, `docara.header` section and
  `docara.brand` component;
- `/development/architecture/`: publisher host and component-owned assets;
- `/development/composition-extensions/`: contribution-based extension path;
- `/development/smart-components/`: complete Smart architecture guide.

Source-backed demonstrator descriptor:
`docs/site/examples/product-smart-runtime.json`.

Expected generated route:
`/examples/product-smart-runtime/`, with exact Markdown and page composition
sources plus an iframe of the primary pipeline result.

The demonstration uses the same `header`, `sidebar` and `outline` regions as a
normal page and renders canonical `docara.brand`, `docara.navigation` and
`docara.toc` with their registered component assets.
