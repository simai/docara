# Docara code-block Framework regression

Date: 2026-07-26
Status: accepted_local_consumer
Track: docara-consolidation
Framework workflow:
`/Users/rim/Documents/GitHub/ui-control/source/workflow/2026-07-26-highlight-public-path-regression.md`

## Goal

Restore syntax highlighting, the language panel, and the copy action on
Docara code blocks by correcting the canonical SIMAI Framework runtime and
updating Docara's immutable Framework lock.

## Product boundary

Docara continues to emit accessible fallback `<pre><code
class="language-*">` markup. It must not own or duplicate Framework
highlighting, language labels, or copy behavior.

## Acceptance

- exact Framework candidate is recorded in all three Docara locks;
- documentation build and static verification pass;
- `docara.test` is updated with a rollback backup;
- live code blocks contain highlighted token spans, a language header, and one
  copy action;
- copy behavior, light/dark themes, desktop/mobile rendering, and console are
  verified.

## Accepted tuple

- Framework source:
  `ui-loader@8dc4a9c40a063a240713f2f6995ac34a0c53c53b`;
- exact builder:
  `ui-builder@f9aa00ab2c4646262a85b7f61629e17af1f78ba7`;
- generated Core:
  `ui@fa5e1b94fd5327847ac920d7001c69dca4634080`;
- Smart remains:
  `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7`;
- compatibility pair:
  `sf-v5.3.2-fa5e1b94-ab896dc7`;
- Core archive SHA-256:
  `8edf2fa23dc0c4a0c570a067f6a2f5e3bd474b8a3c79b609f6a6d601bd2cacfa`.

The source and generated Core feature branches are published as
`codex/highlight-public-path-docara-20260726`. No default-branch merge, tag,
release, or production-readiness claim is part of this acceptance.

## Verification

- two clean Framework builds are byte-identical for Core, components,
  utilities, and Smart;
- Framework source regression tests and runtime smoke checks pass;
- Docara: `331` PHPUnit tests, `5124` assertions — PASS;
- static build: `198` HTML pages, `14236` local references, `0` broken —
  PASS;
- `git diff --check` — PASS;
- local deployment:
  `https://docara.test/ru/authoring/markdown/`;
- ServBay document root verified from the active Caddy configuration:
  `/Users/rim/Sites/docara.test/build_production`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/highlight-runtime-20260726-024612`.

Browser acceptance found three source code blocks, three highlighted blocks,
three language headers, three Framework copy controls, and 71 syntax-token
spans. Dark and light desktop rendering and the 390 px mobile layout were
checked; the page had no horizontal overflow and no console warning/error.
The automation browser exposes the copy control and its exact
`data-clipboard-target` wiring but does not grant the legacy
`document.execCommand('copy')` clipboard path, so OS clipboard mutation is not
claimed as independently observed.

### Publication-path correction

The first local publication copied the verified static build into
`/Users/rim/Sites/docara.test`, while ServBay's active Caddy configuration
serves `/Users/rim/Sites/docara.test/build_production`. The source file existed
but the public URL returned an empty `404` response. The same byte-identical
build was republished into the actual document root. Fresh acceptance after
the correction confirmed:

- HTTP `200` and `113278` response bytes;
- served HTML SHA-256
  `f44e7b4a23f02ffd9b05ffec76fb9cef1ba79f4a429caffef439d91264f09bda`,
  equal to the build artifact;
- three highlighted blocks, three language headers and three copy controls;
- the exact `ui@fa5e1b94...` Framework scripts;
- no browser warning or error.

## Simplicity review

The correction adds no Docara renderer, setting, dependency, or local styling.
Docara keeps one accessible `<pre><code>` fallback; SIMAI Framework alone owns
progressive highlighting, the language panel, and the copy control. The only
source correction is the component-relative dynamic-chunk base plus regression
coverage.
