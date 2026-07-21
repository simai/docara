# Manifest and registry verification

Status: focused verification PASS; full-suite gate pending.

## Implemented contract

- `src/Smart/SmartManifestValidator.php` is the platform-neutral manifest
  entry point for bundled `ui.*` and `docara.*` manifests.
- `SmartRegistry::fromContributions()` accepts independent contributions;
  bundled providers are `FrameworkSmartContribution` and
  `DocaraSmartContribution`.
- Canonical product keys are `docara.brand`, `docara.navigation` and
  `docara.toc`.
- Manifests expose nested props, events, views, presets, registered assets,
  Atlas metadata, controls, accessibility and four readiness flags.
- Product UI labels are resolved from the current language pack and passed as
  validated props; component templates and JavaScript contain no hardcoded
  Russian/English interaction labels.
- The shared `src/Smart` layer contains no `Illuminate` or `Laravel` imports.

## Focused evidence

```text
phpunit SmartRegistry + rendering + shell + documentation:
30 tests, 783 assertions, PASS

portable primary build test:
1 test, 414 assertions, PASS

five-locale/RTL deterministic test:
1 test, 27 assertions, PASS
```

JSON parsing, PHP lint for `src/Smart` and rendering classes, and
`git diff --check` passed before the full-suite gate.
