# C6 — product, compatibility, security and browser matrix

Status: `pass`

All checks use exact source `be0ba2db5254e468c7c014016ade02e8b4f3f16c`
and ZIP `630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0`.

## Compatibility and build

- macOS PHP 8.4.20: fresh dist install, init, update verify, full/single,
  static verification — PASS;
- macOS PHP 8.3.31: fresh dist install, 103-route build and static verifier
  206 HTML / 21,437 references / broken=0 — PASS;
- Linux `php:8.3-cli-bookworm`, immutable image digest
  `sha256:107f022053b2222ffd64f957c2a3b4c724c506301c84638e537f9027ed6468f5`,
  PHP 8.3.33: fresh dist install, init, verify, build and static — PASS;
- public HTTP smoke: 103/103 canonical routes returned 200.

## Update and rollback

The old rc.2 package was independently reproduced at its historical exact ZIP
SHA. The new rc.3 CLI reported `update_available`; dry-run emitted hash-bound
plan `ea3abaadd81221707ad64e5afd25d52f8326700e3e47c8f7aa07b48a8eb3a606`.
Apply changed exactly three engine-owned metadata files and reached `current`.
Rollback restored the old engine digest. The combined `content/**` and
`assets/**` digest remained
`353b7f2d9fb410e5e3293571d5494672f8870f2284451ef08c16e593cbb7575c`
before apply, after apply and after rollback.

## Security and product behavior

Composer audit returned no advisories or abandoned dependencies. Package
verification covered traversal, symlinks, modes, timestamps, duplicate and
case-colliding paths, secrets/private paths, licenses, SBOM and packaged links.
The full 393-test / 7,173-assertion suite includes fail-closed ownership,
update and security negatives.

Exact-artifact browser checks passed at 1920/1440/390, light/dark, RU LTR and
AR RTL fixture. Menu, search, settings, mobile navigation, tabs, copy,
keyboard focus/Esc and reduced motion were exercised. Console/page errors and
horizontal overflow were zero. Machine-readable summary:
[`browser-results.json`](browser-results.json); screenshots are in
[`browser/`](browser/).

The AR fixture proves engine directionality and authoring contract only; it is
not a claim of a complete Arabic documentation translation.
