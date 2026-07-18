# Local ServBay publication plan

Target: `https://docara.test/`

Source:
`docs/site/build_production`

Source tree digest before publication:
`55f591ada243908dce687f044ce93bc6613fd108a280c1b179a73066d6448652`

Final corrected source/served tree digest:
`4568e6d8e48d45144d7b39bcd26ed8204c9428319a0210dd5b80511384270a46`

Current served tree digest before publication:
`451224dd74da76301bad6888d65c5b42b708640d5aaed4b175a32f404aa63dc4`

## Safe write

1. Copy the already verified source tree to a staging directory inside
   `/Users/rim/Sites/docara.test`.
2. Run `scripts/verify-static-build.php` against that staging tree.
3. Move the currently served `build_production` to
   `.docara-backups/product-ui-20260718-2025/build_production`.
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
