# C3 integrated source verification

Candidate parent: `13bcf13`

Authoritative runtime: macOS PHP `8.4.20`.

```text
PHPUnit: 393 tests, 7,173 assertions, PASS
Pint --test: PASS
Composer validate --strict --no-check-publish: PASS
PHP lint: 237 files, failures=0
JSON parse: 437 files, failures=0
YAML parse: 153 files, failures=0
Project graph: 1 goal, 8 stages, 11 batches, 4 metrics, 6 mappings
Graph warnings=0; blockers=0
git diff --check: PASS
```

Composer under PHP 8.4 emitted dependency deprecation notices but returned a
valid strict result; these are environment notices, not validation failures.

The commit containing this evidence is the intended exact product-source
boundary for the new unpublished `2.0.0-rc.3`. Later evidence-only governance
commits are not alternate package sources.
