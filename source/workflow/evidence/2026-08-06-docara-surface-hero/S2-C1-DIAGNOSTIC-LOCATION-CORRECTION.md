# S2-C1 diagnostic-location correction

Date: 2026-08-06
Status: `ready_for_independent_audit`
Entry product candidate: `794fac076be86ed4d03167120800ab0e91715aff`
Exact product candidate: `7eeba4ad7b5acd00f833bf2022e45775444fb69c`

## Outcome

The production PageBuilder now preserves the authored Markdown image location
when the unchanged generic URL policy rejects a Hero image. The correction
relocates the existing exception to `/document/hero/image`; it does not accept
new URL classes, change Hero media semantics or introduce another parse/render
path.

The permanent real-PageBuilder matrix proves line 5, column 1 for `data:`,
`javascript:`, protocol-relative, remote, missing, traversal, case-mismatched,
symlink and hardlink images. Related image structure, `media=none` and alt-mode
failures use the image location; directive prop and variant errors continue to
use the directive opener.

## RED to GREEN

Before the correction, the line-5 fixture failed with:

`MARKDOWN_HERO_IMAGE_UNSAFE ... content/ru/components/hero-s2-contract.md:1:1`

After the correction:

- `HeroMediaRuntimeTest`: 9 tests / 152 assertions;
- Hero/Surface/Atlas/PageBuilder/build contour: 74 / 1,485;
- full PHPUnit: 502 / 10,650.

## Exact builds and static verification

Commands:

```text
php docara build s2c1-final-a
php docara build s2c1-final-b
cp -R docs/site/build_s2c1-final-a docs/site/build_s2c1-final-single
php docara build s2c1-final-single --page=/ru/components/hero/
php docara verify-static docs/site/build_s2c1-final-a
php docara verify-static docs/site/build_s2c1-final-b
php docara verify-static docs/site/build_s2c1-final-single
```

The three roots contain 393 files and are byte-identical. Their canonical
path-sorted digest is computed over records formatted as
`<file-sha256><two spaces><relative-path><newline>`:

`108cba014e31c4aad885242e69fda054f8f5aeaf1c7d8066f7eb842099f9eddb`

Each static result is 266 HTML / 35,583 local references / `broken=[]`.
The former `7a8c5645…` ledger belongs to rejected candidate `794fac0…`.

## Package and fresh consumer

Two independent `git clone --no-local` roots at the exact candidate built and
verified the same unpublished 869-file package:

- ZIP SHA-256:
  `40d86ea64c57fbd40365ff17bd1c46a8e45131b6078761572c2e641405266972`;
- release manifest file SHA-256:
  `d3711ce9e3fc748fb1548ec0bb6d06b4f7940fab3660aa19371f0fb316be622c`;
- checksums file SHA-256:
  `8ae23efcdedd454ea407c1ee88c9185100e6328630a0e00050dd6ee8ffbc2d88`.

A fresh Composer package-repository dist consumer installed that exact ZIP,
whose release manifest records source `7eeba4ad…`. It initialized 78 files,
passed doctor, full build, selected Alert rebuild and both static checks at
78 HTML / 3,931 references / `broken=[]`. Full and single contain 198 files
and share digest
`8fed7a149f0bf87c5bec475981dbaef043d3a0ef30e6e64b1b278ca026ce7710`.
The package contains neither `.git` nor `node_modules`.

## Browser and parity smoke

The exact full build was served from an isolated local root and opened in a
real browser at `/ru/components/hero/`:

- desktop 1440 and mobile 390: horizontal overflow is false;
- the first default Hero remains `variant=split`, contains exactly one
  meaningful image and does not mark it decorative;
- page, Hero image and all local CSS/JS assets return HTTP 200;
- console errors=0 and warnings=0;
- the exact-pinned Framework CDN contour remains online-only and is not
  claimed as offline bundling support.

## Boundaries and rollback

No Hero mode, Surface construction, default/homepage output, asset admission,
parser, registry, renderer, Gateway, LayoutComposer or PageBuilder contract was
changed. S3 was not started.

Rollback is `git revert 7eeba4ad7b5acd00f833bf2022e45775444fb69c` plus the
separate governance commit. This returns to the rejected audit boundary
`ed6aab88e09388e113ec0db4f7e760e70371d40b` without rewriting history.
