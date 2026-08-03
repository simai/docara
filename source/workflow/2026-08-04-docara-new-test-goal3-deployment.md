# docara-new.test Goal 3 candidate deployment

Date: 2026-08-04
Status: `deployed_and_verified`
Mode: local validation-site deployment

## Scope and identity

- repository: `/Users/rim/Documents/GitHub/docara-unified`;
- branch: `codex/docara-unified-architecture`;
- product source: `2a7237bc59265d976b6871cb637e7ae67ca2c00b`;
- handoff input: `0a09faa6b42444ce0d2fa50c94a69395125b6e49`;
- target: `/Users/rim/Sites/docara-new.test` only;
- public URL: `https://docara-new.test`;
- `docara.test`, Caddy configuration and external repositories are excluded.

## Preflight

- user explicitly requested rebuilding `docara-new.test`;
- federation action gate: PASS; backup/rollback and production-safety gates
  permit this reversible local write;
- current target: 305 files / 206 HTML;
- current tree SHA-256:
  `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`;
- current local `/ru/index.html` equals the HTTPS response;
- HTTPS is 200 over HTTP/2; HTTP redirects 301 to HTTPS.

## Change and rollback

Build the exact product source in a fresh disposable clone. Require two
byte-identical builds, 104 routes / 307 files / 208 HTML, static broken=0 and
the accepted candidate ledger before creating a same-filesystem sibling
candidate. `scripts/atomic-static-cutover.php` then renames the current target
to a unique backup and the verified candidate to `docara-new.test`.

Rollback uses the same helper with exact old/new digests. No Caddy reload is
needed. Keep both the previous rc.3 backup and the new immediate rollback
directory; deletion is outside this scope.

## Verification and stop conditions

After cutover require exact active digest, static verification, every metadata
route over HTTPS, representative assets and browser smoke for home and Alert.
Roll back immediately on digest mismatch, required-route 4xx/5xx, broken
reference, browser page/console error, horizontal overflow or failed rollback
precondition.

Evidence is written under
`source/workflow/evidence/2026-08-04-docara-new-test-goal3-deployment/`.
This validation deployment does not accept Goal 3 and authorizes no merge,
push, tag, release or `docara.test` action.

## Result

The exact product source was built twice in a fresh clone; both 307-file trees
were byte-identical at
`5dc4112d3a0424ca74fca1b73a27392532cfdff84011369a5c3b38984fc32e9d`.
The first cutover was rolled back exactly after Chromium reported transient
external jsDelivr QUIC/socket load errors. The candidate then passed an
isolated Firefox HTTP preflight with zero console errors/warnings and zero
overflow. A second exact preflight and atomic cutover succeeded.

The active site now has 104 routes, 307 files and 208 HTML. Static verification
checked 21,842 local references with broken=0; HTTPS smoke passed 104/104
routes. Firefox browser checks passed desktop home and 390px Alert, search,
settings, Escape, Markdown tab, copy feedback and zero overflow/console errors.
The two Highlight.js fallback messages on the home page are console log records,
not browser warnings or errors.

Immediate rollback is available at
`/Users/rim/Sites/.docara-new.test-backup-before-goal3-2a7237b-20260804`,
digest `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`.
Caddy was not reloaded. The pre-existing empty rc.3 backup was not changed.
