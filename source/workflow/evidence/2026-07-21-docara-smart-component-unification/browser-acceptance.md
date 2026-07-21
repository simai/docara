# Browser acceptance

Status: PASS
Candidate: `46fefd88d4031a1a5bcba551fef9bdc6c04b2edf`
Browser: native Chrome through the controlled browser session

## Desktop, 1440 x 900

- Smart guide and demonstrator render `docara.brand`,
  `docara.navigation` and `docara.toc`;
- all five registered Smart CSS/JavaScript assets are present;
- header, sidebar, main and outline regions are composed;
- active page and active ancestor states are visible in the navigation tree;
- navigation expansion updates its accessible label and state;
- search opens, query `SmartRegistry` returns two results, including the Smart
  architecture guide;
- light and dark reader themes both apply through the reader-settings UI;
- keyboard focus has a visible 3 px outline;
- page width remains within the viewport.

## Mobile, 390 x 844

- document width is 378 px for a 390 px viewport; no horizontal page overflow;
- desktop sidebar and outline are hidden and the mobile triggers are visible;
- the mobile navigation opens with the active four-level branch expanded;
- the mobile ToC opens with all six page headings and an active item;
- the product demonstrator remains usable and its isolated result contains one
  brand instance, two navigation instances and two ToC instances (desktop and
  mobile hosts), including depth-four navigation nodes.

## Locale and direction fixture

A disposable three-locale portable build was served locally and removed after
the check:

- English: `lang=en`, `dir=ltr`, localized `Sections` / `On this page`
  controls;
- Arabic: `lang=ar`, `dir=rtl`, localized navigation and ToC controls, RTL body
  direction and no horizontal overflow at 390 px;
- canonical Smart names remain identical across locales; only validated props
  from the locale pack change.

The browser console contained no warnings or errors. The final deliverable tab
was returned to the product Smart demonstrator and the disposable locale server
was stopped.
