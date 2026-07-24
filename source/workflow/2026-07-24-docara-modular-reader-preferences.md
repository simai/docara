# Docara modular reader preferences architecture

Date: 2026-07-24  
Status: completed, acceptance PASS  
Track: `docara-consolidation`  
Source of truth: current `codex/docara-consolidation` worktree and its immutable
SIMAI Framework runtime lock. The legacy Docara skill and legacy Jigsaw/Mix
model are explicitly excluded.

## Goal

Replace the hard-coded theme dialog with a small, extensible reader-preference
system for a static site:

- the owner selects which preferences readers may change;
- Docara validates and compiles a typed preference manifest;
- a product-owned Smart component builds the interface from that manifest;
- SIMAI Framework components render the modal shell and controls;
- a storage adapter persists normalized values locally;
- registered effect adapters apply only known safe changes;
- the same presentation contract can later be reused in Larena with a
  different persistence adapter.

## Verified current state

The current reader settings are not rendered by the locked `sf-modal`
component. `resources/publisher/components/reader-settings.php` emits a native
`<dialog>` and `resources/portable/declarative-shell.js` manually owns open,
close, keyboard trapping, reset and theme synchronization.

The current backdrop is:

```css
background: color-mix(in srgb, var(--sf-on-surface) 34%, transparent);
```

`--sf-on-surface` becomes light in a dark theme, so the overlay becomes light.
The locked Framework modal already uses a theme-independent dark overlay and
supports `position="right"`. This is a replacement problem, not a color patch.

The existing presentation `settings` object is an author-time inherited
contract for site, section and page defaults. It must not also become the
catalog of controls exposed to a reader.

## Product decisions

### 1. Separate author settings from reader preferences

- Keep `settings` for authored presentation defaults such as
  `settings.theme`.
- Add the site-level `reader_preferences` object to `docara.json`.
- Do not inherit `reader_preferences` through sections and pages. A settings
  panel that changes shape while navigating is unpredictable.
- Locale packs own visible labels. Preference definitions and stored values
  remain language-independent.

Recommended owner configuration:

```json
{
  "settings": {
    "theme": "system"
  },
  "reader_preferences": {
    "enabled": true,
    "view": "side-panel",
    "groups": [
      {
        "id": "appearance",
        "fields": ["appearance.theme"]
      }
    ]
  }
}
```

The abstract `side-panel` view deliberately does not expose `sf-modal`,
`sf-drawer`, CSS classes or physical positioning in project configuration.
Docara maps it to the currently admitted Framework implementation. This keeps
author configuration stable when the internal component changes later.

### 2. Use a registry of typed preference definitions

Each available preference is registered by Docara core or an extension:

```json
{
  "id": "appearance.theme",
  "group": "appearance",
  "value_schema": {
    "type": "string",
    "enum": ["system", "light", "dark"]
  },
  "control": {
    "kind": "choice"
  },
  "effect": "docara.theme",
  "apply_phase": "prepaint",
  "storage_scope": "site",
  "copy": {
    "label": "reader.theme.label",
    "help": "reader.theme.help"
  }
}
```

The site configuration may enable, disable, order and narrow declared options.
It may not inject selectors, scripts, arbitrary CSS variables, component tags
or storage keys.

An extension contributes fields through a platform-neutral
`DocaraPreferenceContribution` contract:

- stable contribution id and version;
- typed field definitions;
- localized copy keys;
- registered effect adapters;
- optional immutable assets;
- explicit compatibility requirements.

Duplicate ids, unknown fields, invalid values, missing copy, missing effects
and dependency cycles fail the build.

### 3. Compile one immutable runtime manifest

The build pipeline:

1. loads core and extension contributions;
2. validates the site allowlist from `reader_preferences`;
3. resolves defaults from authored `settings`;
4. compiles the effective groups and fields;
5. emits an immutable reader-preferences manifest;
6. plans only the required Framework assets;
7. renders the generic settings surface.

The manifest can initially be embedded as an
`application/json` script in each page. A separate
`_docara/reader-preferences.json` is appropriate only when one cached resource
is measurably better.

### 4. Give each layer one owner

| Layer | Owner |
|---|---|
| overlay, focus trap, Escape, scroll lock, return focus | SIMAI Framework modal |
| groups, field projection, reset and status | product Smart component `docara.preferences` |
| radio, switch, select, input and buttons | admitted SIMAI Framework controls |
| definitions, validation and compilation | Docara PHP core |
| normalized reader values | preference store adapter |
| DOM/theme/token changes | named effect adapters |

For the first implementation, `side-panel` maps to the locked
`sf-modal position="right"`. No Framework release is required for that shell.
The generated `sf-drawer` found in the development repository is not part of
Docara's current immutable runtime lock and must not become a hidden moving
dependency. After Drawer obtains canonical source, registry admission and an
immutable release, the adapter may switch to logical `inline-end`.

For RTL before that release, Docara maps `side-panel` to right in LTR and left
in RTL. The public configuration remains logical rather than physical.

### 5. Persist one versioned document

A static site uses `localStorage`, not PHP session and not `sessionStorage`.
Recommended key:

```text
docara.preferences.<site-id>.v1
```

Value:

```json
{
  "schema": 1,
  "values": {
    "appearance.theme": "dark"
  }
}
```

Rules:

- omit values equal to the site default;
- validate every stored value before applying it;
- apply `prepaint` preferences before Framework CSS to prevent theme flash;
- apply remaining effects after the shell is ready;
- synchronize tabs with the `storage` event;
- fall back to in-memory state when storage is unavailable;
- support reset of one field, one group and all reader preferences;
- keep site-wide preferences independent of locale unless a definition
  explicitly declares locale scope.

Because Docara is still under development, the old standalone
`docara.reader.theme.v1` key and legacy theme cookie should be removed when the
new store lands. Do not add a permanent compatibility layer.

### 6. Apply effects through a whitelist

The JSON manifest describes intent, not executable DOM operations.

Examples:

- `docara.theme` toggles the admitted root theme classes and `color-scheme`;
- `docara.content-width` maps values to admitted `max-container-*` states;
- `docara.motion` maps values to an admitted reduced-motion state.

Every effect adapter declares accepted values and whether it runs during
prepaint or normal runtime. Arbitrary selectors, style strings and JavaScript
callbacks are forbidden.

## Interface model

### Desktop

- open a panel from the logical inline end;
- use the Framework dark neutral overlay in every theme;
- panel surface follows the active theme;
- sticky title and close action;
- independently scrollable body;
- no left category rail while there are four or fewer groups;
- with more groups, add a compact category rail similar in principle to the
  Bitrix example, not its density or legacy styling.

### Mobile

- use a full-width or near-full-width end panel;
- replace a two-column category rail with a one-column group list;
- retain standard Framework control heights and focus states;
- preserve Escape, overlay close and trigger-focus return.

Preferences such as theme should apply immediately. Do not add a Save button
unless a future field is expensive or unsafe to preview. Reset is always
explicit.

The overlay is always dark neutral. The panel itself should not be forced dark
in light theme: it follows the chosen theme so controls keep their standard
tokens and contrast.

## Bitrix pattern retained and rejected

Retain:

- definitions separate from stored values;
- grouped property descriptors;
- generic control rendering;
- conditions and defaults;
- one persistence interface.

Reject:

- PHP-specific field classes;
- server-written files;
- jQuery and inline styling;
- the large expert-mode settings surface;
- raw template names and executable conditions in public site configuration.

`ui-admin` is useful only as evidence that Framework controls can compose an
editing surface. It is not the settings engine and Docara must not depend on
its backend API.

## Initial scope

The first production-visible registry contains only
`appearance.theme`. A second disabled fixture proves that a new field can be
registered, compiled, rendered, persisted and applied without editing the
settings template. Font size, content width and motion are not enabled merely
because the architecture supports them.

## Implementation batches

### Batch 1 — contract and compiler

- add `reader_preferences` to the site schema;
- add preference definition and contribution types;
- implement registry, validation and effective manifest compilation;
- add positive and fail-closed fixtures;
- document the separation between `settings` and `reader_preferences`.

### Batch 2 — Smart shell

- create product-owned `docara.preferences`;
- map `side-panel` to locked `sf-modal`;
- remove the native `<dialog>`, custom backdrop and manual focus trap;
- keep one settings trigger and one modal lifecycle.

### Batch 3 — store and theme field

- introduce the versioned local store;
- move the theme bootstrap to the registered prepaint effect;
- remove the standalone theme key and legacy cookie path;
- implement field, group and global reset plus cross-tab synchronization.

### Batch 4 — extensibility proof

- add one disabled test contribution;
- prove generic rendering with existing Framework controls;
- verify that enabling it requires only registry admission and
  `docara.json`, not template changes.

### Batch 5 — documentation and acceptance

- add author documentation and a generated registry reference;
- rebuild `docara.test`;
- verify desktop, mobile, LTR, RTL, keyboard, storage failure, light and dark
  themes;
- run the complete static build, schema, unit and browser acceptance suite.

## Acceptance criteria

- dark-neutral overlay is visually identical in light and dark themes;
- reader settings open from the logical inline end;
- no native settings dialog or Docara-owned modal focus trap remains;
- configuration can enable, disable and order registered fields;
- invalid fields, effects, values and dependencies stop the build;
- theme is applied before paint without a visible flash;
- normalized values persist across pages and browser restarts;
- changes synchronize between tabs and reset to authored defaults;
- labels come from locale packs and work in all declared locales;
- keyboard focus, Escape, overlay close and focus return pass;
- RTL opens from the correct logical side;
- no moving Framework reference or unadmitted Drawer dependency is introduced;
- the initial public UI remains simple and shows only the theme setting.

## Framework follow-up

No Framework change blocks the first implementation. Separately:

1. finish canonical source and release admission for `sf-drawer`;
2. verify its overlay, logical placement, focus and stacked-modal contracts;
3. consider a generic `sf-property` or `sf-settings-field` renderer only after
   Docara and Larena prove the same field contract in two real consumers.

The orchestration and preference registry remain product/platform code rather
than being moved into the visual Framework.

## Completion

The initial production-visible implementation is complete:

- `reader_preferences` is a site-only validated contract;
- the registry compiles typed contributions and rejects unknown or invalid
  fields fail-closed;
- `docara.preferences` renders the settings surface through the admitted
  `sf-modal` side panel;
- one versioned, site-isolated JSON value is stored in `localStorage`;
- the theme effect is applied before paint, synchronized across tabs and can
  be reset to the authored site value;
- locale copy, LTR/RTL placement and the disabled-settings path are covered;
- the previous native dialog, theme cookie and standalone theme key are not
  used by the declarative runtime;
- the exact generated site was published to the local test surface.

Acceptance result: `PASS`.

Evidence:
`source/workflow/evidence/2026-07-24-docara-modular-reader-preferences/acceptance.md`.

This acceptance applies to the local Docara candidate and `docara.test`. It
does not claim a public release, default-branch merge or production readiness.

Post-acceptance correction: the preferences panel now overrides the public
Framework modal-shadow variable with fixed black alpha tokens, so its
elevation never becomes light in the dark theme. The corrected local
publication passed the full `331 tests / 5120 assertions` suite, static build
verification and live visual inspection.
