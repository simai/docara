# G1C.4 — integrated retest before the cross-host stop

Status: `PARTIAL_PASS_NO_ACCEPTANCE_CANDIDATE`
Two-build/parity revision: `5124959521b6ae51c7e5fa925b3c3230a65a54ef`
Final blocker-governance verification revision: `4d240944377a354f9a50ff6fe52fc31ed2bc7148`
Environment: macOS, PHP 8.4.20

## Test and build results

- full PHPUnit after the blocker documentation update: 372 tests,
  7,222 assertions, PASS;
- focused portable/provider/search/security matrix: 78 tests, 654 assertions,
  PASS;
- two disposable full builds: 103 routes, 305 files, 206 HTML each;
- byte-identical file ledgers:
  `c883aac8718ec3aceffcc4c901fca9ea4eee5628edd865b819696fc003805583`;
- static verifier A/B: 21,430 local references, broken=0;
- full/single HTML equality: 103/103 routes;
- baseline comparison: 205 HTML equal after replacing only the proven
  `search-index.json?docara_v=<sha256>` query; one intentional content delta is
  `/ru/development/smart-components/`; unexpected deltas=0.
- final blocker-governance site build: 103 routes, 206 HTML, static verifier
  21,430 references and broken=0;
- Pint, Composer strict, 309-file PHP lint, 455 JSON files, 199 YAML files,
  graph validation and `git diff --check`: PASS. Graph summary: 1 goal,
  9 stages, 12 batches, 6 mappings, warnings=0, blockers=0.

Correct labels at the tested revision:

| Route | Raw HTML SHA-256 |
| --- | --- |
| `/ru/components/alert/` | `a3ca0a6dd7baf6d01e16baa17d422e404fe726160c5a26f413af706d8a458843` |
| `/ru/components/button/` | `5af2f8d09a5766cd5c8d9f74b9545e5530181fce4821dc84432c2c6eb6ebe506` |
| `/ru/demonstrator-results/smart-alert/` | `e0b5e38ef5ee0785cff2c3cb98887aeeb599e00b7d0365709cf493710815dcd3` |
| `/ru/demonstrator-results/smart-button/` | `a898dabcfcd9c6083d7a080339a31176a3faa87dd5da4bdfd20a32af7dd7a23d` |

The historical `2a6c99…` / `134b17…` labels belonged to demonstrator routes,
not component documentation routes. Raw hashes above include the changed
search digest; normalized parity is reported separately and never substituted
for a raw file hash.

## Fresh browser smoke

HTTP root: disposable `127.0.0.1:18771`; neither live site was touched.

- Alert 1440x1000: 5 rendered alert blocks, tabs work, settings opens and Esc
  returns focus after transition, overflow=0, console/page errors=0;
- Button 390x844: 15 rendered buttons, mobile nav/search open, Esc returns
  focus, Markdown tab and copy feedback (`check`) pass, overflow=0,
  console/page errors=0;
- dark theme applies at 390px and retains overflow=0/focus return.

Screenshots and SHA-256:

- `browser/alert-1440-light.png` — `1dbedb6478bd14b03b638a90cafd3e77384ee8370af36265c7a5af8c443e215e`;
- `browser/button-390-light.png` — `f58216cb0a525a0c7179c3886fb111b7b187a1c0a29c03eea59d7295bcee9ba5`;
- `browser/button-390-dark.png` — `35a8fcf7a5d9a06e72fb3cfa24f67fc4e09b61ea86cf38c575a262bf04eac883`.

These green Docara-side results do not override the G1C.1 stop condition and
do not constitute Goal 1 acceptance or `audit_pending` readiness.
