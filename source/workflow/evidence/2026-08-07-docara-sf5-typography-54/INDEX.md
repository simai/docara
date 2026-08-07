# SF5 5.4 typography / Docara integration evidence

Date: 2026-08-07
Status: `typography_54_docara_ready_for_independent_audit`
Product candidate: `93a259f4a3c1691926c596dcdc8786e14206c72d`
Rollback entry: `3558ee8a41873a60e261e74655b1032d33bc9f52`

## Immutable owner and distribution chain

- `simai/ui-loader@41cc7e01a3616bf245bf054917033397684d2093`;
- owner handoff `6ce0d0a05dd8eb4f833f9abd04f53b04862c8523`;
- `simai/ui-builder@367b3423f9707b850c6bef9476ab8d1ed44039e1`;
- unpublished generated `simai/ui@2b2e6ea88ac5f30dc0c90c61104506e6c9541108`;
- distribution rollback `d1daa951dd08b94a9f209fd9f31a78d2b3779563`;
- contract `9180a5fe78a01890a8492b240d676b212ad73bccd6f7e9c8853984e4c590a7b3`;
- Core CSS `9c235fbdd02246def279e710bd92ee3c6fed4c3dcdcc859f0ebf9ab73afb20af`;
- Utility CSS `8918648d7ba8b4cf285bbc5d28b22240e05d5f2c47b48c68f9e8e9827e685709`;
- owner packet ZIP `d20a0ce7d97bbb3e9502236fa3cb73acd7ca3d74b2559a3120ea3496a4c98dad`.

Two fresh `git clone --no-local` owner/builder waves, using the exact frozen
source and builder dependencies, reproduced the 57-file Core ledger
`27d05026ac94dd3b79ca2a40eb9aa580c0b575b5e3dd5eb4fa3e5ca55e49698d`
and 3,842-file Utility ledger
`c6040fd80bb7dce811d480f28b21d6fd05163f6fd4fddd9cbdeef07ed0218a31`.
This is a clean-room executor recheck, not an organizationally independent
owner verdict.

## Runtime and security

Commits `64e3486`, `9829a07` and `93a259f` add the exact local projection,
deployment-base-aware URLs and the seven generated Inter subsets. The first
browser run exposed two font 404s; `93a259f` is the durable RED-to-GREEN fix.
Changed bytes, symlink and hardlink projected assets fail before render.
The package lock, starter lock and docs lock carry the same exact identities.
No second renderer, Gateway, registry, LayoutComposer or PageBuilder exists.

## Verification

- focused projection security: 3 tests / 28 assertions, PASS;
- full PHPUnit with PHP 8.4 and `memory_limit=512M`: 508 tests / 10,758
  assertions, PASS;
- Pint, Composer strict/audit, 377-file PHP lint, tracked JSON and
  project-context: PASS; advisories=0;
- public full A/B and selected single: 413 files each, exact canonical ledger
  `e35f077cc1511b88270a28a8e616726543cbc67b5bc794b1a56e461364bc7934`;
- static for all trees: 266 HTML / 35,862 references / broken=0.

The canonical ledger is SHA-256 of path-sorted records formatted as
`<file-sha256>  <relative-path>\n`.

## Browser

The exact build was served from an isolated PHP root. Computed values:

- 320/390: H1-H6 `24/28`, `22/24`, `20/24`, `18/20`, `16/16`, `14/16`;
- 960/1440: H1-H6 `36/40`, `32/36`, `28/32`, `24/28`, `20/24`, `16/16`;
- mobile Display 1-6: `48/56`, `44/52`, `40/48`, `36/40`, `32/36`, `28/32`;
- desktop Display 1-6: `80/96`, `72/80`, `64/76`, `56/64`, `48/56`, `40/48`;
- Body small/medium/large and `.text-6.heading` match role tokens; overriding
  `--sf-heading-1--size` changes computed H1 from 36px to 42px;
- Inter Variable loads from the local hash-bound subsets; font 404s=0;
- light/dark, LTR/RTL and 200% zoom have horizontal overflow=0.

Screenshot SHA-256:
`cb9c3b8047859412e8abcaed51ad9ecefb4bd820c8849af4992e593d500d1836`.
One new browser process recorded a transient jsDelivr JavaScript
`ERR_SOCKET_NOT_CONNECTED`; the exact immutable URL independently returned
HTTP 200 and the subsequent typography console was clean. This is the existing
online Framework-JS contour, not offline-bundling evidence.

## Package and fresh consumers

Two independent clean clones built with
`--tag=v2.0.0-alpha1-typography54`:

- ZIP `d635413a5566cda03ce01bd3e4aac6629fdb46bba00793aa2ed6c825104f9834`;
- external manifest `55e1268498aaf25563a5868288861c7c5e77b949362b6ccb2ad73d973256f207`;
- checksums `c90476441ae7503c17a33f0d5eae7844db254be94b2637946b9d69fb235e70a3`;
- 879 files; both repository verifiers PASS.

Two fresh dist consumers use lock
`2c9550456c03812faead4ce1655b943eb5013467e24bae2fde120fbb0ca42561`.
Both have no package `.git` or `node_modules`, pass init/doctor/full/single/
static, and produce identical 218-file trees at
`9f85a33bc2a0ec3966db4d7e45ed880c0e2770737126901e9fc3204ab7199afe`
(78 HTML / 4,009 references / broken=0).

## Boundary and rollback

No merge, push, tag, publication, release, deployment or site write occurred.
Rollback is a normal revert of the bounded Docara commits to `3558ee8…`; the
unpublished distribution returns to `d1daa951…`. Independent acceptance remains
the only next action.
