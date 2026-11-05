---
description: "How to show an interactive example: the finished result next to the source code, checks at desktop, tablet and phone widths, copying the call."
---

# Interactive example

The block shows the finished result and its source side by side. The reader
switches tabs, checks the behaviour at different screen widths and copies
the exact call.

## Example

:::example {label="Example"}
```markdown
:badge[Done]{variant=main scheme=success size=1/2}
```
:::

## Markup

~~~markdown
:::example {label="Result name"}
```markdown
:badge[Done]{variant=main scheme=success}
```
:::
~~~

- Inside is the example source in a code block: one `markdown` block or an
  `html` block with optional `css` and `javascript`.
- `label` is the caption of the result tab. Without it the tab is called
  "Example" (the `examples.example` string from `lang.json`).
- Instead of the source you can refer to a shared project example:
  `id="…"`. You cannot mix the two ways.
- To show a whole site page and the files it is built from, give its
  address in `src`. See "Whole-page example" for details.

## Variants

### HTML, CSS and JavaScript

For a small self-contained interface, combine HTML with optional CSS and
JavaScript. Such an example runs in an isolated frame and cannot see the
documentation page.

:::example {label="Interactive example"}
```html
<button class="example-button" type="button">Press</button>
<output class="example-result">Ready</output>
```
```css
:root { color-scheme: light dark; }
body { color: CanvasText; background: Canvas; }
.example-button { padding: .5rem 1rem; }
.example-result { margin-inline-start: 1rem; }
```
```javascript
document.querySelector('.example-button').addEventListener('click', () => {
  document.querySelector('.example-result').textContent = 'Done';
});
```
:::

### Shared example from the project

If an example is needed on several pages or in all languages, keep it once
in the root `examples/` folder:

```text
examples/
  utilities/
    animation-duration/
      index.html
      index.css
      index.js
      assets/
```

`index.html` is required, the rest as needed. The folder path becomes the
identifier:

```markdown
:::example {id="utilities/animation-duration" label="Result"}
:::
```

Docara shows only the tabs that exist and publishes the needed files with
`base_url` taken into account. After changing a shared example, run a full
build: a targeted build stops so that it does not leave other pages with
the old version.

### Whole-page example

The header, menu, layout regions and section settings are visible only on a
whole page. Build such a page separately and show it in an example: give its
address in `src`, and inside the block list the files it is built from. Each
`###` heading becomes a tab, and under it goes a
[code from a file](../code-from-file/) block that reads the real file:

~~~markdown
::::example {src="/en/demonstrator-results/region-header/" label="Result"}
### page.json

:::code {src="../demonstrator-results/region-header.page.json" lang=json highlight="20-37"}
:::

### Page Markdown

:::code {src="../demonstrator-results/region-header.md" lang=markdown}
:::
::::
~~~

- The outer block opens with four colons so that the nested code blocks
  close with their own three.
- `src` is a path from the site root, without a domain. Foreign addresses
  are not accepted.
- Inside the block go only `###` headings and `:::code` blocks. The code
  always matches the files the page is built from.
- In the article the page is shown as a 1280 px desktop screen, scaled down
  to the column width. The width check button opens it full screen at the
  real width of a desktop, tablet or phone.
- `id` and `preview` do not combine with `src`.

A ready sample is [Header with an action button](/en/examples/region-header/).

### Display mode

`preview` picks where the result is shown:

- `auto` — Docara decides at build time: Markdown and safe HTML without its
  own styles and scripts are embedded right into the page, the rest opens in
  an isolated frame;
- `inline` — right in the page. If the example needs isolation, the build
  stops with a clear error;
- `sandbox` — in an isolated frame, when your own styles, scripts or an
  independent environment matter.

With `auto`, markup with responsive utilities (`sm:`, `md:`, `lg:`,
`cq-md:` …) opens in a frame: such utilities depend on the window width,
and an embedded example would see only the page width.

:::example {label="Responsive card" preview=sandbox}
```html
<article class="adaptive-card">
  <strong>Product card</strong>
  <span>The price and the action change places on a phone.</span>
  <button type="button">Add to cart</button>
</article>
```
```css
.adaptive-card {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  align-items: center;
  padding: 1rem;
  border: 1px solid currentColor;
  border-radius: 1rem;
}

.adaptive-card button {
  grid-column: 2;
  grid-row: 1 / span 2;
}

@media (max-width: 600px) {
  .adaptive-card {
    grid-template-columns: 1fr;
  }

  .adaptive-card button {
    grid-column: 1;
    grid-row: auto;
  }
}
```
:::

The chosen mode and its reason are recorded in
`build_<environment>.docara/examples.json` and are visible through
`inspect page`.

### What is embedded into the page

An embedded example looks the same as on a real page: dropdowns and
tooltips open over the documentation. The documentation's text styles do
not affect it. The Framework components that the markup uses are loaded
automatically.

Embedded HTML allows:

- `data-*` attributes, except `data-docara-*`; the value cannot start with
  the `javascript:`, `vbscript:` or `data:` schemes;
- an `id` in kebab-case with at least one hyphen, not starting with
  `docara-`;
- a `slot` attribute with a simple kebab-case value;
- a `form` element without `action`, `method`, `target`, `enctype` and
  `name`: Docara cancels the submission of such a form;
- on `sf-*` Smart elements, the attributes that the pinned Framework version
  declares for that tag.

The `on*`, `href`, `src`, `srcdoc`, `style`, `action`, `formaction`, `form`,
`autofocus` and `contenteditable` attributes are never embedded, nor are the
`script`, `style`, `link`, `iframe`, `a`, `img` elements and others outside
the list. Such an example stays in a frame.

The `id` of an embedded example lives in the page's shared namespace. A
clash with another example or a shell element stops the build with the
error `MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE`. Rename the `id` or set
`preview=sandbox`.

## States

### Checking at different widths

The button with devices in the example header opens it full screen with a
choice of width: desktop is 1280 px or the screen width if it is narrower,
tablet is 768 px, phone is 390 px. The real width of the isolated frame
changes, so responsive rules fire the same way as in a separate window. For
an embedded example the frame loads only when the check opens, and the
regular result is shown until it is ready. The exit button and Escape
return the example to its place.

### Wrapping long lines

On the source tab, a button turns on visual wrapping of long lines. The
source text and copying do not change. The choice is shared by all examples
and code blocks on the page and is saved in the browser.

Both buttons can be turned off for a site, a section or a page:

```json
{
  "examples": {
    "fullscreen": false,
    "wrap": false
  }
}
```

or for one block: `:::example {fullscreen=false wrap=false}`.

### Loading

An isolated frame loads when it comes close to the screen (within 600 px),
and a frame in a hidden tab waits until the tab is opened. Examples below
the first screen do not slow down the page load. The frame gets the page's
theme, writing direction, fonts and colour tokens, so it looks like the
surrounding documentation.

## Accessibility

- The example tabs switch with the arrow keys; Tab moves focus to the copy,
  wrap and width check buttons.
- The result frame is named by the `label` caption: give a clear caption.
- The width check mode is a dialog: focus stays inside it, Escape closes it
  and returns focus to the button.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `id` | string | — | The identifier of a shared example from the project's `examples/` folder. |
| `src` | string | — | The address of a built page of this site that the example shows whole; the page's files are listed as tabs inside the block. |
| `label` | string | `Example` | The caption of the result tab. |
| `preview` | `auto`, `inline`, `sandbox` | `auto` | How to show the result: `auto` chooses by content, `inline` shows it right on the page, `sandbox` in an isolated window. |
| `fullscreen` | `true`, `false` | — | The button for checking at desktop, tablet and phone widths. |
| `wrap` | `true`, `false` | — | The button for wrapping long lines in the source code. |

<!-- docara:component-parameters:end -->

## See also

- [Code](../code/)
- [Code from a file](../code-from-file/)
- [Site, section and page](/en/settings/levels-and-inheritance/) — inheritance of `examples.fullscreen` and `examples.wrap`
