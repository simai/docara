# Browser acceptance

Target: `https://docara.test/`

## Desktop

- Viewport override: 1440 × 1000.
- `/examples/` displayed 12 cards with normal documentation navigation and no
  horizontal overflow.
- Five exact result routes returned the expected region, section and component
  signals:
  - header: branded `docara.header`, one shell section and hydrated
    `sf-button`;
  - sidebar: navigation plus inherited/custom safe element content;
  - aside: outline plus `sf-alert` (desktop and mobile clone);
  - footer: visible semantic footer and escaped local link;
  - inheritance: sidebar, outline and footer contributions all present.
- Desktop visual review of the inheritance result showed the three-column docs
  shell, visible section context and page-level aside without collision.
- Browser console warnings/errors: none.

## Mobile

- Requested viewport: 390 × 844; document client width: 378 px.
- No horizontal overflow.
- Desktop sidebar computed `display: none`.
- Mobile documentation-menu and outline triggers were visible.
- Main content and footer remained present.
- Mobile visual review showed readable typography, wrapped breadcrumbs and no
  clipped content.

## Themes

- Initial configured theme: light projection from system/site state.
- Reader settings switched to dark through the visible UI.
- Dark state: `theme-dark`, preference `dark`, source `reader`, body background
  `rgb(15, 17, 21)`, text `rgb(227, 226, 231)`, no horizontal overflow.
- Dark mobile visual review showed consistent header, content and navigation
  colors.
- Preference was returned to `system` and temporary viewport override reset.
- Browser console warnings/errors across the matrix: none.

Verdict: PASS.
