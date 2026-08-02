# Upgrade notes for planned Docara 2.0.0-rc.2

Status: planned, not published

1. Back up the complete current generated output and project-owned content,
   configuration and assets.
2. Install the exact dist package bound to source `56a2abf8…`; do not use a
   moving branch or `latest`.
3. Run `docara update --verify`, then create and inspect a dry-run plan.
4. Apply only the exact hash-bound plan. Project-owned Markdown, assets,
   `docara.json`, section/page settings and locale `lang.json` must remain
   unchanged.
5. Run full build, representative `--page` builds and static verification.
6. For `docara.test`, prepare a sibling same-filesystem directory and use the
   R2 atomic cutover dossier. Never overwrite the active directory in place.
7. On any stop threshold, restore the complete prior output directory and
   repeat the required smoke before reopening traffic.

Legacy public language-pack files are engine-owned obsolete surfaces. The
accepted update removes only the engine-owned schema; user content and locale
`lang.json` remain project-owned.
