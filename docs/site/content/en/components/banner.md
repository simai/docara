---
description: How to show a banner with an announcement for the whole page — about the documentation version, a validity period or an important change.
---

# Banner

A banner shows a short announcement that applies to the whole page: to the
documentation version, a validity period or an important change. For a note
next to a specific step choose an [alert](../alert/).

## Example

:::example {label="Banner"}
```markdown
:::banner {type=info}
This version of the documentation applies to the current stable build.
:::
```
:::

## Markup

```markdown
:::banner {type=warning}
One or two sentences: what changed and what to do.
:::
```

- Inside is plain Markdown, without a heading. A banner without text stops
  the build.
- Put the banner at the start of the page, before the main text.

The banner is built on the SIMAI Framework alert (`sf-alert`) in its flat
look.

## Variants

### Type

`type` sets the meaning of the announcement: the colour and the icon depend
on it.

:::example {label="Banner types"}
```markdown
:::banner {type=info}
This version of the documentation applies to the current stable build.
:::

:::banner {type=success}
The documentation is updated to the current stable version.
:::

:::banner {type=warning}
Save the current build before updating.
:::

:::banner {type=danger}
This version no longer receives security fixes.
:::
```
:::

## Accessibility

- A banner is a `status` region: screen readers announce it as a separate
  message.
- The icon and the text carry the meaning, not only the colour. Name the
  consequence or the next action in words.
- One banner per page: readers skip several announcements in a row.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `type` | `info`, `success`, `warning`, `danger` | `info` | The meaning of the message: information, success, warning or danger. |

<!-- docara:component-parameters:end -->

## See also

- [Alert](../alert/) — a note next to the text
- [Button link](../button/) — a move to the next action
- [Landing blocks](../landing-blocks/) — a call to action on a landing page
