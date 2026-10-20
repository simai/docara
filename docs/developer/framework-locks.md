# Framework locks and component packages

## The site lock

`simai-framework.lock.json` (`docara.framework_lock.v1`) pins one public
`simai/ui` release, named by its release lock v2 identity (`ui-<12>`): Core
in `distr/` and Smart in `distr/smart/` of the same commit. `runtime.ui` and
`runtime.ui_smart` name that commit with trees `distr` and `distr/smart` and
mount `ui`; the projections come from provider `simai/ui`.

Docara bundles the bytes of the release it pins, including the Composition
Recipe runtime (see [resources/framework/README.md](../../resources/framework/README.md)),
so a build needs no Framework checkout. `DOCARA_SIMAI_UI_ROOT` (or
`SIMAI_UI_ROOT`) may name another distribution whose Recipe runtime implements
the same specification edition; ordinary builds do not set it.

Locks of the older two-repository shape (`ui-<12>-smart-<12>`) are still read.
A site pinning the lock Docara 2.18 shipped builds with the bundled release; a
lock naming another Smart revision falls back to the legacy portable Smart
copy in `resources/framework/assets/`.

## Product component packages

Beside the free release, a lock may carry `package_projections`: product
component packages (commercial SIMAI packages), served under their own
mount. A package is built by the SIMAI Framework builder (`sf-build package`)
from a folder of the product's UI repository and taken by its package receipt
(`simai.framework.product-package-receipt` version 1):

```bash
php vendor/simai/docara/scripts/sync-framework-package-runtime.php \
  --receipt /absolute/out/package-receipt.json \
  [--from /absolute/out | /absolute/out/package-<name>.tar] \
  --lock /absolute/site/simai-framework.lock.json
```

The package name, its source revision and the free release it must be served
beside come from the receipt; the script refuses a receipt whose
`framework.release` is not the lock's `runtime.pair_id`. Files are read from
the archive the receipt names (its SHA-256 must match) or from an output
directory holding `smart/`, and each is checked against the receipt's hash and
size. The rule file and each component's script and stylesheet are written to
`resources/framework/runtime-packages/<name>/<package revision>/`, and the
lock records the receipt's identity and the files' hashes, never the bytes.

A build publishes them under
`_docara/framework-packages/<name>/<package revision>/smart/` and declares
`SF_BOOT_CONFIG.packages.<name> = {base: '.../smart'}` before `core.js`; the
Loader fetches `rule.js` itself. Package bytes are never committed
(`resources/framework/runtime-packages/` is ignored); a site lays them into
its installed copy at build time. Run the script once per receipt to serve
several packages.

The script ships in the Composer package (Composer installs the tagged source
tree) but not in the release ZIP, whose contents are listed in
`resources/release/package-surface.json`.
