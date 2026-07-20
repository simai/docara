# Local deployment

Target:
`/Users/rim/Sites/docara.test/build_production`

The local deployment action gate passed before mutation. The final candidate
was built twice, statically verified in staging, and promoted using a
same-filesystem directory rename.

Final backup:

`/Users/rim/Sites/docara.test/.docara-backups/view-composition-20260720-213008/build_production`

Final candidate and served aggregate SHA-256:

`6a2a97198b07a30326ad98237c381b2a8600b54a26141e9cba0be155508914e8`

Post-deployment checks:

- `https://docara.test/` — HTTP 200;
- `https://docara.test/authoring/regions/` — HTTP 200;
- declarative regions preview — HTTP 200;
- four-level declarative navigation preview — HTTP 200;
- static verifier — 115 pages, 11,258 references, zero broken.

Rollback is a same-filesystem replacement of the served directory with the
preserved final backup followed by `verify-static` and the same HTTP checks.
ServBay configuration, credentials and `.env` were not changed.
