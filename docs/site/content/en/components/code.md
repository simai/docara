---
description: "The code block in Docara: the language and file name in the header, syntax highlighting, line numbers and highlighted lines, changes, focus, a terminal, copying and line wrapping."
---

# Code

Docara styles any fragment between three backticks as a code block: HTML,
PHP, JSON, a terminal command, a configuration or plain text. The block runs
nothing: it keeps spaces and line breaks, highlights the syntax and lets you
copy the content.

## Example

:::example {label="Code block"}
````markdown
```php {title="config/app.php"}
return [
    'name' => 'Docara',
];
```
````
:::

## Markup

~~~markdown
```php {title="install.php" highlight=2 numbers}
<?php
echo 'Done';
```
~~~

- The language goes right after the opening backticks: it picks the
  highlighting and appears in the header. Without a language the header
  shows a neutral `Code`.
- Parameters go in curly braces after the language, in any order. A
  parameter without a value, for example `numbers`, means "on".
- Lines are counted from the first line of the block, starting at one. A
  number greater than the number of lines or a reversed range stops the
  build with the code `MARKDOWN_CODE_RANGE_INVALID`.

The syntax is compatible with familiar tools. As in MkDocs Material,
parameters are written as words after the language, only in curly braces. As
in VitePress, change marks are written as a `[!code ++]` comment, and the
short form `` ```js{1,4-6} `` highlights lines.

The block is built on SIMAI Framework code highlighting (`sf-highlight`).

## Variants

### Highlighted lines and line numbers

`highlight` highlights lines, `numbers` numbers them. The count can start
from any number, for example from the line number in the file: `numbers=42`.

:::example {label="Highlighted lines with numbers"}
````markdown
```php {highlight="2-3" numbers}
$site = new Site('docs');
$site->theme('light');
$site->build();
echo 'Done';
```
````
:::

### Changes in code

To show what was added and removed, add a marker comment at the end of the
line, written the way the language writes comments: `// [!code ++]`,
`# [!code --]` or `<!-- [!code ++] -->`. Docara removes the marker from the
shown code.

| Marker | Meaning |
| --- | --- |
| `[!code ++]` | The line is added |
| `[!code --]` | The line is removed |
| `[!code focus]` | The line is in focus |
| `[!code highlight]` | The line is highlighted |

:::example {label="An added and a removed line"}
````markdown
```js
const site = createSite();
site.build(); // [!code --]
await site.build(); // [!code ++]
```
````
:::

To show the marker itself as text, write two exclamation marks:
`[!!code ++]` is shown as `[!code ++]`. In `markdown` blocks markers are not
processed, so a markup example is shown as is.

### Focus

`focus` keeps the given lines in focus and dims the others. Hover over the
block or move into it with the keyboard and all lines are visible again.

:::example {label="Focus on one line"}
````markdown
```js {focus=2}
const site = createSite();
site.theme('dark');
site.build();
```
````
:::

### Terminal

`layout=terminal` styles the block as a terminal window. A line that starts
with `$ ` is a command: the `$` sign is shown separately and is not copied.
The other lines are the command's output.

:::example {label="Commands and output in a terminal"}
````markdown
```bash {layout=terminal}
$ composer require simai/docara
Using version ^3.9 for simai/docara
$ php vendor/bin/docara build
Built 12 page(s) into build_local.
```
````
:::

## States

### Wrapping long lines

By default a long line scrolls inside the block and does not stretch the
page. The "Wrap source lines" button turns on visual wrapping; the source
text, indentation and copying do not change. The choice is saved in the
browser and applies to all code blocks on the page.

You can turn the button off for a site, a section or a page with the
`code.wrap` setting:

```json
{
  "code": {
    "wrap": false
  }
}
```

### Copying

The "Copy" button copies only the code you need to take with you:

- without line numbers, "+" and "−" signs or `[!code …]` markers;
- without removed lines — the code after the change is copied;
- in a terminal — only the commands, without the `$` sign and without the
  output.

## Accessibility

- The copy and wrap buttons get focus with the Tab key; the focus ring is
  visible when you use the keyboard.
- Added lines are marked with a "+" sign and a green background, removed
  lines with a "−" sign and red, so the difference is visible not only by
  colour. A screen reader says "added" or "removed" before such a line; you
  change the words in `lang.json` with the `code.added` and `code.removed`
  keys.
- Do not put passwords, tokens or other secrets in an example. If the code
  needs to be run, explain the environment and the expected result
  separately.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `title` | string | — | The caption above the code, for example a file name. Without it the language is shown. |
| `highlight` | string | — | Lines to highlight: numbers and ranges separated by commas, for example `1,4-6`. |
| `numbers` | string | `false` | Show line numbers: `true` or the number of the first line. |
| `focus` | string | — | Lines in focus: the others are dimmed. Written like `highlight`. |
| `layout` | `default`, `terminal` | `default` | `terminal` styles the block as a terminal window with a `$` prompt before commands. |

<!-- docara:component-parameters:end -->

## See also

- [Code from a file](../code-from-file/) — a fragment of a project file with the same parameters
- [Code in Markdown](/en/authoring/markdown/#code)
- [Interactive example](../example/)
