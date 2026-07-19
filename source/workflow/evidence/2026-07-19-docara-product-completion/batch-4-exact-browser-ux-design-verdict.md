# Batch 4 — exact browser, UX and design verdict

Date: 2026-07-19
Candidate: `adad417a9ea6cad98bc79650710a4d4e732f8cac`
Tree: `cb2939b33cab30ccfc5e7b0baddb933425fc4d68`
Accepted baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Attempt verdict: `PASS`; integration status: superseded by served-site smoke

The matrix below used programmatic activation for part of mutual-exclusion
coverage and did not exercise the physical document-level `Cmd/Ctrl+K` path.
The later served-site smoke found `SP-001`: search and reader settings could be
open simultaneously. This attempt therefore cannot accept or close Batch 4,
despite the bounded checks below having passed. A corrected immutable candidate
must repeat the browser matrix with real keyboard shortcuts.

The reviewer built the site from an exact standard archive and used native
Google Chrome without browser injection. The exact build contained 44 HTML
pages and 4535 verified local references with zero broken references.

Accepted matrix:

- normal storage: cold start, system/light/dark, persistence, navigation,
  reset, inherited defaults, live OS changes and cross-tab synchronization;
- native `--disable-local-storage`: visible body, complete Framework loader,
  25/25 icons, honest page-lifetime memory marker and reset after reload;
- exact pinned Core in every scenario and no unadmitted Smart modal/dropdown;
- Enter, radio arrows, Escape, focus restoration, visible Close focus and
  search/settings mutual exclusion;
- 390, 900 and 1440 px in light and dark, with no overflow, clipping, header
  overlap, unreadable labels, theme drift or targets below 44 px;
- all eight screenshots visually accepted;
- zero page errors, console errors/warnings or request failures.

Raw evidence:

- `/tmp/docara-b4-adad417-exact-browser/verdict.md`, SHA-256
  `4516facc286ddc3209c5b36718a287554b36987764575b589dc0a2a392f47a15`;
- `/tmp/docara-b4-adad417-exact-browser/checks.json`, SHA-256
  `ffa4ddc8514c0cb2cf008cf117b781a7722d7602c78f28b3223d76669048c888`.

This PASS covers the Batch 4 browser/UX/design slice only. It makes no public
release, production, ecosystem or wider-Goal readiness claim.
