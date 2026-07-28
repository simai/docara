# Batch 4 reader-settings decision

Date: 2026-07-19
Baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
State: implementation authorized, acceptance pending

## Reader outcome

The smallest useful reader-settings outcome is one explicit appearance
preference with three values:

- `system` follows `prefers-color-scheme` and reacts to operating-system
  changes;
- `light` fixes the light Simai Framework theme;
- `dark` fixes the dark Simai Framework theme.

The author-owned inherited `settings.theme` value remains the first-visit
default. A reader override is local to the browser, applies before first paint
and can be reset to the current page's author default.

This follows the proven documentation-platform contract: Docusaurus separates
an author default from a reader color-mode switch and can respect the system
preference; VitePress restores the reader appearance before rendering to avoid
theme flicker.

- <https://docusaurus.io/docs/api/themes/configuration/#color-mode>
- <https://vitepress.dev/reference/site-config#appearance>

## Human-centered scope

Batch 4 intentionally does not add text-size, content-width, density, motion
or navigation controls:

- browser zoom already owns general text scaling and must remain the reliable
  accessibility mechanism;
- `layout.max_width` is an author-owned page/section layout contract, so a
  reader override would create competing precedence and unpredictable landing
  composition;
- reduced motion already follows `prefers-reduced-motion` without a custom
  preference;
- one fieldset with three radio choices is immediately understandable and
  keeps a strong default.

These exclusions may be reconsidered only with a demonstrated reader problem,
not to fill visual space in a settings panel.

## Simai Framework mapping

No Framework repository or portable asset projection changes are required.

| Need | Exact building block | Decision |
| --- | --- | --- |
| trigger | Core `sf-icon-button`, projected `sf-icon` | use `tune`, remove `.sf-theme-button` binary-handler hook |
| surface | native `dialog` + surface/border/radius/flex/gap utilities | reuse the accepted search-dialog pattern |
| choices | Core radio markup with native radio inputs | three options, one fieldset |
| reset/close | Core button and icon-button classes | native button semantics |
| colors | `theme-light` / `theme-dark` and Framework tokens | no parallel palette or component |

Portable Docara sets the pinned Core boot option `theme: false`. Core's default
binary OS/cookie bootstrap would otherwise run after Docara's early tri-state
resolver and could overwrite an explicit author default. This option disables
only that automatic bootstrap; Docara still renders the exact Framework theme
classes, tokens and components.

The exact Smart lock contains modal and dropdown records, but their assets are
not admitted to the portable projection. Promoting either for a three-choice
settings form would add a compatibility transaction without improving the
reader outcome. A generic preferences Smart-component remains a Framework
backlog proposal until the Docara interaction is accepted in practice.

## Preference and migration contract

- key: `docara.reader.theme.v1`;
- storage: browser `localStorage`, guarded because storage access can fail;
- a denied write keeps a volatile current-page override, exposes reset and
  reports unavailable persistence instead of claiming success;
- accepted values: `system`, `light`, `dark`; every other value is ignored;
- fallback order: valid reader value, legacy valid `sf-theme` cookie, inherited
  author default, then `system`;
- an explicit change writes the local value and synchronizes the legacy
  `sf-theme` compatibility projection for `light` or `dark`; `system` clears
  that cookie;
- reset removes both local value and legacy cookie, then reapplies the current
  inherited author default;
- `system` is stored explicitly so it can override an author default of
  `light` or `dark`;
- no request, account, database or external service is involved.

The existing Framework classes and tokens remain the only rendered theme. The
Docara controller owns only preference selection, early restoration and the
settings-dialog interaction.

## Interaction and accessibility contract

- the 44 px header trigger has `aria-haspopup="dialog"` and `aria-controls`;
- the native dialog has a visible title and one labelled fieldset;
- opening focuses the selected radio option;
- radio keys retain native browser behavior;
- every change applies immediately and updates a polite status message;
- Escape closes the dialog through native semantics;
- closing returns focus to the trigger;
- reset is hidden when no reader override exists;
- the dialog fits 390 px without horizontal overflow and remains coherent in
  both Framework themes;
- the system preference reacts to a live media-query change;
- the binary `.sf-theme-button` hook is absent, preventing double action.

## Test-first matrix

| Layer | Required evidence |
| --- | --- |
| configuration | existing strict `system/light/dark` schema, inheritance and negative `sepia` tests remain green |
| generated HTML | one trigger, one dialog, exactly three options, reset and status, no Smart modal/dropdown |
| runtime | guarded restore/write/remove, legacy migration, reset, system media listener, no binary Framework hook |
| Framework integration | Core binary theme bootstrap disabled before `core.js`; author light/dark survives the opposite OS preference |
| deterministic build | two clean builds have the same digest and zero broken links |
| browser desktop/mobile | open/change/persist/reload/reset/Escape/focus/system/light/dark/no-overflow/console matrix |
| exact acceptance | independent tester and UX/design verdicts bind to the same candidate SHA |

The focused generated-HTML test was changed first and failed on the accepted
baseline because `data-docara-reader-settings-trigger` did not yet exist. This
is the expected red state before implementation.

## Nonclaims

This decision does not accept Batch 4, complete the product Goal, publish a
public release, change default branches or claim production readiness.
