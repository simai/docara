# G1C-R4 — browser smoke

Status: PASS

Fresh disposable HTTP output from the runtime candidate was checked with a
real Chromium session; active live sites were not touched.

- `/ru/components/alert/`, 1440 px, light: Framework alerts render, Markdown
  tab switches, copy reports `Скопировано`, settings opens and Esc returns
  focus; overflow 0, console errors/warnings 0.
- `/ru/components/button/`, 390 px, dark: 15 Framework buttons render; mobile
  navigation, search, settings and Markdown tab work; Esc returns focus;
  overflow 0, console errors/warnings 0.
- DOM inventory on both pages confirms the shared `docara.brand`,
  `docara.navigation`, `docara.toc` and `docara.preferences` shell components.

Screenshots:

| File | Dimensions | SHA-256 |
| --- | --- | --- |
| `browser/alert-1440-light.png` | 1440x2521 | `ce63b0033672870aaa8fdb14fddc8ed8c9e545ee331d47d200ac0f878bd1a363` |
| `browser/button-390-dark.png` | 390x1829 | `c0c6a753259f38f919d2e96d684cb6008dba510ee5aef731ec56b39f8fa30ee6` |

The screenshots are evidence, not source of truth. The machine assertions and
zero console/overflow results are the acceptance signal.
