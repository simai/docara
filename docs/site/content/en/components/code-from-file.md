---
description: How to insert a fragment of a project file by line numbers, by a named region or between marker lines, so that the code on the page does not drift from the source.
---

# Code from a file

The block inserts a fragment of a project file without copying the code into
Markdown. The file is read at build time, so the code on the page always
matches the source at the moment of publication.

## Example

:::example {label="Code from a file"}
```markdown
:::code {src="../../../snippets/install.php" lines="1-8"}
:::
```
:::

## Markup

```markdown
:::code {src="../../../snippets/install.php" lines="1-8"}
:::
```

- The block is empty: the code comes from the `src` file. The path is
  relative to the page's folder and does not leave the project.
- The language and the caption come from the file name; you can set them
  with `lang` and `title`.
- The fragment is selected in one way: `lines`, `region` or the pair
  `start_after` and `end_before`. Two ways at once stop the build with the
  code `MARKDOWN_CODE_OPTIONS_CONFLICT`.
- `highlight`, `numbers`, `focus` and `layout` work as in a
  [regular code block](../code/).

## Variants

### File lines

`lines` is one line `4` or a range `4-12`, counting from one. If the end of
the range is beyond the number of lines, the lines up to the end of the file
are shown.

### File region

You mark a region in the file itself with a comment in the file's language:
`// #region build` and `// #endregion build`. `# region build` and
`<!-- #region build -->` work too. The marker lines are not shown on the
page.

:::example {label="The build region with the file's line numbers"}
```markdown
:::code {src="../../../snippets/site-builder.php" region=build dedent=true numbers highlight=3}
:::
```
:::

With `numbers` the count follows the file's lines: you can see where the
fragment sits in the source. `highlight` counts lines from the start of the
fragment. `dedent=true` removes the common indentation of the shown lines.

### Between marker lines

If the file has no regions, you select the fragment by the text of the
neighbouring lines. The lines with the `start_after` and `end_before` text
are not part of the fragment.

:::example {label="Between marker lines"}
```markdown
:::code {src="../../../snippets/site-builder.php" start_after="#region read" end_before="#endregion read"}
:::
```
:::

## States

### Checks at build time

The build checks the file in advance and stops if:

- the path is absolute or leads outside the project root;
- the file does not exist, is a folder or a symbolic link;
- the file is larger than 1 MiB or is not encoded in UTF-8;
- the `lines` range is written incorrectly, its end is before its start or
  its start is past the last line of the file;
- the `region` or the `start_after` and `end_before` text is not in the file.

Error codes and how to fix them are in the
[error reference](/en/reference/errors/content/).

## Accessibility

- The block looks and works like a regular code block: the same copy and
  wrap buttons, the same change marks.
- The caption above the block is the file name by default. If the fragment
  is part of a file, give a caption that says so:
  `title="install.php, installation"`.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `src` (required) | string | — | The path to the project file the code comes from. |
| `lang` | string | `text` | The language for syntax highlighting. |
| `lines` | string | — | A range of the file's lines, for example `10-24`. |
| `title` | string | — | The caption above the code. Without it the file name is shown. |
| `highlight` | string | — | Lines to highlight: numbers and ranges separated by commas. |
| `numbers` | string | `false` | Show line numbers: `true` or the number of the first line. |
| `focus` | string | — | Lines in focus: the others are dimmed. |
| `layout` | `default`, `terminal` | `default` | `terminal` styles the block as a terminal window. |
| `region` | string | — | The name of a region marked in the file with `#region` and `#endregion` comments. |
| `start_after` | string | — | A marker line: the code starts from the line after it. |
| `end_before` | string | — | A marker line: the code runs up to it. |
| `dedent` | `true`, `false` | `false` | Remove the common indentation from all lines of the fragment. |

<!-- docara:component-parameters:end -->

## See also

- [Code](../code/)
- [Interactive example](../example/) — a shared example from the project
- [Project files](/en/start/project-files/)
