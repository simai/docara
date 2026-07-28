# Local deployment: Docara Smart search trigger

Date: 2026-07-22
Verdict: PASS

## Target

- source build: `docs/site/build_production`;
- local ServBay target: `/Users/rim/Sites/docara.test/build_production`;
- public URL: `https://docara.test/ru/migration/legacy/`;
- response after publication: HTTP 200.

## Safety

The preflight action gate passed and recorded
`source/output/action-gates/action-gate-report-20260722115404.json`.
Source and target are on the same filesystem. The verified build was copied to
a staging directory and switched with directory renames; ServBay configuration
and processes were not modified.

## Integrity

- source/deployed page SHA-256:
  `69e3523672cbb7ca848f702b5722a1107f285994263b9cdd18eb211e900346a7`;
- source/deployed shell CSS SHA-256:
  `64bf05c5d9be76bd292940a9928c448ff37ec896e87d4b439345dcdcccd35157`.

## Rollback

Restore the previous directory from:

`/Users/rim/Sites/docara.test/.docara-backups/smart-search-trigger-20260722-145426/build_production.previous`

No public push, merge, tag, release or production deployment was performed.
