# Verification summary

## Product result

- The declarative demonstrator now contains 12 source-backed examples: the
  previous seven plus branded header, sidebar, aside, footer and three-level
  composition inheritance.
- `docara.shell` is a registered shell section for header, sidebar, outline and
  footer.
- `shell.element` renders one structured, escaped semantic element. Allowed
  author data is limited to tag, text, local href, aria label and utilities from
  the pinned Framework projection.
- `shell.smart` admits exact `ui.alert` and `ui.button` manifests in addition to
  internal Docara shell composites.
- Section and block IDs are unique, array order is deterministic, and
  configuration provenance names the owning site, section or page file.
- Content semantic parity now compares main-region Smart calls only; shell UI
  no longer creates a false mismatch against legacy Markdown content.

## Static and test evidence

- Focused composition/configuration/parity suite: 36 tests, 244 assertions,
  PASS.
- Full regression: 595 tests, 4,947 assertions, PASS.
- PHP Pint: PASS.
- `git diff --check`: PASS.
- Composer validation: PASS; the bundled Composer PHAR emits PHP 8.4
  deprecation notices but no validation error.
- Static verification for both clean builds: 152 HTML pages, 15,489 local
  references, zero broken records.
- Demonstrator receipt: 12 example records with exact source SHA-256 values.
- Authored documentation: 58 Markdown pages; resolved page plans: 84; search
  documents: 71.

## Determinism and compatibility

`build_a`, `build_b` and the served local site are byte-identical by sorted
file-manifest digest:

```text
68475469b5a1e84e85bdc07ae4da9ad30bb2e3b9c5d69c37154c8578adde92d3
```

- Framework compatibility ID:
  `sf-v5.3.2-7e836d8a-dd786bba`.
- Framework registry SHA-256:
  `2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7`.
- Accepted legacy renderer SHA-256 remains:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Fail-closed boundaries

- region, section and block schemas use `additionalProperties: false`;
- direct compiler calls repeat critical runtime checks instead of relying only
  on JSON Schema;
- unknown Smart components, unsupported views, duplicate IDs, unknown keys,
  script tags, unsafe local links, unregistered utilities and executable or
  template surfaces fail before publication;
- authored Markdown remains the only source for `main`;
- templates and renderer IDs remain registered product-owned resources.
