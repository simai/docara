---
description: "How to write the steps of an instruction: a numbered list or a timeline with the current state of the process."
---

# Steps

Steps show a short sequence of actions. The timeline marks completed,
current and future steps; a plain list suits an instruction without state.

## Example

:::example {label="Steps"}
```markdown
:::steps {current=2}
1. Create the project.
2. Add pages.
3. Publish the site.
:::
```
:::

## Markup

```markdown
:::steps
1. A short action.
2. The next action.
3. The last action.
:::
```

- Inside is exactly one numbered list. Another kind of list or no list
  stops the build.
- Write steps briefly and in one style: "Create…", "Add…". Format a heading
  inside a step in **bold**, not as a `###` heading.
- You can put steps inside a [surface](../surface/) to separate them from
  the main text.

## Variants

### Look

`layout=timeline` shows steps on a vertical line with state marks.
`layout=list` is a compact numbered list without marks.

:::example {label="List without marks"}
```markdown
:::steps {layout=list}
1. Prepare the Markdown.
2. Check the JSON.
3. Build the static site.
:::
```
:::

## States

### Current step

`current` is the number of the current step, starting from `1`. The steps
before it are marked with a check mark as completed, the steps after it as
future.

:::example {label="The third step is current"}
```markdown
:::steps {current=3}
1. The structure is ready.
2. The pages are added.
3. Check the result.
:::
```
:::

## Accessibility

- Steps are a plain numbered list: screen readers read the number and text
  of each step.
- If `current` is set, the current step is marked for screen readers
  (`aria-current="step"`): the reader hears where they are. The "completed"
  check mark is only visual — if it matters, say so in the step text.
- The marker and the text stand in separate columns, so a large font does
  not make them overlap.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `layout` | `timeline`, `list` | `timeline` | `timeline` shows steps on a line with a state mark, `list` is a plain numbered list. The old name `view` is accepted with a warning. |
| `current` | integer | `1` | The number of the current step: the previous ones are marked completed. |

<!-- docara:component-parameters:end -->

## See also

- [Tabs](../tabs/) — for equivalent options
- [Lists and quotes](/en/authoring/markdown/#lists-and-quotes)
- [Details](../details/)
