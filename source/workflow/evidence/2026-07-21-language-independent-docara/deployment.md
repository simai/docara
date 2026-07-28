# Local deployment evidence

Date: 2026-07-21
Verdict: PASS

Target: local ServBay site `https://docara.test/` only.

- deployment ID: `language-i18n-20260721-120942`;
- source: `docs/site/build_production`;
- staging: `/Users/rim/Sites/docara.test/.docara-staging/language-i18n-20260721-120942`;
- backup: `/Users/rim/Sites/docara.test/.docara-backups/language-i18n-20260721-120942/build_production`;
- the previous served tree was copied and hash-checked before the atomic swap;
- staged, source and deployed manifests match exactly;
- deployed static verifier: 154 HTML pages, 16079 local references, zero
  broken references;
- `/` and `/authoring/localization/` both return HTTP 200 after publication;
- rollback source remains available in both the backup and the staging
  `served-before` tree.

No `.env`, source checkout, vendor dependency, public host, release, branch or
tag was changed by publication.
