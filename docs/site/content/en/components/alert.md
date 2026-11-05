---
description: How to show a tip, a warning or an error message in the text so that the reader does not miss it.
---

# Alert

An alert highlights what the reader must not miss in the text: a tip, a
result, a warning or an error.

## Example

:::example {label="Alert"}
```markdown
:::alert {type=info}
#### Useful information

Carry on as usual.
:::
```
:::

## Markup

```markdown
:::alert {type=warning}
#### A short heading for the situation

What happened and what to do next.
:::
```

- The first line is a `##`–`#####` heading: it names the situation briefly.
- After it comes an explanation or the next action. An alert without a
  heading or without an explanation stops the build.

The alert is built on the SIMAI Framework alert (`sf-alert`).

## Variants

### Type

`type` carries the meaning of the message: the colour and the icon depend on
it.

:::example {label="Alert types"}
```markdown
:::alert {type=clear}
#### For your information

This message needs no action.
:::

:::alert {type=info}
#### Useful information

Carry on as usual.
:::

:::alert {type=success}
#### Done

The build passed the checks.
:::

:::alert {type=warning}
#### Note

Check the parameters before publishing.
:::

:::alert {type=danger}
#### Fix required

The configuration failed the checks.
:::
```
:::

### Look

`variant` changes the density of the styling, not the meaning.

:::example {label="Alert look"}
```markdown
:::alert {type=info variant=default}
#### Filled

Suits most alerts.
:::

:::alert {type=info variant=flat}
#### No shadow

For a calm message.
:::

:::alert {type=info variant=outlined}
#### Outlined

When you need a lighter accent.
:::
```
:::

## Accessibility

- An alert is a `status` region named after its heading: screen readers
  announce it as a separate block.
- Colour is not the only carrier of meaning: the icon and the heading both
  convey the type. Name the situation in words: "Note", "Fix required".
- Do not put several alerts in a row: the reader stops noticing them.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `type` | `clear`, `info`, `success`, `warning`, `danger` | `info` | The meaning of the message: `clear` is neutral, `info` is information, `success` is success, `warning` is a warning, `danger` is a danger or an error. |
| `variant` | `default`, `flat`, `outlined` | `default` | The look: `default` is filled, `flat` has no shadow, `outlined` has a border. |

<!-- docara:component-parameters:end -->

## See also

- [Banner](../banner/) — an announcement for the whole page
- [Details](../details/) — for additional details
- [SIMAI Framework ui.* components](../smart-ui/) — the `ui.alert` alert
