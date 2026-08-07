# Component catalogue information architecture evidence

Date: 2026-08-07

Status: `complete_local_validation`

Product candidate: `75143bc9b6e978a167a20f87d5a26c469e0b415e`

Parent local Framework runtime: `ad147f32ec9854e5bb97ea635b349b3ce803ed43`

## User outcome

- `/ru/components/` contains two three-column tables and 31 unique direct
  component/reference links;
- the six former type entry pages plus `components/syntax` have no authored
  prose owner below `/components/`;
- `/ru/start/component-model/` is the single authoring-model overview;
- all seven retired URLs publish deterministic `noindex,follow` redirect pages
  to the new overview.

## Tests and build

- focused documentation/catalog/static suite: 51 tests / 3,319 assertions;
- full behavioral checkpoint `fdeed3c…`: 511 tests / 11,513 assertions;
- exact candidate `75143bc…` differs from that checkpoint only by Pint's
  required blank-line formatting in `scripts/verify-static-build.php`;
- Pint test: PASS; Composer strict validation and locked audit: PASS (Composer
  emits tool-owned PHP 8.4 deprecation notices); security advisories: none;
- two full roots `build_catalog-a` and `build_catalog-b`, plus representative
  `build_catalog-single`, contain 650 files and are byte-identical;
- canonical path-sorted tree digest:
  `ead33689e47c3eb690bc9f1ccb570bec117b5a994130886ce37dd50f3eead435`;
- both full roots: 127 authored pages, 261 HTML, 113 search documents,
  7 configured redirects, 127 locale redirects;
- static verification: 261 HTML / 32,970 local references / `broken=[]`.

Canonical digest algorithm is the exact
`scripts/atomic-static-cutover.php::treeDigest()` implementation: sort by
relative path, form `<file-sha256>  <relative-path>` records, append one newline
after every record and SHA-256 the resulting byte stream.

## Browser acceptance

Playwright opened the exact candidate first from a disposable PHP server and
then from `https://docara-new.test`:

- desktop 1280x720 and mobile 390x844: two tables, 31 unique component links,
  document overflow false;
- console errors=0 and warnings=0;
- the retired `/ru/components/native-markdown/` URL reaches
  `/ru/start/component-model/` with the exact canonical URL;
- all observed candidate assets were local HTTP 200 responses.

## Authorized local cutover and rollback

SIMAI action preflight: PASS. The active validation tree before cutover was
`b6e0d9a7578b0af004a8fd1950ba26eab41010c561b9bcb938583fa7e74ab5ca`.
Atomic preflight and cutover both passed to candidate digest `ead33689…`.
The exact previous tree remains preserved at:

`.docara-new.test-backup-before-components-75143bc-20260807`

Rollback command uses `scripts/atomic-static-cutover.php rollback` with active
`docara-new.test`, recovery candidate
`.docara-new.test-candidate-components-75143bc-20260807`, the preserved backup,
expected active candidate digest `ead33689…` and expected backup digest
`b6e0d9a…`. No write occurred to `docara.test`; no merge, push, tag, release or
external owner write occurred.
