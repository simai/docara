# S1 baseline and Surface implementation evidence

## Exact boundary

- branch: `codex/docara-unified-architecture`;
- entry HEAD: `8e4b71c58065a1f49382dd8077809363e0eed873`;
- accepted parent product: `eb35f5c6f18e5eb9be69e91887b09486f5703136`;
- implementation checkpoint: `6348bd39da15628ba540b0d7761106d72f881b23`;
- entry worktree: clean.

## Reproduced RED

Two entry-HEAD documentation builds produced 132 routes, 391 files and 264
HTML with identical complete-tree digest
`44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4`.
Both static checks reported 35,044 local references and `broken=[]`.

The landing Hero was a direct child of `.docara-content.container`, while the
only full-width CSS selector required an intermediate
`[data-docara-section][data-docara-region-owner=main]`. Therefore the emitted
`data-docara-width=full` request was not applied to the actual DOM.

## GREEN implementation checkpoint

- one typed `docara.surface` definition and one immutable
  `SurfacePresentation` primitive;
- direct-child plus existing wrapped-child Layout selector, using the existing
  `100cqw` geometry rather than `100vw`;
- closed enum/token props and a decorative local image layer with empty alt;
- registry-owned `content.embeddable` capability and exact
  slot/count/order/depth contract;
- local asset admission rejects protocol/remote/traversal/missing, symlinked
  root/file, hardlink, case mismatch and unsafe extension before render;
- nested Surface and Hero/Showcase/Promo remain forbidden;
- project `project.product-configurator` is admitted by provider-owned
  capability and renders through the same Smart Gateway/renderer instance.

Commands:

```bash
/Applications/ServBay/package/php/8.4/8.4.20/bin/php vendor/bin/phpunit tests/Unit/SurfaceRuntimeTest.php
/Applications/ServBay/package/php/8.4/8.4.20/bin/php vendor/bin/phpunit --filter 'SurfaceRuntimeTest|EffectiveComponentCatalogTest|StaticBuildVerifierTest|PortableSiteBuilderTest'
/Applications/ServBay/package/php/8.4/8.4.20/bin/php vendor/bin/pint --test
git diff --check
```

Results: Surface security/runtime 5 tests / 43 assertions; focused
catalog/build contour 78 tests / 2,291 assertions; Pint and diff check PASS.

## Boundary and rollback

No external repository or site was written. Revert bounded S1 commits to return
to entry HEAD; ignored build roots are disposable. Hero background mode,
homepage art direction and S2/S3 are explicit nonclaims.
