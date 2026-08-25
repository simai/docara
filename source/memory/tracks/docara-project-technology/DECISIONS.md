# Track Decisions

- Root `graph.json` v2 is the only canonical manifest.
- `graph/graph.json` remains a compatible project index under that manifest.
- Private `source/` content is inventoried locally and never copied wholesale.
- Synchronization runs at meaningful task boundaries, not in the background.
- Automatic keyword inference must not reactivate a release or implementation
  track from a negative boundary statement.
