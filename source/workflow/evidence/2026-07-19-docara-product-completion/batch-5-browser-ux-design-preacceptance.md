# Batch 5 browser, UX and design preacceptance

Date: 2026-07-19
Candidate: mutable worktree only
Browser: native Google Chrome 150.0.7871.128
Verdict: PASS

## Matrix

Nine scenarios were exercised against the disposable static build:

- 390x844: light JS, dark JS, light without JS;
- 768x1024: light JS, dark JS, light without JS;
- 1440x900: light JS, dark JS, light without JS.

All nine scenarios passed the executable checks:

- landing response and CTA destination return success;
- H1, lead, one native CTA, three features and command proof are present;
- CTA is inside the first viewport, keyboard reachable, visibly focused and
  followed with Enter;
- CTA height is 44–44.1 px in every scenario;
- CTA remains full-width only at 390 px and is compact at 768/1440 px;
- feature cards fill the one-column content width and become three equal-height
  columns at the Core `lg` breakpoint;
- the short command proof is visible without hidden horizontal scrolling;
- landing contains no docs navigation, search or outer hero card;
- there is no page-level horizontal overflow;
- configured light/dark mode is applied when JavaScript is available;
- all content and the real native link remain usable without JavaScript.

There were zero console problems, zero HTTP errors and zero unexpected request
failures. `net::ERR_ABORTED` events produced only while the harness
intentionally navigated away from the current document were recorded
separately and did not mask a failed resource load.

## Visual correction loop

The first independent visual review correctly rejected the earlier mutable
render for:

- an over-wide desktop CTA;
- prose-width leakage into tablet feature cards;
- technical HTML indentation and hidden scrolling in the mobile command proof;
- JS/no-JS CTA height drift.

All four findings were corrected before this report. The PNG matrix and
machine report were regenerated from the corrected build.

Independent attempt-2 then reopened all nine updated PNGs and returned `PASS`.
It confirmed the compact tablet/desktop CTA, stable 44–44.1 px action height,
full-width tablet cards, equal desktop cards, readable mobile command proof,
theme consistency, absence of clipping and the intended
meaning → action → properties → proof hierarchy.

The accepted mutable browser report is:

- path: `/tmp/docara-b5-landing-preacceptance/checks.json`;
- SHA-256:
  `e3c04b1d23771c6fc812d7b406fcbe09946ee9ad65eb0d97fbd0d2dd5e10f19a`.

No publication or readiness claim is made by this preacceptance.
