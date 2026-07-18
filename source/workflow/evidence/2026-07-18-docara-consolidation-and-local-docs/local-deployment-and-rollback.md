# Local deployment and rollback evidence

Date: 2026-07-18
Site: `https://docara.test/`
Candidate: `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`
Verdict: **PASS**

## Preflight and backup

- Served path: `/Users/rim/Sites/docara.test/build_production`.
- Previous served tree: 50 HTML pages, relative tree hash
  `fdd1da06b21f165a7f3f601100c0eb9bf691e961b7e0f73ba795deb8150d2e8a`.
- Timestamped rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/2026-07-18T164341+0300-before-725f22b-build_production`.
- Backup hash after deployment and cleanup:
  `fdd1da06b21f165a7f3f601100c0eb9bf691e961b7e0f73ba795deb8150d2e8a`.
- Candidate static verifier before swap: 39 HTML pages, 398 local references,
  `broken: []`.
- Candidate tree hash before swap:
  `451224dd74da76301bad6888d65c5b42b708640d5aaed4b175a32f404aa63dc4`.

The deployment used a command-specific action gate. Its scope allowed only the
served static-output swap paths, forbade the local `.env` and site source, and
installed an automatic rollback trap for verifier or HTTPS failure.

## Atomic publication and smoke

The prior served output was moved to a temporary swap path, the verified
candidate was moved atomically into `build_production`, and the served tree was
verified again. These representative routes returned HTTP 200 and
`text/html; charset=utf-8`:

- `/`;
- `/start/`;
- `/components/`;
- `/components/alert/`;
- `/components/button/`;
- `/components/tabs/`;
- `/development/starter-mirror/`;
- `/build/php-only/`.

Post-deploy static verification reports 39 HTML pages, 398 checked local
references, and no broken references. The served tree hash is still
`451224dd74da76301bad6888d65c5b42b708640d5aaed4b175a32f404aa63dc4`.

## Rollback and cleanup

The deployment script would restore the swap tree automatically on any failed
postcondition. After independent tester, HCS, browser, HTTPS, static-verifier,
and hash acceptance, a second command-specific destructive gate approved
deleting only these temporary directories:

- `/Users/rim/Sites/docara.test/.docara-swap-old-4a312c1`;
- `/Users/rim/Sites/docara.test/.docara-deploy-725f22b`.

Both are absent. The timestamped backup remains present and unchanged. The
working site and backup hashes were rechecked after cleanup, and HTTPS root and
Button routes still return 200.

This is local ServBay acceptance, not a production or release-readiness claim.
