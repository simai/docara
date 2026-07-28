# Browser smoke and visual acceptance

Date: 2026-07-18
Site: `https://docara.test/`
Candidate: `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049`
Verdict: **PASS**

## Desktop and themes

- Viewport: 1440 x 900.
- Root page has Russian language metadata, `Docara` heading, eight primary
  navigation entries, and no horizontal overflow.
- System theme resolved to dark. Explicit light and dark selections each
  changed the document color scheme and retained a correct layout. The final
  preference was restored to system.

## Routes and components

The following pages loaded with the expected URL, title, heading, main content,
and no horizontal overflow: Start, Components, Alert, Button, Card, Code,
Steps, Table, Tabs, Starter mirror, and PHP-only build.

- Alert renders the real Framework alert contract.
- Button renders one labeled disabled Smart-component demonstration.
- Card, Code, Steps, and Table render their documented semantic/utility
  recipes.
- Tabs visibly explains that the exact Framework contract is unavailable; no
  fake component syntax or substitute runtime is presented.

## Mobile, accessibility, console, and assets

- Viewport: 390 x 844.
- Root and Button pages have no page-level horizontal overflow.
- The Button demonstration remains visibly labeled and disabled.
- Browser console warnings/errors: `[]`.
- Reload network inventory: 62 responses, failed requests `[]`, non-success
  statuses `[]`.
- Page inventory: 41 assets: 28 CSS, 12 JavaScript, and 1 font. Framework assets
  use the exact immutable `ui@7e836d8…` revision; local Smart alert/icon assets
  also loaded successfully.

## Screenshots

| File | Bytes | SHA-256 |
| --- | ---: | --- |
| `browser/desktop-dark-root.jpg` | 89998 | `bd6886cc8aa9282f3dc573d3edb0266572cd0344b8ce622731033d6a907c24d9` |
| `browser/desktop-light-root.jpg` | 91622 | `b795b3f1c478d7f9f489849e26a2abb53fe1e00484fab90e241088d8b26d54bf` |
| `browser/mobile-dark-root.jpg` | 36440 | `871d596c406ea81a374e4cc09a01801ffdb114179a0b37f6568256f18e3585f6` |
| `browser/mobile-dark-button.jpg` | 30159 | `5b31a392f67bb712573543bb9bbf3f254304d579fb4fd87c94ea10ba3780ff21` |

The browser session was returned to the deliverable root page and finalized.
