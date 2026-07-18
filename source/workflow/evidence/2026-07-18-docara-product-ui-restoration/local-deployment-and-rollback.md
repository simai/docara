# Local ServBay deployment and rollback evidence

Date: 2026-07-18
Target: `https://docara.test/`
Served path: `/Users/rim/Sites/docara.test/build_production`

## Publication result

The final source, staging, and served tree digests all matched:

`4568e6d8e48d45144d7b39bcd26ed8204c9428319a0210dd5b80511384270a46`

Before each write, the candidate was copied to a staging directory and passed
the static-build verifier. The current served directory was then moved to a
timestamped rollback path and the verified staging directory was atomically
renamed to `build_production`.

Final verification:

- 41 HTML pages;
- 3574 local references checked;
- zero broken references;
- fourth-level URL: HTTP 200, 60711 bytes;
- browser desktop/mobile/theme/keyboard checks: PASS;
- clean fresh-tab runtime log after final correction: `[]`.

## Rollback chain

The pre-product local site remains preserved at:

`/Users/rim/Sites/docara.test/.docara-backups/product-ui-20260718-2025/build_production`

Intermediate candidates are preserved at:

- `/Users/rim/Sites/docara.test/.docara-backups/product-ui-icon-fix-20260718-203509/build_production`
- `/Users/rim/Sites/docara.test/.docara-backups/product-ui-runtime-fix-20260718-204258/build_production`

To roll back, move the current `build_production` aside and atomically move
the chosen preserved directory back to the served path. Re-run the static
verifier, HTTPS smoke, and browser shell check before declaring recovery.

ServBay configuration, `.env`, dependencies, package releases, public sites,
and source repositories outside the clean worktree were not changed.
