# Docara 2.0.0-rc.2 changelog draft

Status: planned, not published

## Added

- typed in-memory Document IR and one renderer registry;
- one Smart Component Gateway for block and inline components;
- deterministic release archive, manifest, checksums and SBOM;
- ownership-aware init/update verify/dry-run/apply/rollback lifecycle;
- physical Markdown owners, front matter and explicit locale missing-page
  policy;
- semantic source/package documentation gates.

## Changed

- full and single-page builds now differ only by selected route set;
- navigation, search, outline and component views derive from PageBuilder
  metadata;
- shared public translations use `content/<locale>/lang.json` only.

## Removed

- generated public component/example owners and allowlists;
- public language-pack schema/data/runtime;
- `trustedMainHtml`, generated-page bypass and parallel public projectors.

## Compatibility

- PHP `^8.2` and Composer 2;
- immutable SIMAI Framework tuple from the packaged runtime lock;
- no compatibility contract for unpublished Docara 1 experimental storage.
