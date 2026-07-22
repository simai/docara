# Local publication and rollback

Date: 2026-07-22
Status: PASS
Target: `https://docara.test/`

The action gate passed and recorded:

`source/output/action-gates/action-gate-report-20260722134254.json`

The verified candidate was copied into same-filesystem staging, verified,
then atomically renamed to:

`/Users/rim/Sites/docara.test/build_production`

The previous served tree is retained unchanged at:

`/Users/rim/Sites/docara.test/.docara-backups/search-final-20260722-164301/build_production.previous`

Post-publication verification:

- 271 HTML pages;
- 20,512 local references;
- 0 broken references;
- served-tree digest:
  `3886d84b96e68541ce542ada96b068a098366a40a4740a3c35b37238668df154`.

Rollback is the reverse same-filesystem rename: move the current served tree
aside, restore the retained `build_production.previous` as
`build_production`, then repeat static and HTTPS/browser smoke checks.

No `.env`, ServBay configuration, public remote, branch, tag or release was
changed.

## Contextual excerpt follow-up

The corrected search build was atomically published on 2026-07-22 at
`16:57:17` local time. The immediately previous served tree is retained at:

`/Users/rim/Sites/docara.test/.docara-backups/search-context-20260722-165717/build_production.previous`

Post-publication static verification again returned 271 pages, 20,512 local
references and 0 broken references. The served browser query `устано` passed
the exact visible-match acceptance described in `acceptance.md`.

## Minimum typography follow-up

The verified `1/3` typography build was atomically published on 2026-07-22 at
`17:13:36` local time. The previous served tree is retained at:

`/Users/rim/Sites/docara.test/.docara-backups/search-type-20260722-171336/build_production.previous`

Static verification remained 271 pages, 20,512 references and 0 broken.
