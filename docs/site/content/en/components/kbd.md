---
description: How to mark a key or a key combination in an instruction so that the reader can tell it from ordinary text and code.
---

# Keyboard input

A key highlights a keyboard button inside an instruction and sets it apart
from ordinary text and code.

## Example

:::example {label="Key"}
```markdown
Press :kbd[Esc] to close the window.
```
:::

## Markup

```markdown
:kbd[Ctrl]
```

- The square brackets hold the key label, as on the keyboard. It is plain
  text: HTML does not work inside, the `<` and `>` signs are shown as is.
- An empty key `:kbd[]` stops the build with the error
  `CONTENT_COMPONENT_SLOT_REQUIRED`.

## Variants

### Combinations

Write each key separately and put a `+` sign between them as plain text.

:::example {label="Key combination"}
```markdown
Open search with :kbd[⌘] + :kbd[K], then press :kbd[Enter].
```
:::

## Accessibility

- The key is output as a `<kbd>` element: screen readers recognise it as
  keyboard input.
- Write the label as a word or a symbol, as on the keyboard: `Esc`, `Enter`,
  `⌘`.
- For a menu command, a file name or code choose plain text or backticks,
  not a key.

## Parameters

<!-- docara:component-parameters:start -->

No parameters.

<!-- docara:component-parameters:end -->

Markup with parameters, for example `:kbd[Esc]{size=1}`, stops the build
with the error `CONTENT_COMPONENT_PROP_UNKNOWN`.

## See also

- [Badge](../badge/)
- [Code](../code/)
