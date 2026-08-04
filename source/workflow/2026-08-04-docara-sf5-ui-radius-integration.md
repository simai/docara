# Docara / SF5 UI radius integration

Date: 2026-08-04
Status: framework candidates complete; Docara repin blocked on immutable source availability
Docara input revision: `01d941dc9db40540c3f7907ffdf9c603c1084542`

## User outcome

One framework-owned radius controls the default rounding of small UI controls.
Components keep their own override variable, while a future Docara reader
preference changes only the shared token through a bounded preset.

## Contract

```css
--sf-radius-1\/3: var(--sf-a2);
--sf-radius--ui: var(--sf-radius-1\/3);
--sf-button--radius: var(--sf-radius--ui);
```

The same inheritance is implemented for icon buttons, inputs and dropdowns.
The historical `--sf-ui-radius-default` is an alias to the shared token for
existing input-like controls. Per-corner overrides and explicit circle, pill
and square variants remain authoritative.

## Exact local candidates

| Surface | Branch | Commit | Tree |
| --- | --- | --- | --- |
| Framework source (`simai/ui-loader`) | `codex/sf5-ui-radius-contract` | `36123543027d6b363c2242c747bf1fd8ec7d6c88` | `248caa7bdee5bacb29ae1e59fc7e47e9d66b8329` |
| Framework distribution (`simai/ui`) | `codex/sf5-ui-radius-contract` | `ed829c3bf53d3bfc37e628e601da73183961ae72` | `28e0477a4f7b9226e8d351b5669dc70d49f54bc7` |

Neither commit exists in a fetched remote ref. No push, tag, release or deploy
was performed.

## Verification

- source focused: 2/2 PASS;
- source full: 42/42 PASS;
- two-wave product build: PASS and byte-identical for Core, Component, Utility
  and Smart;
- build report SHA-256:
  `369e33aa18a32231e39e71d61e924b9c31686e423732347fdf190f7de4878edc`;
- deterministic distribution transform: two clean parent archives produce the
  same selected-tree digest
  `6aa761876740432c7ed9299b27f3adf6bd6092b124122650498a255c753b1a4b`;
- distribution focused test: 3/3 PASS;
- browser computed values for button, icon button, input and dropdown:
  default `2px`, shared-token override `8px`, console warnings/errors `0`.

The pre-existing Framework registry suite is not claimed green: its current
`distr/rule/rule.json` is invalid at line 495 independently of this radius
delta. The radius-focused contract and deterministic output are green.

## Integration boundary

Docara's `resources/framework/runtime-lock.json` resolves `simai/ui` through an
exact jsDelivr Git commit. Repinning it to a local-only commit would create an
unresolvable build and violate the immutable runtime contract. Therefore this
batch intentionally does not change the Docara runtime lock, schemas or reader
settings yet.

After explicit publication approval, the bounded continuation is:

1. push the two owner branches through their normal review flow;
2. verify the exact `simai/ui` commit is fetchable from the declared source;
3. update Docara's runtime lock and registry binding;
4. add a bounded reader preference such as `compact`, `default`, `comfortable`
   that maps only to allowlisted Framework radius primitives;
5. run full/single, deterministic, static and browser parity, then create a new
   Docara candidate for independent audit.

Arbitrary CSS values from Markdown or project configuration remain forbidden.

## Rollback

- source: revert `36123543027d6b363c2242c747bf1fd8ec7d6c88`;
- distribution: revert `ed829c3bf53d3bfc37e628e601da73183961ae72`;
- Docara: no runtime change exists in this checkpoint.

## Stop condition reached

An immutable consumer pin requires an external owner-repository push/review
action that is outside the currently authorized local-only boundary. Do not
fake the pin, vendor an untracked local path or add a Docara-only component
radius dialect.
