# Local ServBay publication plan

Target: `https://docara.test/`

Source:
`docs/site/build_production`

Source tree digest before publication:
`94872dc8627fac21cbc5c0fed8f6a9515b7fdb35d7e75580b49656ce4162eccf`

Final corrected source/served tree digest:
`94872dc8627fac21cbc5c0fed8f6a9515b7fdb35d7e75580b49656ce4162eccf`

Current served tree digest before publication:
`d1ce27750983da6d7c337f0010486fd44a759746d1b3cfeb8d75c544aa5d9131`

## Safe write

1. Copy the already verified source tree to a staging directory inside
   `/Users/rim/Sites/docara.test`.
2. Run `scripts/verify-static-build.php` against that staging tree.
3. Move the currently served `build_production` to
   `.docara-backups/product-ui-final-20260718-215550/build_production`.
4. Atomically rename the verified staging directory to `build_production`.
5. Verify the published digest, HTTP 200, local references and browser shell.

## Rollback

If static verification, HTTP smoke, console, navigation, theme or responsive
acceptance fails, move the candidate aside and restore the saved
`build_production` directory from the backup path. Do not change ServBay
configuration, project sources, `.env`, dependencies or public releases.

The action-gate preflight is recorded in
`source/output/action-gates/action-gate-report-20260718172432.json` and requires
safe write, backup and rollback evidence.

The safe publication completed. Exact staging, digest, HTTP, browser, and
rollback evidence is recorded in `local-deployment-and-rollback.md`.
