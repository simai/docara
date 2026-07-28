# Local deployment

Destination: `/Users/rim/Sites/docara.test/build_production`.

The verified candidate was copied to a same-site staging directory, verified
there, and swapped only after success. The final correction was published with
rollback backup:

`/Users/rim/Sites/docara.test/.docara-backups/declarative-demonstrator-20260721T021044/build_production`

HTTP smoke returned 200 for:

- `https://docara.test/authoring/regions/`
- `https://docara.test/examples/`
- `https://docara.test/examples/regions-disabled/`
- `https://docara.test/examples/smart-button/`

Rollback: move the current `build_production` aside and restore the retained
backup to the same path, then repeat static and HTTP smoke checks.

This is a reversible local ServBay publication, not a public or production
deployment.
