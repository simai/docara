# M3.3 batch 18: Embed and Example

Date: 2026-08-01

Verdict: PASS

Parent SHA: `b2f695f819e1352ed7420050c94ab25555b2cb87`

Candidate SHA: commit containing this evidence

## Ownership and runtime

- `/ru/components/embed/` -> `docs/site/content/ru/components/embed.md`;
- `/ru/components/example/` -> `docs/site/content/ru/components/example.md`.

Both physical owners reuse generic typed-directive/example IR and one
PageBuilder, renderer registry and Smart gateway. Browser acceptance exposed a
shared consent-gateway defect: the shell queried a stale template attribute and
never promoted `data-src` to `src`. The common shell now clones the accepted
template and activates its iframe. The consent button label comes from the
Markdown link owner, so no public English fallback prose remains hard-coded in
PHP or JavaScript.

## Legacy reduction and rollback

- generated-route allowlist: `20 -> 18`;
- Russian language-pack component prose: `18 -> 16`;
- generated component details: `5 -> 3`;
- physical component ownership: `26/32 -> 28/32`;
- retired localized example hashes:
  - Embed: `adeeb97dd66058a3326617605a57facc0ebf13188e9019f7b14e2b1eb0ccecd5`;
  - Example: `d3a429debc17d956b78f2df2fcc41ab3d0c7b5cba3c15e9949f400a80a4f4eb0`.

Repository search found zero active references to both localized fixtures.
Reverting the checkpoint commit restores the former projection and is the
rollback path.

## Verification

- full build: 103 pages; isolated: one Embed and Example page each;
- exact full/isolated tree SHA:
  `5a4c983049b26437bc441bad677e05a624d9c68ba292f89f8065e61090e97cb0`;
- static: 206 HTML, 18,942 references, 0 broken;
- Embed HTML: `3d25a30e84cb49b8e8219a959d55cca2f365ad2e2cad1bce480feb9aa657e394`;
- Example HTML: `2ebf00fd70aa0e08daa8307b4e99a31e56708e177159ad11cea302593c745fc2`;
- focused suite: 142 tests, 3,487 assertions, PASS with two inherited warnings;
- full PHPUnit: 375 tests, 6,300 assertions, PASS with two inherited warnings;
- PHP lint: 222 source/test/stub files; JSON, graph and diff hygiene: PASS.

## Browser evidence

Desktop-light Embed renders four consent-gated responsive blocks with localized
Markdown-owned labels and zero overflow. Before confirmation there are no live
external iframes; keyboard activation replaces the consent surface with the
correct titled iframe. The activation check ran offline, so evidence caused no
external data transfer.

Mobile-dark Example renders both Markdown and HTML/CSS/JavaScript examples with
six tabs, no overflow and one sandboxed script frame. Clicking its button changes
the result from `Готово` to `Выполнено`. Both pages emit zero console
warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| embed | desktop-light | `237f683e10f9b8911ec737da5c4217036a8308c9cc8b87fa6cde2c04623e3060` |
| example | mobile-dark | `d72827fd3b289afb9b7754612257f2b35736376e880588bc03d7f357385fc1fe` |

## Boundary

Batch 18 PASS; M3 remains open. 28/32 component routes are physical and four
public routes remain generated (Steps, Tabs, Tree and the component index).
Batch 19 is Steps and Tree.
