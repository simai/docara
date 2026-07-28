# Batch 8 — complete Docara documentation plan

Date: 2026-07-19
Accepted implementation candidate:
`a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
Status: `IMPLEMENTATION_READY`

## Outcome

Give five audiences one tested path through the same product contract:

1. beginner: install, create, build, verify and preview over HTTP;
2. author/site owner: configure inheritance, choose docs or landing, author,
   stage, publish and roll back;
3. migrating owner: move from prior Docara, `docara-template` and
   `docara-mix`;
4. maintainer: clone, install, test and verify the documentation;
5. extension developer and AI: choose native Markdown, typed Docara, exact-lock
   Smart-component or an explicit non-executable requirement.

Component identity, lifecycle, parameters, states, limitations, exact call and
live example remain owned by canonical JSON plus its exact fixture and
generated page. Authored documentation explains tasks and concepts without
copying those facts.

## Test-first changes

Add:

- `tests/Unit/DocumentationContractTest.php`;
- `tests/Unit/DocumentationExamplesTest.php`;
- `tests/PortableDocumentationSiteTest.php`.

The tests must require:

- five audience paths and one H1 per authored page;
- real-schema validation of marked valid and invalid examples;
- exact canonical `docs_ref` mapping;
- no old manual component routes or links;
- no product use of legacy technical version shorthand or readiness overclaim;
- exact build matrix and real static verifier.

## Page operations

Add five Markdown pages:

- `build/verify.md`;
- `build/publish.md`;
- `troubleshooting.md`;
- `development/getting-started.md`;
- `development/extensions.md`.

Add `troubleshooting.page.json` at navigation order 80.

Delete nine duplicate manual component pages:

- `components/alert.md`;
- `components/button.md`;
- `components/card.md`;
- `components/code.md`;
- `components/cta.md`;
- `components/features.md`;
- `components/steps.md`;
- `components/table.md`;
- `components/tabs.md`.

Replace the two remaining inbound manual links with generated
`docara.cta` and `docara.features` routes. Do not add duplicate Markdown
redirect stubs. Record retired routes in migration guidance; a future public
compatibility need requires a separately tested alias or redirect contract.

## Canonical `docs_ref`

- five native records point to `authoring/markdown.md`;
- five typed and two admitted Smart records point to
  `components/syntax.md`;
- five unavailable requirement records point to `components.md`.

No `docs_ref` points to generated output.

## Documentation surfaces

Update repository entry points, quick start, configuration, inheritance,
layout, Markdown/component family guidance, build, verify, publish, reference,
legacy/template/Mix migration, symptom-first troubleshooting, maintainer
architecture/testing and extension admission.

The beginner path must end with production build, `verify-static`, HTTP
`serve --no-build`, visible success and `Ctrl+C`; never recommend `file://`.

The extension decision order is:

native Markdown, typed Docara, exact-lock Smart admission, then requirement.
Promotion deletes a requirement and adds an executable owner record; it never
mutates a requirement into supported readiness.

## Exact target matrix

- authored Markdown: 43;
- HTML pages: 56;
- search documents: 55;
- catalogue records: 17;
- generated catalogue surfaces: 13;
- supported detail pages: 12;
- unavailable records on index: 5;
- retired manual routes: zero;
- broken references: zero.

Run focused and complete PHPUnit, Composer validation, Pint and diff check.
Build twice from one exact archive, compare digests, run `verify-static`, then
browser-check 1440, 768 and 390 pixels, cold load, light, dark and system
themes, keyboard, focus, search terms and overflow.

No release, merge, push, tag, default-branch change, public deployment,
Framework owner write or repository archive is part of Batch 8.
