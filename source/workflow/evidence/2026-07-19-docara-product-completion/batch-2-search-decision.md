# Batch 2 deterministic local search decision

Date: 2026-07-19
Base: `d4ce688b38c6a29c2b57aaac2f8fe132f05b26b9`
Architecture verdict: PASS_WITH_NOTES, no blockers
Status: implementation-ready

## Product boundary

Search is a Docara product function, not a new generic Smart-component.

- PHP build time produces a deterministic local index from pages that are
  actually being published.
- A small Docara browser runtime loads that index on first use and performs
  local prefix/full-text matching.
- Simai Framework owns input, button, list presentation, utilities, themes,
  focus states and tokens.
- No external API, server endpoint, Node.js production dependency, moving
  Framework asset or new Smart-component is introduced.

The pinned upstream contains related custom elements, but they are not part of
the accepted portable projection and do not implement full-text documentation
search. Batch 2 therefore uses native `dialog`, input, links and the existing
static Framework component patterns rather than silently extending the Smart
allowlist.

## Configuration

One inherited `search` branch is added to site, section and page descriptors:

```json
{
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

- `enabled` controls whether the current page exposes search;
- `indexed` controls whether pages in the current scope enter the index;
- `$reset: true` follows the existing merge contract;
- safe defaults are `enabled: false`, `indexed: true`;
- the shipped starter and Docara documentation explicitly enable search;
- `navigation.hidden` does not imply `search.indexed: false`.

## Generated contract

Output:

- `/_docara/search-index.json`;
- `/_docara/search.js`.

The canonical index contains schema/version/algorithm, a content hash and
documents sorted by locale then URL. Each document contains deterministic ID,
URL, locale, title, description, ancestor trail, headings and normalized
visible text. It contains no timestamp, absolute path, raw Markdown or build
diagnostics.

Visible Smart text is projected explicitly:

- `ui.alert`: title and supporting text;
- `ui.button`: text.

An admitted component without a search text projection fails closed instead of
silently disappearing from results.

## Interaction

The smallest complete UI has five surfaces: header trigger, native dialog,
Framework-styled input, live status and native result links.

- `Cmd/Ctrl+K` opens search;
- focus moves to the input;
- two characters are required;
- Arrow Down/Up traverse result links;
- Enter from the input opens the first result;
- Escape closes the dialog and restores trigger focus;
- results are built with DOM APIs and `textContent`, never `innerHTML`;
- the index is fetched only on first open and only from the local site;
- locale isolation, loading, empty, no-results and load-error states are
  explicit;
- long content wraps and the 390px layout has no horizontal overflow.

Matching is Unicode-normalized, case-insensitive, treats Russian `ё` as `е`,
requires every token and weighs title above headings, description and body.
Fuzzy typo correction, stemming, morphology, semantic/AI search and analytics
are explicit nonclaims.

Official references are behavioral evidence only:

- VitePress local search and keyboard/localization patterns:
  <https://vitepress.dev/reference/default-theme-search>;
- Docusaurus navbar/search placement patterns:
  <https://docusaurus.io/docs/api/themes/configuration/>.

Their Vue/React/Node or hosted search stacks are not dependencies.

## Test-first acceptance

- strict schemas accept only boolean `enabled/indexed` and `$reset`;
- reset/inheritance and exclusion are explained in resolved plans;
- two builds produce byte-identical index/runtime/output trees;
- hydrated Markdown and supported Smart visible props are searchable;
- scripts, styles, templates and hidden/aria-hidden content are excluded;
- malicious text is rendered as text, not markup;
- title/heading/description/body ordering and Russian `ё` matching pass;
- an enabled locale without indexed documents fails before destination cleanup;
- missing runtime, invalid UTF-8, unknown Smart text projection and invalid
  generated schema fail closed;
- base URL is applied to local index/runtime URLs;
- production build/static verifier and browser keyboard/responsive/theme/error
  scenarios pass on one exact candidate.

Index size grows linearly with the corpus. At the current 41-page scale this is
not a blocker; sharding or a worker must be justified by measurement rather
than added pre-emptively.
