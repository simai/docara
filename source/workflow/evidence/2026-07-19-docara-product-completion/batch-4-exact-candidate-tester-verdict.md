# Batch 4 — independent exact-candidate tester verdict

Date: 2026-07-19
Candidate: `adad417a9ea6cad98bc79650710a4d4e732f8cac`
Tree: `cb2939b33cab30ccfc5e7b0baddb933425fc4d68`
Parent: `4f901507186afa3f5582cc2bb4754148f0df2a5b`
Accepted baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Verdict: `PASS`

Status: bounded automated/tester PASS from the first acceptance attempt. The
later served-site smoke found `SP-001` on a physical `Cmd/Ctrl+K` path that was
not exercised by this matrix. This verdict remains valid for the checks listed
below, but it cannot accept or close Batch 4; a corrected immutable candidate
must receive a new exact verdict.

The tester used two byte-identical standard archives for the distribution and
a detached exact full tree for the internal tests deliberately excluded by
`export-ignore`. The archives embed the exact candidate SHA and have common
SHA-256 `5e078f344e85adf239c20f9d114b4cfbd812dcde7292a70b4e2e6ede215b55af`.

Checks passed:

- Composer strict and production/development platform requirements;
- Pint, 206 PHP files, 40 JSON files and 13 JavaScript files;
- five unique generated inline scripts from 44 generated HTML pages;
- authoritative PHPUnit: 465 tests, 2382 assertions, zero failures/errors;
- two clean production builds: 44 HTML pages, 4535 local references and zero
  broken references each;
- recursive build equality and path-independent digest
  `8726cbb745247cc09d2eaa4f179ca84b05187e083432d2fc93de0669ddfb3cbf`;
- exact Core CDN bytes, Smart projection hashes, lock mirrors, six fixed
  revisions and zero moving references;
- native Chrome storage disabled without injection, throwing getter, unusable
  storage probe, normal native-storage non-interference and honest volatility;
- Close focus at 390 and 1440 px in both Framework themes;
- complete baseline diff-check, secret gate, repository hygiene and nonclaims.

The first restricted PHPUnit run is excluded from the verdict: it produced
only three DNS failures for the existing JSONPlaceholder snapshot fixture. The
authoritative exact-tree repeat with that endpoint available passed.

Raw evidence:

- `/tmp/docara-b4-adad417-exact-tester/verdict.md`, SHA-256
  `e0e96247767d296ff09fb9ee05e87f7518345cc398f8331520a2d0b69d8171fe`;
- `/tmp/docara-b4-adad417-exact-tester/checks.json`, SHA-256
  `3716f33ebb4dbae5f0db72c5749761b85db2aaaa22e4ee6729d7daf34404400e`.

This PASS is bounded to the Batch 4 tester matrix. It does not by itself close
Batch 4 or claim public release, production, ecosystem or wider-Goal readiness.
