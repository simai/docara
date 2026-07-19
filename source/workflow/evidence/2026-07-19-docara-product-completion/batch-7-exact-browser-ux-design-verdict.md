# Batch 7 — exact native-Chrome UX/design verdict

Date: 2026-07-19
Candidate: `a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
Tree: `ec09ea5249a43c712729cbb74ab03e736987a353`
Exact archive origin: `http://127.0.0.1:8137`
Exact build digest:
`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`
Verdict: `PASS`

## Exact-source boundary

The browser target was built from `git archive` of the exact candidate. The
working tree and `docara.test` were not used as the candidate source and were
not changed during this verdict.

## Responsive and visual matrix

### Catalogue index

- 1440 by 900: two-column supported cards, desktop navigation and right TOC
  form a clear three-zone documentation layout;
- 390 by 844 after a reload at the mobile viewport: one-column cards, mobile
  navigation and mobile TOC remain in the intended reading order;
- all 12 supported full-surface card links and five unavailable disclosures
  are present;
- root and body widths remain inside the viewport at both breakpoints;
- dark, light and restored system preference all apply coherently.

### Generic component detail

- `ui.alert` shows title, identifier/family, purpose, live example, call,
  parameters, states and provenance in a reusable hierarchy;
- the admitted info-state Smart-component renders with its icon and exact
  pinned Framework styling;
- 1440 by 900, 768 by 900 and 390 by 844 layouts remain readable;
- previous/next and heading navigation are preserved;
- at 390 pixels the page remains 378 pixels wide while the wide parameter table
  stays inside its local `overflow-x: auto` wrapper.

## Keyboard and focus

- `Command+K` opens search and focuses the search input;
- `Escape` closes search and returns focus to the prior control;
- the mobile Sections disclosure opens with `Enter`, closes with `Escape` and
  retains focus on its summary;
- a full-surface catalogue card activates with `Enter` and reaches
  `/components/catalog/native.code/`;
- visible focus styling is present;
- theme controls are semantic radios, and reset restores the site `system`
  preference.

## Runtime observations

- main Chrome console warnings/errors: zero;
- mobile Chrome console warnings/errors: zero;
- fonts report `loaded`;
- exact routes, dotted identifiers, examples and assets return the expected
  document state.

The first screenshot immediately after one navigation captured before Chrome
painted the page. A state check already showed the complete DOM; the
network-idle screenshot immediately afterwards rendered normally. During the
keyboard card check, the navigation expectation wrapper timed out after the
navigation itself completed; URL, title, heading and DOM then confirmed the
correct target. Neither observation is a product defect.

## Bounded limitation

The pinned Framework code highlighter exposes the visible English label
`Copy` on the Russian page. Docara does not patch the pinned Smart-component
runtime locally; this remains a bounded Framework-owner localization/accessibility
gap and does not block the catalogue's authoring job.

This verdict accepts only the exact candidate's browser, UX and design surface.
It does not claim independent tester acceptance, publication, public release,
Framework ecosystem readiness or completion of the wider Goal.
