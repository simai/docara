# G3D.2 — integrated exact-candidate retest

Date: 2026-08-04
Status: `ready_for_independent_audit`; not self-accepted
Exact product/docs candidate: `ba89bccf8e2ad11ed7c72d89d380e924aaaf17d8`
Accepted SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`

## Source and runtime checks

- focused Smart SDK/discovery/MCP/scaffold/provider/cross-host suite:
  25 tests / 263 assertions, PASS;
- full PHPUnit: 440 tests / 8,078 assertions, PASS;
- exact Docara/SF5 cross-host fixture remains byte-identical with empty
  warnings/stderr;
- Pint: PASS;
- Composer strict validation: PASS;
- Composer audit without a nonexistent package-owned lock: no advisories;
- PHP lint: 365 tracked PHP files, PASS;
- JSON: 466 tracked files, PASS;
- YAML: 35 tracked files, PASS;
- project graph: 1 goal / 11 stages / 14 batches / 4 metrics / 7 mappings,
  warnings=0, blockers=0;
- project-context and candidate-range `git diff --check`: PASS.

Composer emits only tool-owned PHP 8.4 deprecation notices. The repository does
not own `composer.lock`, so a locked audit is not claimed.

## Public build parity

Two independent full builds each produced 104 routes / 307 files / 208 HTML.
Their complete normalized ledgers are identical:

`44d4b62f377fca72866874f4bbf6a3029fcf74f52575b811cfd1debbdde96744`

The selected `/ru/components/alert/` build preserves that same complete output
tree. Alert HTML SHA-256 is
`ba04c7bf3cb085e9c5a7aa631b0a3278bac8cf78b083df02d33802bd5d4a23d0`.
Static verification checked 21,842 local references with `broken=0`.

## Deterministic package and consumers

Two independent `git clone --no-local` checkouts of the exact product
candidate produced byte-identical unpublished packages:

- ZIP SHA-256:
  `105a12843ea4cdfa6fe3f30458e43095c5fc80740d2f0912bd0a21817208fb45`;
- external manifest SHA-256:
  `9e7096a1c2164e7c23af5236bde76a86894cac21360ca8f578c132c11a9c38d9`;
- checksum file SHA-256:
  `eecfe4228810d6887c97e2a2b8554769f4bae30e28a83c9e1d0c5ae29ab04684`;
- package verifier: PASS, 741 files in both clones.

Two fresh Composer dist consumers used the same lock SHA-256
`0e5a8df4ad0eeaf73bb35cb49c049b5c0c957a0555d374a410421dcd6a28de50`.
Both initialized and produced identical 168-file full and selected-Alert
trees, ledger
`d8b21cd7f57fc66943a2274bb5bf52ae5253a380acc1ea00d5bdb036429f0f9d`.
Neither consumer contains package `.git` or `node_modules`.

## Fresh production-path browser QA

All 24 Smart/region/layout scenarios passed (desktop/mobile, light/dark,
LTR/RTL). Each authoritative `qa --verify` returned 8/8; local assets were
served by the isolated production-path preview; a11y, console errors/warnings,
overflow and visual diff are zero; keyboard/focus and reduced motion pass.

| Subject | Final plan | Reference | Manifest seal | Report | Artifact |
| --- | --- | --- | --- | --- | --- |
| `smart:ui.alert` | `234a07307de95999af00fe056c7d66f35a9e42e19f59fbbee910d68e83cd6a35` | `2f37df30d98e6f888e36a5a594898724ae597c11e04770f0c5253b25fc8c210f` | `1e8ebd30a6fb3894b2cc4620ff7db443f90f81ceb3cc8002a1f5b4274e6fe4e5` | `53a604b55d716179af95e88aa72141fc3d474e88a05a3d805974b8a7383ac152` | `847c86293c87a304259c82607e978a6b2967d58a67840d2fac3509294651a80d` |
| `region:main` | `7e1056a9425b7e0ce5108ebd09d5a78e5125cce911ebfd09edf4c02e3f846686` | `19adddc94d21982dcd477640dde5c118b9842fe9f675f2ced8590aae170ae3a3` | `091ff99af07bfb6a4be8fb7ce84522efd6f2a8e0ef3bdc78dcbd0d68a2e6c882` | `7035aad74e5ee5ead4df07e332ea917e3166503f2f29eafa5926af5c172cacbc` | `a8397d410a8a3e331e7a46b462584f0cffb50437991a82511fa09df84c8d198e` |
| `layout:docara.docs` | `3f61eaa045ab9ced3349a4461c2930243ee05e17e3bedefa1955dc36b817f266` | `0875bbefe184187e5477bde866a322cd97dc26b8dbd9da31af4b455fdbffea9c` | `dfa2ff76d01bb8ee160a08ac5d3ce8a814a5ee07223f290ec1faa0f7f1d3789c` | `baa305e4771e9ba5d1d773bd428a1957ec0696d7b2532dd2f03f960c7f116bf6` | `50e4fd76ae8b827e4e2b5b143cd9f8036aac70b7c0630f5e06062b9214157e6f` |

The combined reference/result PNG ledger is
`99e87ffbb0eb184a069bb09a92a98f770f2f8f0a7e133fae8055473465ee0d5f`.
Screenshots are disposable evidence, not a source of truth.

No external repository, `docara.test`, `docara-new.test`, release, tag, merge,
push or deployment was touched.
