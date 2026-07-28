# Local ServBay root recovery

Date: 2026-07-19
Scope: local `docara.test` runtime only
Result: recovered; accepted Batch 4 remains served

## Incident

ServBay regenerated its Caddy configuration with
`/Users/rim/Sites/docara.test` as the document root. The accepted static tree
was one level lower in `build_production`, so `/` returned 404 while the
subdirectory remained reachable.

## Backup and rollback

Before changing the local ServBay site setting, the existing runtime files
were copied to:

`/Users/rim/Sites/docara.test/.docara-backups/servbay-root-recovery-20260719-1124`

Recorded SHA-256 values:

- `config.data`:
  `b284784abf35c7a7eef3f2c3419230a5bed77c535a12e096e54be99935cd3657`;
- `Caddyfile`:
  `6e602832b7e6a4487959fc1ce61b0847f7abd72f738738030ce95c8c47fcef3c`;
- `autosave.json`:
  `80098802c98f193ab83d15e71b353fc576e459772fb160bd9ab21395cd7c5d6b`.

The local action-gate report is:

`source/output/action-gates/action-gate-report-20260719082221.json`

Rollback is to restore those three files through ServBay and select the former
site root.

## Current state

The persistent ServBay site setting now generates:

`root * "/Users/rim/Sites/docara.test/build_production"`

in `/Applications/ServBay/package/etc/caddy/Caddyfile`. HTTPS smoke:

- `/` — 200;
- `/authoring/reader-settings/` — 200;
- `/components/alert/` — 200;
- `/404-not-real` — 404.

This recovery changed only the local runtime root. It did not publish the
rejected Batch 5 candidate, change repository history or make a release
readiness claim.
