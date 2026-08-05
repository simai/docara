# C6 integrated Goal C handoff

Status: `PASS` executor evidence; independent acceptance is not claimed.

## Exact boundary

- accepted Goal B product: `c3b91eee71ab906cd79ae7a119c6961664f03528`;
- accepted Goal B governance: `481e34cccade12a0d7f8d2dbf9b4d37933e49419`;
- Goal C product/evidence candidate:
  `ae6a1e918e248517b728cf40460d6c359991b66e`;
- branch: `codex/docara-unified-architecture`;
- rollback: revert Goal C commits through `d25e89d` and return to the exact
  accepted Goal B boundary. No history rewrite, external write or legacy
  deletion is required.

## Source, Atlas and schema outcome

- `132/132` public routes have one physical `docs/site/content/ru/**/*.md`
  owner; generated public owners and project redirects remain zero;
- Atlas receipt: 50 effective entries, content SHA-256
  `739347f68cf4dfec4c89806898c5449165df9a10cc4b40e3e2c45924cf70aa4b`;
- schema receipt: five trusted schema sources, content SHA-256
  `42f58acb61573a0c278c6fba7ab093998dc0e22be2c6c149c6e836817f5d38e8`;
- both receipts are hash-bound in `resolved-page-plans.json`; the static
  verifier independently recomputes Atlas/schema hashes and a permanent test
  rejects a mutated binding;
- six component entries, the design chain, 13 settings guides, safe agent
  journey and project demos remain Markdown-owned; cards/field facts alone are
  derived from admitted registries and schemas.

## Test and contract gates

Executed with `/Applications/ServBay/package/php/8.4/8.4.20/bin/php`:

- full PHPUnit: `479 tests / 9,867 assertions`, PASS;
- focused documentation/link/schema: `27 / 2,293`, PASS;
- focused security/container/Framework/demo: `34 / 257`, PASS;
- focused cross-host + Goal C projections: `12 / 533`, PASS;
- exact SF5 cross-host: `1 / 45`, HTML byte-identical at
  `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`,
  blockers empty;
- Composer validate strict and audit, Pint, tracked PHP lint, 541 tracked JSON,
  36 tracked YAML, project-context and `git diff --check`: PASS;
- project graph validator: 1 goal / 14 stages / 17 batches / 4 metrics /
  8 mappings, warnings=0, blockers=0;
- candidate-range `git diff --check c3b91eee…ae6a1e91`: PASS;
- package secret/private-path scan: zero matches.

Composer prints its known tool-owned PHP 8.4 deprecation notices; validation
and audit exit successfully and report no advisories.

## Build, package and consumer determinism

Two independently populated clean roots at the exact candidate produced:

- 132 routes / 391 files / 264 HTML;
- complete-tree SHA-256
  `b8b47a837f2ac067434a8da27fd950d93e99916f107441dec42dedb3c9843e81`;
- byte-identical full/full ledgers and byte-identical complete ledger after
  `build production --page=/ru/components/alert/`;
- static verifier: 264 HTML / 35,044 local references / `broken=[]`.

Two independent clean clones produced the same unpublished 862-file package:

- ZIP SHA-256:
  `a5b02a256ff2a57dcb979b8a5f286279b15351f106a8a08908156d8f840f4b14`;
- release manifest SHA-256:
  `eb79422e762a4c56997bb2fe818dd6feba65548b887eb0c42a313e70a409dac7`;
- checksum file SHA-256:
  `f66dde3f3a0536430df11251baaeb0308a21a09625e92bd5017c7153f7091227`;
- repository verifier: PASS for both copies.

Two fresh Composer dist consumers used one lock SHA-256
`50d465d37aae6b030c122a900c803b0b677f55d3477bf2de2c8071a22af7dab7`.
Both initialized, passed doctor/full/single/static without package `.git` or
Node and produced the same 198-file ledger
`35491e8ae1ac6bed1c070f61698b6d75a64cbee0fbaa71f919cb9e6d76322879`.
Static verification: 78 HTML / 3,931 references / `broken=[]`.

## HTTP, browser, accessibility and SEO smoke

The runner [run-browser-goalc.js](run-browser-goalc.js) served only the
disposable exact-candidate production tree. Machine result:
[browser/result.json](browser/result.json).

- HTTP smoke: 132/132 routes returned 200;
- representative new-product routes: 27/27 checks across 1440 light, 1920 dark
  with reduced motion and 390 mobile light;
- each checked route has one H1, self canonical, `lang=ru`, no horizontal
  overflow and no console/page warning or error;
- search and reader-settings dialogs open by keyboard/click, Escape closes and
  restores focus to the trigger;
- screenshots are content-addressed in `browser/result.json` and are evidence,
  never a source of truth.

The first browser open recorded the pre-existing Goal B transient jsDelivr
`ERR_SOCKET_NOT_CONNECTED`. The exact pinned URL returned HTTP 200 immediately;
reload and the complete final matrix were clean. Offline Framework delivery is
not claimed.

## Architecture and nonclaims

- one `DocumentRendererRegistry`, one `SmartComponentGateway`, one
  `LayoutComposer` and one `PageBuilder` remain active;
- typed Document IR remains in memory and full/single differ only by selected
  route set;
- accepted Framework pins/support states are unchanged; `ui.list-item` remains
  text-only for admitted dropdown children, and icons/avatars/tags/raw related
  surfaces remain unsupported;
- no Goal D, release review, merge, push, tag, release, deploy, external owner
  write, `docara.test` or `docara-new.test` write occurred.

The only next action is an independent Goal C reverse-outcome audit.
