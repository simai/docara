# Verification

Date: 2026-07-27

## Exact selector coverage

An independent second extraction parsed the five live production CSS files and compared, in order:

- source path;
- source line;
- media-query context;
- selector group;
- normalized declarations.

Result:

```text
status=PASS
production_rules=147
prototype_selectors=64
total=211
source_hashes=PASS
exact_rule_comparison=PASS
prototype_comparison=PASS
decision_coverage=PASS
unique_ids=PASS
```

## Structured artifacts

```text
jq selector-ledger.json=PASS
jq framework-targets.json=PASS
expected counts=PASS
diff whitespace=PASS
```

## Immutable registry

```text
registry source commit=b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6
registry sha256=2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7
registry sha verification=PASS
```

## Reproduced lock blocker

```text
Docara lock compatibility ID=sf-v5.3.2-27f8af31-ab896dc7
immutable registry compatibility ID=sf-v5.3.2-7e836d8a-dd786bba
IDs differ=PASS (expected reproduction of the blocker)
```

This verifies the audit inventory and the inconsistency finding. It does not accept a corrected Framework tuple and does not authorize a release.
