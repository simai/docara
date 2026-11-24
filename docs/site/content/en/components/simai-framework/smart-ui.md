---
title: "SIMAI Framework ui.* components"
description: "The ui.* Smart components from SIMAI Framework — a button, an alert, a badge, a tag, a tooltip and an avatar: what they are, how a site allows them and how to call them."
tags: [smart, ui, framework, sf-button, sf-alert, sf-badge, sf-tag, sf-tooltip, sf-avatar]
---

# SIMAI Framework ui.* components

The `ui.*` Smart components are ready-made interface elements from SIMAI
Framework: a button, an alert, an input field and others. In Markdown you
call them with a `:::ui.name` block with parameters in JSON. Docara checks
the parameters against the component description already at build time,
and in the browser the component works the same way as in other products on
SIMAI Framework.

How they differ from regular Docara components:

- a regular component, for example the [alert](/en/components/blocks/alert/)
  `:::alert`, is written as Markdown text and suits most pages;
- a Smart component takes parameters exactly as the Framework describes
  them and is needed when the page must have a real Framework element: a
  button with a loading state, an alert with a heading and supporting text
  and so on.

## How a site allows components

A site can call only the Smart components listed in the
`simai-framework.lock.json` file next to `docara.json`, in the `manifests`
section. For each component it pins the provider, the exact version and the
checksum of its description, and also the restrictions for a static site:

```json
{
  "manifests": {
    "ui.button": {
      "provider_revision": "4b055d09926fec4c32f2ae43b2e7e0a6f64d7663",
      "sha256": "235e049d…"
    },
    "ui.alert": {
      "provider_revision": "4b055d09926fec4c32f2ae43b2e7e0a6f64d7663",
      "sha256": "056329bb…",
      "consumer_policy": {
        "blocked": [
          {"prop": "closable", "value": true, "code": "FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME"},
          {"prop": "type", "value": "success", "code": "FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME"}
        ]
      }
    }
  }
}
```

`docara init` creates the file; a Docara update does not change it. The
build checks the checksums against the component descriptions that ship
with Docara: if a component is not in the lock or the description does not
match, the build stops. This site's lock allows `ui.button`, `ui.alert`,
`ui.badge`, `ui.tag`, `ui.tooltip` and `ui.avatar` — the same ones a new
project gets. The lock fields are described in detail
in the [Framework lock](/en/reference/framework-lock/) reference.

## The ui.button button

```markdown
:::ui.button
{"preset":"primary","text":"Continue","aria-label":"Continue the setup"}
:::
```

Result:

:::ui.button
{"preset":"primary","text":"Continue","aria-label":"Continue the setup"}
:::

`preset` picks a ready-made style: `primary`, `secondary`, `tonal_on_surface`,
`outline`, `outline_on_surface`, `link`, `link_on_surface` or `on_surface`.
The other parameters:

| Parameter | Value |
| --- | --- |
| `text` | The button label, up to 120 characters |
| `aria-label` | The label for screen readers, up to 120 characters |
| `size` | `1/3`, `1/2`, `1`, `2` or `3` |
| `loading` | `true` — the button shows loading |
| `disabled` | `true` — the button is unavailable |
| `native-type` | `button`, `submit` or `reset` |

:::ui.button
{"preset":"outline","text":"Saving","loading":true,"disabled":true,"native-type":"button","aria-label":"Saving in progress"}
:::

`ui.button` is an action button in an interface, not a link. To make a
button lead to another page, use a [button link](/en/components/inline/button/)
`:button[...]` or the [cta](/en/components/layout/landing-blocks/#cta-action-button)
block.

## The ui.alert alert

```markdown
:::ui.alert
{"type":"info","variant":"default","title":"The build checks parameters","supporting-text":"An unknown parameter or value stops the build before publishing.","closable":false,"aria-label":"The build checks parameters"}
:::
```

Result:

:::ui.alert
{"type":"info","variant":"default","title":"The build checks parameters","supporting-text":"An unknown parameter or value stops the build before publishing.","closable":false,"aria-label":"The build checks parameters"}
:::

| Parameter | Value |
| --- | --- |
| `type` | `clear`, `info`, `warning` or `danger` |
| `variant` | `default`, `flat` or `outlined` |
| `title` | The heading, up to 160 characters |
| `supporting-text` | The supporting text, up to 500 characters |
| `closable` | Only `false`: an alert cannot be closed on a static site |
| `aria-label` | The label for screen readers |

:::ui.alert
{"type":"warning","variant":"outlined","title":"Check the catalogue address","supporting-text":"Pin external addresses to a commit, not to a branch.","closable":false,"aria-label":"Check the catalogue address"}
:::

This site's lock forbids `type=success` and `closable=true`: a static site
has nothing to support them with. Such a call stops the build with
`FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME`. Docara sets the `id`
identifier itself.

## The ui.badge badge

A short label: a status, a type or a number.

```markdown
:::ui.badge
{"text":"Paid","status":"success"}
:::
```

Result:

:::ui.badge
{"text":"Paid","status":"success"}
:::

| Parameter | Value |
| --- | --- |
| `text` | The label, up to 80 characters |
| `status` | `info`, `success`, `warning` or `error` — what the badge reports |
| `appearance` | `tonal` (the default), `outline` or `main`. `main` is only for a count with the `error` status |
| `scheme` | `neutral`, `primary`, `secondary`, `tertiary` or `on-surface` |
| `size` | `1/3` (the default), `1/2` or `1` |
| `line` | `1/2`, `1`, `2` or `text` — the line height, so the badge does not change the height of a table row or a line of text |
| `activity` | Where a run stands: `running`, `waiting`, `done`, `stopped`, `failed`, `paused` or `queued`. The badge adds its own icon and word in the page language |
| `icon`, `icon-position` | The icon name and its place: `start` or `end` |
| `icon-only` | `true` — only the icon is visible; the text stays for screen readers |
| `tight` | `true` — narrow side room for dense places |
| `aria-label` | The label for screen readers, up to 120 characters |

:::ui.badge
{"text":"PHP 8.4","appearance":"outline","scheme":"on-surface","size":"1/2"}
:::

:::ui.badge
{"activity":"running"}
:::

For a badge inside a line of text the inline
[Docara badge](/en/components/inline/badge/) `:badge[...]` is handier.

## The ui.tag tag

A tag marks content with a short word. The `checkbox` kind is a filter
toggle: pressing it checks or unchecks it.

```markdown
:::ui.tag
{"text":"Components","kind":"checkbox","count":"12"}
:::
```

Result:

:::ui.tag
{"text":"Components","kind":"checkbox","count":"12"}
:::

| Parameter | Value |
| --- | --- |
| `text` | The label, up to 80 characters |
| `kind` | `default` (the default) — a plain label, `checkbox` — a toggle |
| `status` | `info`, `success`, `warning` or `error` |
| `appearance` | `default`, `outline` or `ghost` |
| `size` | `1/3`, `1/2` (the default), `1`, `2` or `3` |
| `height` | `low`, `default`, `high` or `highest` |
| `inline` | `true` — the tag sits on the line of another control |
| `count` | A number after the label, such as `12` or `99+` |
| `disabled` | `true` — the tag is unavailable |
| `aria-label` | The label for screen readers |

:::ui.tag
{"text":"PHP 8.4","appearance":"outline"}
:::

Tags that are removed with a cross or open a menu are not needed on this
page: a static site has nothing to handle their action, so Docara does not
accept them.

## The ui.tooltip tooltip

The Framework tooltip bubble itself: a short text, a second line and a key
combination. Here it is shown as it is, without the element it usually
appears over.

```markdown
:::ui.tooltip
{"text":"Copy the address","shortcut":"mod+c","arrow":"top-center"}
:::
```

Result:

:::ui.tooltip
{"text":"Copy the address","shortcut":"mod+c","arrow":"top-center"}
:::

| Parameter | Value |
| --- | --- |
| `text` | The main text, up to 160 characters |
| `supporting-text` | The second line, up to 300 characters |
| `shortcut` | A key combination, such as `mod+c` or `shift+/` |
| `appearance` | `dark` (the default) or `light` |
| `arrow` | Where the tail is: `none` (the default), `top-center`, `bottom-center`, `inline-start`, `inline-end`, `bottom-inline-start` or `bottom-inline-end` |

:::ui.tooltip
{"text":"Draft","supporting-text":"Only you can see this page.","appearance":"light","arrow":"inline-start"}
:::

## The ui.avatar avatar

An avatar shows a person or an organisation: initials from the name, an icon
and a mark in the corner.

```markdown
:::ui.avatar
{"heading":"Anna Smirnova","hue":"auto","status":"online"}
:::
```

Result:

:::ui.avatar
{"heading":"Anna Smirnova","hue":"auto","status":"online"}
:::

| Parameter | Value |
| --- | --- |
| `heading` | The name: the initials and the screen reader label come from it |
| `text` | Your own initials instead of the ones from the name, up to 8 characters |
| `icon` | An icon instead of the initials, for example for a bot or a service |
| `size` | `1/4`, `1/3`, `1/2`, `1` (the default), `2` or `3` |
| `shape` | `circle` (the default) — a person, `square` — an organisation, a project, a group |
| `hue` | The colour of the initials: `auto` — from the name, or a colour name: `red`, `teal`, `indigo` and others |
| `status` | The mark in the corner: `online`, `away`, `offline`, `company`, `verified` or `none` |
| `badge-count`, `badge-dot` | A count or a dot on the avatar |
| `line` | `1/2`, `1` or `2` — the line height when the avatar stands next to text |
| `aria-label` | The label for screen readers |

:::ui.avatar
{"heading":"Documentation team","shape":"square","hue":"indigo","status":"verified"}
:::

## Other components

- `ui.input`, `ui.checkbox`, `ui.dropdown` and `ui.list-item` (only the text
  variant inside `ui.dropdown`) also ship with Docara. The site's own
  components use them, for example the install command builder — see the
  [extension examples](/en/examples/extensions/). Directly in Markdown they
  can be called only on a site whose lock allows them.
- `ui.tabs` and `ui.dataview` are not accepted yet. Use the Docara
  [tabs](/en/components/blocks/tabs/) and
  [table](/en/authoring/markdown/#tables) instead.
- `ui.copy-button`, `ui.reference-link` and `ui.progress-bar` are not
  accepted yet either: they do not have a description Docara can check yet.

The `php vendor/bin/docara atlas --json` command shows which components are
available to your site, and
`php vendor/bin/docara inspect smart ui.button --json` the details of one
component. How Smart components work inside and how to write your own is
described in the [Docara Smart components](/en/development/smart-components/)
section.

## Errors

An incorrect call stops the build. The most common ones:

| Code | Cause |
| --- | --- |
| `FRAMEWORK_DIRECTIVE_JSON_INVALID` | The parameters are not valid JSON |
| `FRAMEWORK_PROP_UNKNOWN` | The component has no such parameter |
| `FRAMEWORK_PROP_ENUM_INVALID` | The value is not in the list of allowed values |
| `FRAMEWORK_PROP_MANAGED` | Docara sets the parameter itself, for example `id` |
| `FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME` | The lock forbids the value for a static site |

The causes and fixes for all codes are in the
[page error](/en/reference/errors/content/) reference.
