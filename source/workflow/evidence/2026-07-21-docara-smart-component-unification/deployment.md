# Local deployment

Status: PASS
Candidate: `46fefd88d4031a1a5bcba551fef9bdc6c04b2edf`
Target: `https://docara.test/`

The deterministic `docs/site/build_a` tree was verified before publication:

- 169 HTML pages;
- 20,176 local references;
- zero broken local references;
- 191 files;
- byte-identical to the independently generated `build_b` tree.

The previous local build was copied to:

```text
/Users/rim/Sites/docara.test/.docara-backups/build-production-before-smart-unification-20260721-155732
```

The candidate tree was staged and atomically swapped into:

```text
/Users/rim/Sites/docara.test/build_production
```

HTTP smoke checks returned `200` for the home page, the Smart architecture
guide, the product Smart demonstrator, the isolated demonstrator result and the
component-owned navigation JavaScript asset. Served HTML contains the
canonical Smart component attributes and all five registered component assets.

Rollback is a bounded directory swap from the timestamped backup above. No
public push, release, tag or production deployment was performed.
