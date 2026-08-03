# Goal 2-C correction evidence index

Date: 2026-08-03
Status: `in_progress`
Input revision: `4c0f623403773c00669ef53782785d750839de0a`
Rejected candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`
Audit marker: `019fc4b1-57f1-7f10-a7e0-92a9322f4bee`

Evidence will be added by bounded batch. Executor evidence cannot self-accept
Goal 2 and cannot authorize Goal 3.

## C2.0 recovery and RED contract

- exact input branch: `codex/docara-unified-architecture`;
- exact input revision: `4c0f623403773c00669ef53782785d750839de0a`;
- input worktree: clean;
- preview receipt was a normal `docara.resolved_page_plans.v1` without build
  purpose, so the static verifier accepted it;
- preview publication contained only HTML/JSON and omitted absolute local
  assets;
- Alert dependency closure included all project `smart/`, including
  unrelated `project.notice`;
- `oneOf` had no implementation in `JsonSchemaValidator`, so an invalid kind
  bypassed schema validation.

## Implementation checkpoint (working tree)

Commands:

```text
/Applications/ServBay/package/php/8.4/current/bin/php vendor/bin/phpunit --filter 'PreviewKernelTest|DesignRegistryTest|StaticBuildVerifierTest'
/Applications/ServBay/package/php/8.4/current/bin/php vendor/bin/phpunit --filter 'Preview|DesignRegistry|ProjectDesignComposition|StaticBuildVerifier|PortableSiteBuilder|Declarative'
/Applications/ServBay/package/php/8.4/current/bin/php vendor/bin/pint --test
git diff --check
```

Results:

- focused: 30 tests, 246 assertions, PASS;
- broader affected surface: 113 tests, 1,572 assertions, PASS;
- Pint and diff check: PASS.

Candidate SHA and final artifact/browser hashes are intentionally deferred
until the runtime checkpoint is committed and exact-revision acceptance runs.
