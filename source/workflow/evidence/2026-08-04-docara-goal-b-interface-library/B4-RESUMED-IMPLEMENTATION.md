# B4 — exact Framework form wave consumption

Date: 2026-08-05
Input Docara HEAD: `f2da4cc0e53d4a4104dc28f6fb38ce9717e6b0b3`
Status: `implementation_green_integration_pending`

## Immutable owner inputs

| Packet | Product candidate | Content SHA-256 |
| --- | --- | --- |
| `ui.input`, `ui.dropdown`, `ui.checkbox` | `7e0b87187ceb1f89fad730094bcc4aada3e4f3f2` | `83551f972ad0b1a6e2037f61583769e32a4a78081e01ed0a0fe888b1187baca1` |
| `ui.list-item` | `639d7b67833cfdf1e2c349c5f83669ba0e34fe05` | `7dbcb161e8bb48c342a385c3f28f7dc8628eecdf0c09758ab3113eb8dc2107db` |

The list-item artifact tree is
`6029ca7c9ded244d5d4326d526dd9d91396551cf595ce872bc95119fda90a47c`.
Its admitted scope is only `type=text` below `ui.dropdown`. Related icons,
avatars, tags and standalone-form-control behavior are not claimed.

## Implementation outcome

- Exact unchanged manifest/view/preset/template bytes are vendored under
  `resources/framework/portable-smart/` and checked before descriptor
  registration.
- `resources/framework/portable-smart-lock.json` binds packet, artifact,
  manifest/view/preset/template, slots, asset/hydration contracts and exact
  runtime asset hashes.
- One `FrameworkLockSmartProvider` owns both the direct portable artifacts and
  the bounded Alert/Button storage adapter. Selection is artifact-format
  driven, not component-ID driven.
- Nested options resolve recursively through the same
  `SmartComponentGateway` and `SmartRenderer`. The project configurator uses
  three admitted `ui.list-item` text children; no `items` prop or raw markup is
  introduced.
- Exact assets are published only when the page asset plan actually uses the
  portable wave. This retains the default no-use output surface.

## Focused verification

```text
/Applications/ServBay/package/php/8.4/8.4.20/bin/php vendor/bin/phpunit \
  tests/Unit/FrameworkPortableWaveTest.php \
  tests/Unit/ProjectExtensionDemoTest.php \
  tests/Unit/DesignAtlasTest.php \
  tests/Unit/SmartProviderRegistryTest.php \
  tests/Unit/SmartComponentGatewayTest.php

PASS — 24 tests, 174 assertions.
```

Focused behaviors include the four-component provenance matrix, populated
dropdown dependency trace, standalone/unsupported list-item rejection,
Atlas/inspect/preview projection, useful local-only project demos and exact
usage-driven asset publication.

## Rollback

Before the B4 checkpoint is committed, rollback is the exact input HEAD above.
After commit, use its parent as the immutable rollback boundary. No legacy path
is deleted in this batch.

## Remaining gate

This file is implementation evidence, not Goal B acceptance. Full B5 tests,
clean-root builds, package/consumer, static, browser/accessibility, governance
and exact-candidate evidence remain required before
`goal_b_ready_for_independent_audit`.
