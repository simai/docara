# Technology conformance evidence

## Product contract

- Owner: Docara 2 portable PHP generator.
- Runtime: PHP 8.2+ and Composer; Node.js is not required by the generated
  portable site.
- Content/composition: Markdown plus validated JSON contracts.
- Presentation: generated Smart-component and Simai Framework assets from the
  candidate's locked framework contract.
- Delivery: static `build_production`, verified before publication.

## Evidence

- exact candidate: `0d2a528c4bd5cff5b4986ff60e0abd668d328f47`;
- PHP 8.2 and 8.4 full matrices: PASS;
- `verify-static`: 190 HTML, 10,480 local references, zero broken;
- served output and independent rebuild manifests: byte-identical;
- browser: responsive navigation, Smart search behaviour, highlighted query
  fragments and zero console warnings/errors;
- active site has only `build_production/` and its manifest; legacy Jigsaw/Mix
  project files remain exclusively in the rollback backup;
- active Docara skill is immutable-release managed and matches canonical source.

Verdict: **conformant for the bounded Docara 2 candidate and local test site**.
No public-release or production claim is made.
