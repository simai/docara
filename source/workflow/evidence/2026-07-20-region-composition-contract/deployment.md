# Local deployment

Date: 2026-07-20
Status: PASS

Target:
`/Users/rim/Sites/docara.test/build_production`

Action gate:
`source/output/action-gates/action-gate-report-20260720155403.json`

Final staged switch:

- stamp: `20260720T1620-region-composition-final`;
- source: `docs/site/build_production`;
- staging:
  `/Users/rim/Sites/docara.test/.docara-staging/20260720T1620-region-composition-final`;
- backup:
  `/Users/rim/Sites/docara.test/.docara-backups/20260720T1620-region-composition-final`;
- previous active digest:
  `0b265010543e0bca2ed631cdffff3a53c294dbbe47bb00ec498784fae1c7962e`;
- backup digest:
  `0b265010543e0bca2ed631cdffff3a53c294dbbe47bb00ec498784fae1c7962e`;
- source/staging/served normalized digest:
  `2c98bd9fc49b89a74af5d7e98947551e65e6734a23fc47cbe7c8826ed43d8ae6`.

Static verification after the switch:

- 115 HTML pages;
- 11,256 local references checked;
- zero broken.

HTTP responses matched local files byte-for-byte:

| URL | Status | SHA-256 |
| --- | --- | --- |
| `/` | 200 | `6a1db479ed5b857d19c3d2b05d6f516bb648aa6515d6c23f137e5ad0a915be94` |
| `/authoring/regions/` | 200 | `682752784047817ae1e65a7be9d9cde39127e4e2c44141a5056feaea26421026` |
| `/_docara/declarative-preview/` | 200 | `a50ad9377c0bdae7aace3c88cff407f06fa86b8fa89a8e757193ceeaee724c02` |
| `/_docara/declarative-preview/pages/authoring/regions/` | 200 | `c14fc8524823c91c46907299b05772aefda9b305109a514c5c72ad3f0b01afa8` |
| `/_docara/declarative-preview/index.json` | 200 | `f8b9ab328b37282e75bf4891de33ed61daa4b5ff928b5d5d92c40c97e6795b20` |

Rollback:

1. move current `build_production` to a new quarantine path;
2. move
   `.docara-backups/20260720T1620-region-composition-final/build_production.original`
   back to `build_production`;
3. rerun static verification and HTTP equality checks.

No ServBay configuration, database, public deploy, push, tag or release was
performed.
