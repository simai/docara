# Changed surface inventory

- `FileRecipeCompiler` owns one managed Node session and supports file-backed
  and generated Recipe requests.
- `composition-recipe-file-adapter.mjs` imports one exact Framework runtime,
  resolves multiple requests and returns one bounded result per input line.
- `ResolvedPlanRecipeBridge` projects Docara page, region, section and block
  data into Recipe, verifies the resulting Document and hydrates the PHP
  renderer projection from that Document.
- `PortableSiteBuilder` requires the exact Recipe runtime for every build and
  writes Recipe, Document and dependency digests to the page receipt.
- Markdown, project JSON, public URLs, HTML templates, navigation, search,
  theme logic and Framework generated distributions are unchanged.

The old `ResolvedRenderPlan` PHP type remains as a renderer adapter. It no
longer crosses directly from the configuration compiler to the renderer in the
production builder: its values are first resolved and round-tripped through
the standard Composition Document.
