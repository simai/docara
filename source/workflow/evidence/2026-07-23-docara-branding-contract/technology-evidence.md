# Technology conformance evidence

## Product contract

- Owner: Docara 2 portable PHP generator.
- Configuration: validated `branding` JSON contract.
- Rendering: registered `docara.brand` Smart views and PHP templates.
- Presentation: Simai Framework tokens and utilities; no user-provided HTML,
  template path or arbitrary CSS classes.
- Delivery: static `build_production`, verified before reversible local
  publication.

## Evidence

- `branding.mode`: `full`, `compact`, `logo`, `text`;
- `branding.size`: `small`, `medium`, `large`;
- four registered Smart views, including accessible logo-only output;
- one theme-independent SVG logo and a separate ICO favicon;
- PHPUnit: 312 tests, 4193 assertions, PASS;
- repository and changed-file Pint checks: PASS;
- Composer strict validation and JSON parsing: PASS;
- `verify-static`: 190 HTML files, 10,394 local references, zero broken;
- served logo and favicon hashes match the supplied source assets;
- dark and light browser inspection: compact branding renders correctly,
  remains overflow-safe and preserves an accessible link name;
- source and served build manifests are byte-identical;
- rollback copy retained at
  `/Users/rim/Sites/docara.test/.docara-backups/20260723-153709/build_production`.

Verdict: **conformant for the bounded branding contract and local test site**.
No merge, push, release or production-readiness claim is made.
