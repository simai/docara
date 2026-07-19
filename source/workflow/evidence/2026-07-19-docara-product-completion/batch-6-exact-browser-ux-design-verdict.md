# Batch 6 — exact browser, UX and design verdict

Date: 2026-07-19
Candidate: `68a960ff1debde48664aa8541413dbef208612ee`
Browser: native Google Chrome `150.0.7871.128`
Verdict: `PASS`

## Exact source

The browser source was extracted from the exact candidate at:

`/private/tmp/docara-b6-browser-68a960f-20260719`

Dependencies were attached read-only and outside the archive tree. The
production build passed static verification with 47 HTML pages, 4,943 local
references and zero broken references before the browser run.

## Desktop acceptance

At 1,440px the home, quick-start, component and Button pages were checked.

- same-tab navigation retained the active page and expanded active ancestor;
- `Meta+K` opened search, `настройки чтения` produced six results and keyboard
  selection opened the reader-settings page;
- breadcrumbs, right heading outline and previous/next links remained present;
- `system`, `dark` and `light` settings changed the shared Framework theme
  contract rather than adding a separate theme implementation;
- dark body background resolved to `rgb(26, 27, 31)`;
- light body background resolved to `rgb(250, 249, 254)`;
- the setting persisted through the shared `sf-theme` cookie;
- there were no console errors and no horizontal overflow.

## Cold mobile and keyboard acceptance

At 390 × 844 on a cold Button-page load:

- document and viewport width were both 378px;
- the mobile section menu opened;
- the Components ancestor was expanded;
- Button was marked as the active page;
- the first physical Tab focused `К содержанию`;
- Enter moved focus to `MAIN#docara-main`;
- there were no console errors.

## Visual artifacts

- desktop dark settings/search surface:
  `/private/tmp/docara-b6-browser-68a960f-20260719/.playwright-cli/page-2026-07-19T12-05-38-004Z.png`,
  1,440 × 1,000, SHA-256
  `8e88289e682d6dbcb40b2db73d3f5c8e5d562cf924d4e3cc54f5710162316a62`;
- cold mobile active menu:
  `/private/tmp/docara-b6-browser-68a960f-20260719/.playwright-cli/page-2026-07-19T12-06-23-938Z.png`,
  390 × 844, SHA-256
  `68f4e1fb0dd79542da7fd9fb3e59c434e768ffd4931b94403b602d7d4f9adbe9`.

Both screenshots were inspected visually. The component content remains clear,
the active trail is unambiguous and no clipping, mixed theme or accidental
layout primitive was found.

This verdict accepts the exact candidate's existing reader shell while Batch 6
adds its component contract. It does not accept the Batch 7 live catalogue or
the wider Goal.
