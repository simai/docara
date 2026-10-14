# Bundled SIMAI Framework

Docara ships the SIMAI Framework bytes a site needs, so a build never fetches
them and needs no Framework checkout. Everything here is taken from one public
`simai/ui` release, named by its release lock v2 identity (`ui-<12>`):

| Path | What it holds | Refreshed by |
|---|---|---|
| `runtime-lock.json` | The Framework runtime record (Core and Smart at one `simai/ui` commit, the contract registry, boot paths, Smart components) plus the asset planner's records of superseded projections. It must equal the `runtime` of the starter and documentation-site locks. | both sync scripts below |
| `runtime-smart/<revision>/smart/` | Every conventional unminified Smart entrypoint of the release (`distr/smart/<component>/{js,css}/<component>.{js,css}`). The eager subset is the lock's `asset_projection`; the rest is `dynamic_asset_projection`, published for the browser Loader. | `scripts/sync-framework-smart-runtime.php` |
| `composition-runtime/<revision>/distr/` | The Composition Recipe runtime (the import closure of `core/js/composition/index.mjs`) and the Recipe contract lock with its normative files. Docara resolves every page through it with Node.js. | `scripts/sync-framework-composition-runtime.php` |
| `manifests/` | The `ui.alert` and `ui.button` component manifests; `provenance.upstream_revision` names the release commit. | by hand, with the lock's `manifests.*.sha256` |
| `view-utilities.json` | The utility classes Docara views may use, bound to the release and registry digest. | `scripts/sync-framework-rule-registry.php` |
| `portable-smart/`, `portable-smart-lock.json` | The portable Smart artifact packets Docara renders on the server. | separately, by packet |
| `assets/smart/` | The legacy portable Smart files, used only when a lock names a Smart revision that is not bundled. | not refreshed |

The Core runtime projection (rule registry, utility and component styles,
Core scripts, fonts and notices) lives in
`resources/portable/vendor/simai-framework/runtime/<revision>/` with its
`runtime-manifest.json`, and is written by
`scripts/sync-framework-rule-registry.php`.

## Moving to another release

1. Set the identity fields of `docs/site/simai-framework.lock.json` and
   `stubs/portable/simai-framework.lock.json` to the new release: `runtime.ui`
   and `runtime.ui_smart` (same commit; trees `distr` and `distr/smart`, mount
   `ui`; `sha256` is the SHA-256 of `git archive --format=tar <commit> <tree>`,
   `files` the number of blobs in the tree), `pair_id` `ui-<12>`, `bundle_id`,
   `framework_registry` (copy `contracts/generated/*` from the commit that binds
   the registry to the release), the `runtime_projection` source and paths, and
   the `asset_projection` and `dynamic_asset_projection` sources (provider
   `simai/ui`).
2. Seed `resources/portable/vendor/simai-framework/runtime/<revision>/` with an
   empty `runtime-manifest.json` and remove the previous revision directory.
3. Run, each with the path of a `simai/ui` checkout that has the commit (the
   scripts read Git objects, never the working tree):

   ```bash
   php scripts/sync-framework-rule-registry.php /path/to/ui
   php scripts/sync-framework-smart-runtime.php /path/to/ui
   php scripts/sync-framework-composition-runtime.php /path/to/ui
   ```

4. Remove the previous `runtime-smart/<revision>/`, update the manifests'
   `upstream_revision` and the locks' manifest digests, and record the previous
   lock in `runtime-lock.json` `asset_planner.superseded_framework_locks`
   (`runtime_sha256` is the SHA-256 of the canonical JSON of its `runtime`), so
   sites still pinning it build with the new bytes.

Nothing here claims production readiness of every Framework component; the
release lock's own `claims` apply.
