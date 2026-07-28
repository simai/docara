# Local publication

Destination: `/Users/rim/Sites/docara.test/build_production`

Preflight classified the operation as low-risk reversible local publication.
The candidate was copied to a same-site staging directory, passed static
verification there, and then replaced the local destination by a guarded
same-filesystem move.

Rollback backup:

`/Users/rim/Sites/docara.test/.docara-backups/region-composition-20260721T063332Z/build_production`

Post-publication evidence:

- served output digest equals both clean-build digests:
  `68475469b5a1e84e85bdc07ae4da9ad30bb2e3b9c5d69c37154c8578adde92d3`;
- static verification: 152 HTML pages, 15,489 references, zero broken;
- HTTP 200: catalogue, five detail pages and five exact result routes;
- desktop/mobile/light/dark browser acceptance: PASS.

Rollback: move the current destination aside, restore the retained backup to
`/Users/rim/Sites/docara.test/build_production`, then repeat static, HTTP and
browser smoke checks.

This is a reversible local ServBay publication, not a public or production
deployment.
