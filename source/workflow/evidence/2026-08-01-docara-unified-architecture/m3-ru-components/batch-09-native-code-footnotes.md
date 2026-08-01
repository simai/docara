# M3.3 batch 09: native code and footnotes/sources

Date: 2026-08-01

Verdict: PASS

Parent SHA: `74430d2`

Candidate SHA: commit containing this evidence

## Ownership and scope

- `/ru/components/code/` -> `docs/site/content/ru/components/code.md`;
- `/ru/components/footnotes-and-sources/` ->
  `docs/site/content/ru/components/footnotes-and-sources.md`;
- equivalent portable starter owners live under
  `stubs/portable/content/ru/components/`;
- no other locale, dependency or Framework lock changed.

Both pages contain useful purpose, a working tabbed general example, compact
parameter/syntax guidance, a call and relevant limitations. Page prose is no
longer owned by the Russian language pack or localized catalog examples.

## Shared example contract

The code page exposed a generic authoring need: a Markdown source example must
be able to contain its own fenced code. The existing example contract now
accepts CommonMark backtick or tilde fences of length three or more and chooses
a deterministic safe fence for the source tab. The page can therefore use an
outer `~~~markdown` fence around an inner ` ```php ` fence without a new Code
pipeline or renderer.

Focused testing caught and removed an empty extra code block caused by the old
fixed triple-backtick source wrapper. The final page has exactly three
non-empty code surfaces: preview, Markdown source tab and call snippet.

Footnotes remain inside the same generic `example` SourceNode so the reference
and definition are rendered as one Markdown unit; the result exposes standard
`doc-noteref`, `doc-endnote` and `doc-backlink` roles and stable targets.

## Legacy reduction and rollback

- generated-route allowlist: `39 -> 37`;
- Russian language-pack records/max: `37 -> 35`;
- Russian generated component-detail receipt: `24 -> 22`;
- physical component ownership: `7/32 -> 9/32`;
- zero-reference localized examples retired:
  - `native.code.ru.md`, old SHA-256
    `01b7df396c0162de46583a03e0ec506ca01ab973b3d4384a8d7d04cb42b3f275`;
  - `native.footnotes_and_sources.ru.md`, old SHA-256
    `50ec6f7ca729da23bd085d4fdde12ec6c3344a4b4d8b79979d8372890aa79861`.

Rollback is a revert of this checkpoint commit; the parent restores both exact
examples, language-pack records and allowlist entries.

## Build and static verification

```text
php ../../docara build m3-native-code-footnotes-full
PASS — 103 pages, 321 files

php ../../docara build m3-native-code-single --page=/ru/components/code/
PASS — 1 selected page; full/single tree diff empty

php ../../docara build m3-native-footnotes-single \
  --page=/ru/components/footnotes-and-sources/
PASS — 1 selected page; full/single tree diff empty

php ../../docara verify-static build_m3-native-code-footnotes-full
PASS — 206 HTML pages, 18,890 local references, 0 broken
```

- content-addressed full tree SHA-256:
  `58a683f3a080fa1d6d1a982c04c4bc4491c32ebf4f8c698a73b04b10aa6589d4`;
- code HTML SHA-256:
  `6263f8c2b120cf81020b071d6d441e2982b97e0c7cc0db2645e1fb1e6f826fd2`;
- footnotes HTML SHA-256:
  `e3a0017a4b7a468fc64fc764599a35bdf791ead58437a36f4e9d9b2536f1e830`;
- generated receipt contains neither migrated route;
- focused PageBuilder/renderer/catalog/allowlist/static tests: PASS;
- full PHPUnit: 371 tests, 7,120 assertions, 2 inherited warnings, PASS;
- Pint, PHP lint, graph/resource JSON and `git diff --check`: PASS;
- project-graph validator: PASS, 0 warnings and 0 blockers.

## Browser verification

Playwright verified desktop-light (1440 x 1000) and mobile-dark (390 x 844)
for both routes. Code tabs/copy work with three non-empty code blocks and no
global overflow. The footnote and backlink navigate to existing `fn:source`
and `fnref:source` targets; roles, tabs and responsive layout remain intact.
Console warnings/errors: zero.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| code | desktop-light | `3b80568e1cf562cd407d9a681eeea9cd44e061841031e65e74e7a3da72b89dd2` |
| code | mobile-dark | `ae39080f96282a45ff51f7edccabed4d7dd2d7f1b3e4b4cc65343f853de48915` |
| footnotes | desktop-light | `01cab677f51566c2e2e6d048bdccb3f7e90619f4d57aad117beba4bebe9878c0` |
| footnotes | mobile-dark | `ab180819247736953c1c57075e89581a128dee3804ea3c17a7d621141be414a0` |

Screenshots are disposable evidence only.

## Regression deviation resolved

The first full suite found one stale static-verifier negative fixture: it
mutated `/components/code/` as a generated page after that route became
physical. The fixture now mutates still-generated `/components/card/`; its
fail-closed landmark/adjacency/sibling assertions pass again.

## Readiness boundary

Batch 09 is complete; overall M3 is not. Nine of 32 component routes are
physical and 23 remain generated. Batch 10 migrates details and backlinks.
