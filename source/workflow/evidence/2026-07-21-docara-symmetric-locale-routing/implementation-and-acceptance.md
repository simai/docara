# Implementation and acceptance

Date: 2026-07-21
Verdict: PASS
Baseline: `5727b10fb1648a675c1857caf74632e3986334b5`
Implementation candidate: `e8aaac5665b99415034928ad6bc8c63f7ff6b831`

## Product result

- `locale_routing.strategy=prefixed` is the recommended symmetric mode;
- every locale owns an explicit content root and public prefix;
- the compatibility mode `default_unprefixed` remains implemented and tested;
- `/` is a deterministic static redirect/fallback to `default_locale`;
- Russian Docara sources moved to `docs/site/content/ru` and the portable
  starter moved to `stubs/portable/content/ru`;
- the local documentation is published under `/ru/`;
- `.docara/locale-routes.json` contains one root redirect and 92 exact legacy
  unprefixed redirects;
- authored pages, generated catalogs, examples, declarative previews,
  navigation, search, canonical and alternate links use the shared locale URL
  projection;
- rendered Markdown links are projected to their locale URL without rewriting
  canonical, alternate, external or fragment-only links.

## Automated acceptance

- focused locale/site/static suite: `66 tests`, `1005 assertions`, PASS;
- complete PHPUnit suite: `618 tests`, `5426 assertions`, PASS on PHP 8.2.29
  from ServBay;
- changed PHP files: Pint PASS;
- `git diff --check`: PASS;
- two consecutive production builds: byte-identical;
- relative tree SHA-256: `dd85122921f6486311fc01efeff6fc6246c50ee27529fc445ee9dd0ad0607d51`;
- static verifier: 271 HTML pages, 20,566 local references, zero broken
  references;
- generated localization page: zero unprefixed Markdown authoring links.

The repository-wide Pint check still reports eight formatting deviations in
pre-existing files outside this change. They were not rewritten as part of the
locale-routing goal; all changed PHP files pass the same formatter.

## Local publication

Publication ID: `symmetric-locales-link-fix-20260721-1815`

- served digest equals the accepted source digest;
- backup:
  `/Users/rim/Sites/docara.test/.docara-backups/symmetric-locales-link-fix-20260721-1815/build_production`;
- same-filesystem rollback tree:
  `/Users/rim/Sites/docara.test/.docara-staging/symmetric-locales-link-fix-20260721-1815/served-before`;
- root, localized documentation, legacy URL, catalog, examples, declarative
  preview and landing smoke URLs all returned HTTP 200.

No public push, merge, tag, package release or production-readiness claim was
made.
