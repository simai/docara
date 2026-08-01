# M3.3 batch 17: Code-from-file and HTML

Date: 2026-08-01

Verdict: PASS

Parent SHA: `c158a5e7aa42667823ed0ae9934d911a30539934`

Candidate SHA: commit containing this evidence

## Ownership and runtime

- `/ru/components/code-from-file/` ->
  `docs/site/content/ru/components/code-from-file.md`;
- `/ru/components/html/` -> `docs/site/content/ru/components/html.md`.

Both pages reuse the generic `typed_directive` IR, the existing renderer
registry, Smart gateway and PageBuilder. The shared Markdown example renderer
now preserves authored source context when its preview contains an external
code directive; a regression test covers this generic behavior. No Code- or
HTML-specific pipeline, compiler, IR type, registry or gateway was added.

## Legacy reduction and rollback

- generated-route allowlist: `22 -> 20`;
- Russian language-pack component prose: `20 -> 18`;
- generated component details: `7 -> 5`;
- physical component ownership: `24/32 -> 26/32`;
- retired localized example hashes:
  - Code: `d664d2e237b86c5924c5f7107c4bb4f5aaccd856303498a2cd3467d1d148cc18`;
  - HTML: `0416a1fdb086b0122ad8b5bc0a91ce6974be467c71681004c29a01776d3a9571`.

Repository search found zero active references to either localized fixture
before deletion. The checkpoint commit revert restores both projections and is
the rollback path.

## Verification

- full build: 103 pages; isolated: one Code-from-file and HTML page each;
- exact full/isolated tree SHA:
  `e0d065aa343baf21845c5c17ec86b7e88651897b7d330c380a07e914a743d282`;
- static: 206 HTML, 18,942 references, 0 broken;
- Code HTML: `8b3f00428cc1195c921634c8cb5c86eb0cd7c2873f05fd2c22af453582ac2644`;
- HTML HTML: `4c1a1dab72a90b7cee22a8e414b4db7ef10517f70e2bdfd98fa5351c93b24af9`;
- focused suite: 86 tests, 2,565 assertions, PASS with two inherited warnings;
- full PHPUnit: 375 tests, 6,387 assertions, PASS with two inherited warnings;
- PHP lint: 223 files; JSON, graph validation and diff hygiene: PASS.

## Browser evidence

Desktop-light Code renders two titled external-code previews plus their source
surfaces, with zero horizontal overflow. Keyboard activation selects the
Markdown tab, copies its source, reports success and retains focus.

Mobile-dark HTML renders two sandboxed iframes (general preview and working
result), neither admits scripts; the working result contains the expected
standalone fragment. Both routes use physical page shells and emit zero console
warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| code-from-file | desktop-light | `a8e64763588499a99e8a657b1675fbfb72197eca0c335374edd50b1297e4c4ee` |
| html | mobile-dark | `866333775b82d60e2ebfaed58b6e69c1d1f181570605aa52af9e0b62c0f8192f` |

## Boundary

Batch 17 PASS; M3 remains open. 26/32 component routes are physical and six
public routes remain generated (five details plus the component index). Batch
18 is Embed and Example.
