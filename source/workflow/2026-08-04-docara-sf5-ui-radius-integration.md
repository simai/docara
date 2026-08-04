# Docara / SF5 UI radius integration

Date: 2026-08-04
Status: implementation complete; ready for independent audit
Docara product candidate: `6bbe0653265bbfb08027b717ed2981a1add79c2e`
Evidence: `source/workflow/evidence/2026-08-04-docara-sf5-ui-radius/INDEX.md`

## User outcome

Modal search/settings windows start without background blur. A reader can choose
standard, medium or large rounding; the choice changes one Framework-owned
token and is restored across pages. Components keep their own native override
variables and explicit square/pill/circle variants remain authoritative.

## Contract

```css
--sf-radius-1\/3: var(--sf-a2);
--sf-radius--ui: var(--sf-radius-1\/3);
--sf-button--radius: var(--sf-radius--ui);
```

The same inheritance exists for icon buttons, inputs and dropdowns. Docara's
allowlist maps `default`, `medium`, `large` to Framework primitives
`--sf-radius-1/3`, `--sf-radius-1/2`, `--sf-radius-1`; arbitrary CSS from
Markdown or configuration remains forbidden.

## Exact owners

| Surface | Branch | Exact commit |
| --- | --- | --- |
| Framework source (`simai/ui-loader`) | `codex/sf5-ui-radius-contract` | `36123543027d6b363c2242c747bf1fd8ec7d6c88` |
| Framework distribution (`simai/ui`) | `codex/sf5-ui-radius-contract` | `d1daa951dd08b94a9f209fd9f31a78d2b3779563` |
| Docara product | `codex/docara-unified-architecture` | `6bbe0653265bbfb08027b717ed2981a1add79c2e` |

The distribution tree is the accepted Docara baseline plus the bounded radius
delta. Intermediate distribution commits `ed829c3…`, `96c17a26…` and
`89d4ea3c…` are superseded history and are not consumer pins.

## Verification and recovery

The exact commands, hashes, browser values, nonclaims and rollback boundaries
are recorded in the evidence index. Full and single-page build still use the
same PageBuilder; no renderer, Gateway, registry or component-ID branch was
added.

## Human-centered simplicity

One shared token is the smallest control surface that satisfies the request.
Three named presets are understandable in settings, preserve accessibility and
avoid exposing implementation CSS. No new page source, renderer or engine path
was introduced.

## Rollback

- Docara: revert product commits from `42babb7…` through `6bbe065…`;
- Framework distribution: repin to the prior exact `cc1bfbc…` baseline or
  revert the bounded `d1daa951…` branch commits;
- Framework source: revert `36123543…`.

No merge, tag, release or deploy was performed.
