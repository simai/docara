# Verification summary

Status: implementation and build gates PASS; browser and exact-candidate
acceptance pending.

## Full suite

Command:

```text
/Applications/ServBay/bin/php -d memory_limit=1G vendor/bin/phpunit
```

Result: `615 tests, 5387 assertions, PASS` in `04:56.977`.

## Deterministic builds

Commands:

```text
php ../../docara build a
php ../../docara verify-static build_a
php ../../docara build b
php ../../docara verify-static build_b
diff -qr build_a build_b
```

Both static verifications reported:

- HTML pages: `169`;
- local references checked: `20176`;
- broken references: `[]`.

Both trees contain `191` files and `diff -qr` produced no difference.
Representative exact hashes also matched:

- product Smart demonstrator:
  `0e407fabb87f3e2511c28bbf312213fdcd406aa1646e2f085a2969afa77b8f0e`;
- Smart architecture guide:
  `318a92421a0af76af9ce6f86f67a3d0f7a59dcf23fddf7e6a609e9d2dd075975`.

## Static/source checks

- PHP lint: PASS for `src/Smart` and declarative rendering classes;
- product/resource JSON parsing with `jq`: PASS;
- `git diff --check`: PASS;
- shared Smart contract contains no Laravel/Illuminate import: enforced by
  `SmartRegistryTest`.
