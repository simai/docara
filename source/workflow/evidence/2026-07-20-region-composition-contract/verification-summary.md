# Verification summary

Date: 2026-07-20
Verdict: PASS

## Runtime

Supported CLI runtime:

```text
/Applications/ServBay/package/php/8.2/8.2.29/bin/php
TZ=UTC
```

The Homebrew PHP binary is locally broken because it references removed ICU
73 libraries. No dependency or system configuration change was made; all
acceptance used the working supported ServBay PHP runtime.

## Focused and integration checks

Final integration matrix:

```text
99 tests, 1,562 assertions, PASS
```

It covered architecture boundaries, descriptor schemas, inheritance and reset,
region resolution, shell composition, trusted rendering, Larena projection,
documentation examples, PortableSiteBuilder, the real documentation site and
the static verifier.

Additional focused matrix:

```text
48 tests, 735 assertions, PASS
```

## Full regression

Final full-suite result after all code and documentation corrections:

```text
580 tests, 4,804 assertions, PASS
Time: 03:22.291
```

## Formatting and hygiene

- Pint on the changed PHP surface: PASS.
- `git diff --check`: PASS.
- Legacy renderer SHA-256 remains:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
- No secret, `.env`, database or ServBay configuration change is in scope.

## Deterministic production build

Two consecutive builds produced the same content-tree command digest:

```text
3a6b1babfe5f2e2b58cafc0ca6de22dc92d5c1969b6548c9f3a7dce397780c4f
```

Both static verification runs returned:

```json
{
  "schema": "docara.static_build_verification.v1",
  "deployment_base": "/",
  "html_pages": 115,
  "local_references_checked": 11256,
  "broken": []
}
```

Documentation inventory:

- authored Markdown pages: 46;
- resolved product pages: 59;
- search documents: 58;
- preview page for `/authoring/regions/`: rendered;
- unsupported components on that page: none.

## Semantic result

- Default page regions: `header`, `sidebar`, `main`, `outline`.
- Demonstration page regions: `header`, `main`.
- Disabled `sidebar` and `outline` are absent from the HTML, not merely empty.
- Required `main` fails closed with
  `DECLARATIVE_REQUIRED_REGION_DISABLED`.
- Invalid region/Smart/binding combinations fail closed.
- `layout.$reset` restores registered structural defaults while clearing
  inherited author presentation values.
