# Acceptance: Docara neutral outline marker

Date: 2026-07-22
Verdict: PASS
Scope: local Docara source, exact generated site and `docara.test`

## Scenario

A reader follows the current heading through the right contents rail in light
or dark theme. The indicator must remain visible and anchored to the divider,
but must not look like a primary action or focus state.

## Implementation evidence

- divider: `--sf-outline-variant`;
- active marker: `--sf-outline`;
- active label remains `font-weight: 700`;
- no new Docara color, opacity, class, DOM node, dependency or JavaScript.

## Automated evidence

- focused PHPUnit:
  `/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/phpunit --colors=never tests/PortableDocumentationSiteTest.php tests/PortableSiteBuilderTest.php`;
  PASS, `37 tests`, `812 assertions`;
- full PHPUnit: PASS, `623 tests`, `5569 assertions`;
- exact build from `docs/site`: PASS;
- static verifier: `271` HTML pages, `20512` local references, `0` broken;
- `git diff --check`: PASS.

## Browser evidence

URL: `https://docara.test/ru/migration/legacy/`.

Light theme:

- marker: `rgb(118, 119, 124)` / `#76777c`;
- divider: `rgba(118, 119, 124, 0.23922)`;
- marker geometry: `2 x 36 px`;
- marker displacement from divider: `0 px`.

Dark theme:

- marker: `rgb(144, 144, 149)` / `#909095`;
- divider: `rgba(227, 226, 231, 0.23922)`;
- marker geometry: `2 x 36 px`;
- marker displacement from divider: `0 px`.

Both themes:

- active label remains `700` weight, so state is not color-only;
- horizontal overflow: `0 px`;
- generated asset URL uses SHA-256
  `40c1c9318b8ed26ab5b2a7da0943e9c908f6079139d6a52e85567b78708819b7`.

## Simplicity evidence

The change replaces one semantic Framework token. It adds no CSS selector,
component, utility, control, behavior, configuration or runtime branch.

## Publication and rollback

- deployed path: `/Users/rim/Sites/docara.test/build_production`;
- deployed/source Smart CSS SHA-256 matches exactly;
- rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/neutral-outline-marker-20260722-141043/build_production.previous`;
- action gate:
  `source/output/action-gates/action-gate-report-20260722111031.json`.

## Not checked

- no RTL locale is currently available for visual acceptance;
- no public production host was changed.

## Nonclaims

No public push, merge, tag, package release or production-readiness claim was
performed.
