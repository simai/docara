# M3 reverse-outcome audit

Date: 2026-08-02

Runtime parent: `59427fd7307d0981fa1627f83df4068c5d53fada`

Verdict: PASS

## Reverse test of the requested outcome

1. **Can a Russian component page still be generated without its Markdown
   owner?** No. The final inventory binds 32 routes to 32 physical files;
   generated Russian component routes, RU allowlist entries and RU localized
   catalog examples are zero.
2. **Can public component prose still come from the Russian package language
   pack or manifest?** No. The pack has zero messages and no component prose;
   boundary tests reject page prose in config, manifests and `lang.json`.
3. **Is there a second route-specific engine?** No. The codebase has one exact
   `PageBuilder`, `DocumentRendererRegistry`, `SmartComponentGateway` and
   generic `ComponentBlockNode`; route-family scans find no Alert-specific or
   peer-specific pipeline class.
4. **Does isolated build enter unrelated Russian component work?** No. Early
   selection observer tests prove it does not compile other physical pages or
   run irrelevant catalog/example projections. All 32 selected results match
   their full-build HTML byte for byte.
5. **Are index and reader projections a second prose source?** No. The physical
   index owns its prose; its list, navigation, breadcrumbs, outline,
   previous/next and search consume PageBuilder results/route metadata.
6. **Was legacy removed without proof?** No. `old-to-new-map.json` records old
   hashes, replacements and rollback. Sources still consumed by other locales
   remain; only zero-reference Russian projections and zero-page public assets
   were retired/suppressed.
7. **Is the result reproducible and usable?** Yes. Two clean full trees have
   identical hash `4aa179bde88d4391cd6b4a3ddeb112d0ef5ff6db2d04b6ec725d897fe0a29426`;
   both have zero broken references, and the desktop/mobile light/dark browser
   matrix plus all-route smoke pass.

## Notes and nonclaims

- The accepted result is the whole Russian `/ru/components/` M3 scope, not all
  locales or every public site route.
- Project-wide legacy retirement remains a later bounded stage wherever a
  proved consumer still exists.
- Merge, push, tag, release, deploy and production readiness were not performed
  or claimed.
