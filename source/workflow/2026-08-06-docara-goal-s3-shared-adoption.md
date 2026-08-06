# Goal S3 — Shared adoption, public documentation and integrated acceptance

Date: 2026-08-06
Status: `complete`
Track: `docara.track.surface-hero-media`
Entry product: `7eeba4ad7b5acd00f833bf2022e45775444fb69c`
Entry governance: `98934cb847de01180f1584908e3c7338ade17f2e`
Target state: `surface_hero_track_ready_for_user_decision`

Final product candidate: `dd2c0d623f0757e172861fdac959b839a7fff495`
Exact evidence: `source/workflow/evidence/2026-08-06-docara-surface-hero/S3-SHARED-ADOPTION.md`

## Authority and routing

Independent reverse-outcome audit accepted Goal S2/S2-C1 with
`PASS_WITH_NOTES`. The accepted package reproduction command includes the exact
input `--tag=v2.0.0-alpha1-s2c1`; the product bytes are unchanged.

The active federation resolver still routes to the disabled stale Docara skill.
Repository specification, graph, this track and handoff therefore remain the
source of truth, with `dev`, `docs`, `tester` and `graph` as the explicit owner
fallback. This graph gap does not expand scope.

## Frozen default baselines

The following exact renderer outputs are frozen before shared adoption:

| Semantic block | SHA-256 | Bytes |
| --- | --- | ---: |
| Hero default split | `886d1e4b0b2066004431427c1f69e2b0d34b2ce71b4960db16ebf1e667cb9684` | 564 |
| Showcase default | `402551dc2dd75537370a195b67099d7e12a9e9394cee0ab2c2460bff6d0a6405` | 936 |
| Promo default | `50a55ea25e32533c84a0d2e3f1fd21fdf5082cccfe702243eacdfa2bc8b73648` | 919 |

Goal S2 public build baseline remains 393 files at canonical digest
`108cba014e31c4aad885242e69fda054f8f5aeaf1c7d8066f7eb842099f9eddb`.
Static verification is 266 HTML / 35,583 references / broken=0.

## Batches

### S3.0 — entry freeze

- record independent S2 acceptance and exact package tag;
- freeze semantic HTML, public build, asset, hydration and browser baselines;
- inventory duplicate outer Surface construction and rollback boundary.

### S3.1 — one shared presentation implementation

- route Surface, Hero, Showcase and Promo outer presentation through one
  immutable `SurfacePresentation` implementation;
- preserve semantic content ownership and exact default bytes;
- keep Hero background media on the same implementation;
- retire duplicate outer frame construction only after parity and zero refs.

### S3.2 — public contract and documentation

- complete Surface, containers, Hero, catalog, Atlas and authoring guidance;
- publish copyable contained/full/background Surface and Hero side/background
  examples;
- document Surface versus Hero, double-wrap rejection, accessibility,
  responsive, failure and local-only security behavior;
- do not invent global settings or unsupported author props.

### S3.3 — integrated verification

- focused and full tests, schema/catalog/Atlas/security/container checks;
- preview/production parity, full/full/single determinism and static verifier;
- proportional package and fresh-consumer verification;
- browser landing/docs/isolated matrix at 320/390/768/1024/1440,
  light/dark, LTR/RTL, keyboard/focus, reduced motion, contrast, overflow and
  local asset network/console checks.

### S3.4 — terminal handoff

- bind specification, roadmap, acceptance, graph/generated context and
  ACTIVE/START/NEXT/RESULT/STATUS to one exact candidate;
- preserve rollback and content-addressed evidence;
- stop at `surface_hero_track_ready_for_user_decision` without S4, release or
  deployment.

## Invariants and stop conditions

One Markdown source, typed in-memory IR, renderer registry, Smart Gateway,
LayoutComposer and PageBuilder remain authoritative. Project configuration may
not provide CSS, class, PHP, callback, template or filesystem paths. No second
background engine or component-ID/namespace dispatch is allowed.

Stop if default semantic/visual parity cannot be preserved, if safe retirement
lacks zero-reference/rollback proof, if a second pipeline is required, or if
overlapping user/external-owner changes appear.

## Rollback

Before the product commit, rollback is the accepted entry product
`7eeba4ad7b5acd00f833bf2022e45775444fb69c`. After each bounded commit, revert
only that commit; never rewrite history or touch external sites.
