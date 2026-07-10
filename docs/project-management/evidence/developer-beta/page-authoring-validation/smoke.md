# Browser smoke

Target: disposable `http://127.0.0.1:8765`, installed through the Developer
Beta installer with an isolated SQLite database and the current Docara working
tree overlaid only for pre-commit acceptance.

Observed outcomes:

1. persistent administrator login reached `Pages · Larena`;
2. empty state and Create page action were visible;
3. a draft Page was created and appeared in the list with slug, status and Edit;
4. a second Page with the same slug returned the shared validation summary,
   the custom duplicate message and preserved title/body;
5. the first Page was renamed and re-slugged, with `Page updated.` visible;
6. SQLite contained one updated draft Page and two audit events.

The real `larena.test` MySQL contour was not mutated in this batch.
