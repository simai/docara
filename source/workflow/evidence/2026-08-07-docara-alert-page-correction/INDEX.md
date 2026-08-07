# Alert page and local icon correction evidence

Date: 2026-08-07
State: `ready_for_independent_audit`
Product candidate: `d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f`
Parent product commit: `2a04b48804b02af023538863e5fd34c539687f1d`

## Outcome

The Success Alert now shows `check_circle` with the existing semantic success
color. The public Alert guide follows the same compact reference sequence as
Badge: introduction, type table and rendered example, presentation table and
rendered example, then usage guidance. Button and Icon pages remain visually
correct.

## Root cause and correction

- the immutable Framework Alert CSS sets the success icon token to
  `transparent`; Docara now applies one bounded post-Framework compatibility
  declaration using `var(--sf-success)`;
- the exact local icon projection now includes complete Outlined, Rounded and
  Sharp variable fonts from
  `google/material-design-icons@50f0603134ce7b70b2d71b686cc13e8b57ccb74c`;
- exact Outlined SHA-256:
  `5c0be48d07803e6eb6a993ad441f6fc92340ee0da9d1b57cc348f62569947ae5`;
- exact four-file icon projection packet SHA-256:
  `040d5a4c9fb0893e0333bd5d5020cc98929e21dad28f68273c8e306102213f5c`.

No Framework distribution file was edited. No second parser, registry,
renderer, Gateway, LayoutComposer or PageBuilder was introduced.

## Tests and deterministic build

- focused exact projection and real docs-site PageBuilder regression:
  `2 tests / 349 assertions`;
- `PortableSiteBuilderTest` including the published compatibility rule: PASS;
- full PHPUnit: `511 tests / 11,547 assertions`, failures/errors/skips `0`;
- Pint, Composer strict validation, JSON validation and `git diff --check`:
  PASS;
- full roots: `docs/site/build_build_alert-final-a` and
  `docs/site/build_build_alert-final-b`;
- representative single root:
  `docs/site/build_build_alert-final-single`, route
  `/ru/components/alert/`;
- all three contain `652` files and share canonical tree digest
  `db628b95db1087878f46c087297c289c153cdc1ef9675f474f358189d04b8521`;
- static verifier on both full roots: `261 HTML`, `32,965` local references,
  `broken=[]`;
- fresh initialized consumer: `39` routes / `78` HTML / `4,087` references /
  `broken=[]`, with the same exact full Outlined font hash.

Canonical tree digest command:

```bash
cd <build-root>
find . -type f -print0 | sort -z | xargs -0 shasum -a 256 \
  | sed 's#  \\./#  #' | shasum -a 256
```

## Browser and validation-site proof

- candidate and HTTPS validation-site screenshots show visible clear, info,
  success, warning and danger glyphs;
- desktop and mobile Alert render without horizontal overflow;
- Button and Icon pages retain glyph rendering after the complete Outlined
  projection;
- exact full Outlined URL returns HTTP 200 and `font/woff2`;
- no external font request is required.

Screenshots:

- `output/playwright/alert-final-success-visible.png`;
- `output/playwright/alert-live-mobile.png`;
- `output/playwright/button-live-regression.png`;
- `output/playwright/icon-live-regression.png`.

## Atomic local cutover and rollback

Action gate: `source/output/action-gates/action-gate-report-20260807172945.json`.

- previous active tree: `650` files,
  `ead33689e47c3eb690bc9f1ccb570bec117b5a994130886ce37dd50f3eead435`;
- current `docara-new.test`: `652` files,
  `db628b95db1087878f46c087297c289c153cdc1ef9675f474f358189d04b8521`;
- rollback backup:
  `/Users/rim/Sites/.docara-new.test-backup-before-alert-68617a2-20260807`;
- backup digest remains exact previous active digest.

Rollback uses `scripts/atomic-static-cutover.php rollback` with the same active,
candidate, backup and exact digest values used by the successful preflight and
cutover. No write occurred to `docara.test`; no merge, push, tag, release or
publication occurred.

## Human-centered simplicity review

- `user_goal`: recognize every Alert state immediately and understand the API
  in the same sequence used by other component guides;
- `primary_action`: compare types and copy a working Markdown example;
- `secondary_actions`: compare visual variants and read usage constraints;
- `removed_or_avoided`: no duplicate gallery, new icon service, new runtime or
  component-specific engine dispatch;
- `states_reviewed`: desktop, mobile, dark theme, five semantic Alert types;
- `responsive_reviewed`: narrow and wide layouts show the same information
  hierarchy and visible icons;
- `token_reuse`: `--sf-success` is reused; no raw color was introduced;
- `accessibility`: Alert roles and labels are unchanged; decorative icons stay
  `aria-hidden=true`, while text continues to carry the meaning;
- `tester_verdict`: executor evidence is complete; independent acceptance is
  intentionally not self-issued.
