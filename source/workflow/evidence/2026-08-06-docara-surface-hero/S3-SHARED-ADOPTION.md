# Goal S3 shared adoption and integrated acceptance

Date: 2026-08-06
Product candidate: `dd2c0d623f0757e172861fdac959b839a7fff495`
State: `surface_hero_track_ready_for_user_decision`

## Outcome

`docara.surface`, `docara.hero`, `docara.showcase` and `docara.promo` now use
one `SurfacePresentation`. Hero, Showcase and Promo keep their own semantic
content contracts; authors do not add a Surface wrapper around them. No second
parser, renderer, registry, Gateway, LayoutComposer, PageBuilder or background
engine was introduced.

Frozen default renderer outputs remain byte-identical:

| Block | SHA-256 | Bytes |
| --- | --- | ---: |
| Hero | `886d1e4b0b2066004431427c1f69e2b0d34b2ce71b4960db16ebf1e667cb9684` | 564 |
| Showcase | `402551dc2dd75537370a195b67099d7e12a9e9394cee0ab2c2460bff6d0a6405` | 936 |
| Promo | `50a55ea25e32533c84a0d2e3f1fd21fdf5082cccfe702243eacdfa2bc8b73648` | 919 |

The docs homepage PageBuilder content hash remains
`6ddf36545bd418eb503876bc712f66b415d068eb94bc477c645d6b4bfefadbfb`.
Typed IR contains each semantic block once. Structural regression proves that
the two meaningful-media call sites delegate to `renderSemanticFrame()` and
that duplicate outer-frame builders are absent from `PortableMarkdownRenderer`.

## Tests and dependency security

- focused shared Surface/docs/runtime contour: 101 tests / 2,604 assertions;
- exact documentation/application contract contour: 5 tests / 1,979 assertions;
- full PHPUnit: 505 tests / 10,689 assertions;
- Pint, PHP lint and `composer validate --strict`: PASS;
- `composer audit --locked`: no advisories on the immutable generated lock.

The first local lock still resolved `league/commonmark 2.8.3`, for which the
current advisory feed reports fixed vulnerabilities. The final evidence lock
resolves `2.9.0`; the tracked library constraint remains `^2.4`, and no tracked
dependency or runtime contract was changed. Composer's PHP 8.4 deprecation
notices are tool-owned and are not hidden.

## Exact public build

Commands were run from `docs/site` with ServBay PHP 8.4.20:

```text
php ../../docara build build_s3-exact-a
php ../../docara build build_s3-exact-b
cp -R build_build_s3-exact-a build_build_s3-exact-single
php ../../docara build build_s3-exact-single --page=/ru/components/surface/
php ../../docara verify-static build_build_s3-exact-{a,b,single}
```

All three trees contain 393 files and have canonical digest
`99ab56dfd80ce08bfb92f12ebccc3a0e4617de75b71b4ddfdf973a6887bd98ab`.
The digest is SHA-256 of records sorted by relative path, each record formatted
as `<file-sha256>  <relative-path>\n`. Static verification for every tree is
266 HTML / 35,596 local references / broken=0.

## Package and fresh consumer

Two `git clone --no-local` roots built the exact candidate with:

```text
php scripts/build-release-package.php --revision=dd2c0d623f0757e172861fdac959b839a7fff495 --version=2.0.0-alpha1 --tag=v2.0.0-alpha1-s3 --output=<isolated-output>
```

- ZIP: `4c5496b35430c62a16d8a26558783c0cefdd983b12607d005f1f8b564713646c`;
- external manifest: `8c196a3b4b4d6a35c56c0e663a7d9eff21b42c5ce554bf19cb59971d1014539c`;
- checksum file: `bb59d1f5d27c554de3ad77f0bfb82ff705203994c7277b0d4dd35101410aeddd`;
- 869 files; both repository verifiers PASS.

A fresh Composer dist consumer uses lock
`8115c5f63f1f5002d09c64fda5659d10fa78b320e1e0b743887555725635da05`.
It has no package `.git` or `node_modules`, passes init, doctor, full, selected
single and static verification, and its 198-file full/single trees share digest
`8c1ed0c7e9011e082d199d5f877f373df387da87f22d16d57ae6f773f2536447`
(78 HTML / 3,931 references / broken=0).

## Browser evidence

The exact 393-file build was served from an isolated local HTTP root. Landing,
Surface docs and Hero docs were checked at 320/390/768/1024/1440 in light LTR
and dark RTL: 30 scenarios, no horizontal overflow, reduced-motion honored,
local assets successful and console warnings/errors empty. Landing retained one
Hero, Showcase and Promo and the accepted full-bleed geometry. Surface docs
kept all five examples inside `main`; the decorative layer count was one.
Settings Escape returned focus and keyboard Tab produced a visible focus target.

Screenshots:

- landing desktop: `e2a1042ac1231c186434afd44c84f755a96bf155155388013e8442f806995ffc`;
- landing mobile: `434c5ab623d9b1e047c8f64942c18fc6b0847646cf6627a71ceb5faab043f08f`;
- Surface docs mobile: `fb8a8e2ec2d7b88d33993c8134224e6def5591b4f7a99a148d47869c2b076874`.

## Rollback and boundaries

Rollback product is accepted S2 candidate
`7eeba4ad7b5acd00f833bf2022e45775444fb69c`. Revert bounded commits in reverse
order; do not rewrite history. No homepage art direction, external repository,
site, merge, push, tag, release or deployment was changed. There is no S4 or
Goal D; the only next step is an explicit user decision.
