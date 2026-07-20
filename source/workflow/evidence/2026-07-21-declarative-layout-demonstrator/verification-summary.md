# Verification summary

Candidate: worktree on `codex/docara-consolidation` after `dd62e5b1c178b6750d61e667014bf6d6143e0f6f`.

## Product evidence

- `docs/site/examples`: 7 strict descriptors.
- Generated receipt: `docara.declarative_examples.v1`, 7 detail records.
- Authored Markdown pages: 53.
- Primary resolved page records: 74.
- Generated HTML pages: 137.
- Search documents: 66.
- Static verifier: 13,804 local references, zero broken records.
- Focused example/layout tests: PASS.
- Static verifier tamper test: PASS.
- Full regression on ServBay PHP 8.4.20: 592 tests, 4,906 assertions, PASS.
- Snapshot subprocesses now pin UTC, so their date routes are independent of
  the local PHP timezone.
- PHP formatting, Composer validation and `git diff --check`: PASS.

## Deterministic build evidence

Two clean builds, the retained production build and the served local tree have
the same normalized content digest:

```text
5c6161a587026e78bf2d9043b2ca5d440713d7cb524a6d48404fb2c92230f2d9
```

Both clean builds independently passed static verification with 137 HTML
pages, 13,804 local references and zero broken records. HTTPS smoke returned
200 for `/authoring/regions/`, `/examples/`,
`/examples/regions-disabled/` and `/examples/smart-button/`.

## Safety evidence

- Invalid schema, missing source, visible result, route collision and symlink
  source fail before destination replacement.
- Result Markdown must be present among displayed exact sources.
- Public and private receipts must be identical.
- Static verification binds result route, iframe, source text and SHA-256.
- Unsupported Smart calls remain rejected by the primary builder.
- The accepted legacy renderer SHA-1 remains
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Corrective discoveries

- Disabled sidebar previously left an empty layout column; the publisher now
  omits the wrapper and mobile navigation controls.
- Declarative Smart resolution previously did not apply manifest presets while
  the portable runtime did; both paths now apply the same preset contract.
- A sandboxed same-origin preview raised theme-cookie `SecurityError`; the
  iframe now embeds only an internally resolved trusted result route without
  the incompatible sandbox attribute.
