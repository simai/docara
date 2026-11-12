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
- `label` is the caption under the example, as under a picture: small
  muted text that wraps when long. The first tab is always called
  "Example" (the `examples.example` string from `lang.json`), so the code
  tabs stay in view. Without `label` there is no caption.
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

### Example log

An example can show a log under it: the events that happen in the example
and its console. The reader presses the buttons and sees at once what the
example reports, without developer tools. The log is part of the example's
frame, not of its content; by default there is none.

:::example {label="Links" log=click log_console=true}
```html
<div class="links">
  <button type="button" class="sf-button sf-button--default sf-button--primary sf-button--size-1 inline-flex items-center" data-href="/en/start/"><span class="sf-button-text-container">Guide</span></button>
  <button type="button" class="sf-button sf-button--outline sf-button--primary sf-button--size-1 inline-flex items-center" data-href="#parameters"><span class="sf-button-text-container">Reference</span></button>
  <button type="button" class="sf-button sf-button--tonal sf-button--primary sf-button--size-1 inline-flex items-center" data-href="mailto:team@example.com"><span class="sf-button-text-container">Write to us</span></button>
  <button type="button" class="sf-button sf-button--link sf-button--primary sf-button--size-1 inline-flex items-center" data-href="ftp://example.com"><span class="sf-button-text-container">Not a link</span></button>
</div>
```
```css
.links { display: flex; flex-wrap: wrap; gap: .75rem; }
```
```javascript
document.querySelector('.links').addEventListener('click', (event) => {
  const button = event.target.closest('[data-href]');
  if (!button) return;
  const href = button.dataset.href;
  docara.example.log('navigate', { href, text: button.textContent.trim() });
  if (href.startsWith('ftp:')) {
    console.warn('The browser does not open the ftp: scheme');
  } else {
    console.log('Navigation cancelled:', href);
  }
});
```
:::

The log is turned on with the example's parameters:

~~~markdown
:::example {label="Links" log=click log_console=true}
```html
…
```
```javascript
docara.example.log('navigate', { href, text });
console.log('Navigation cancelled:', href);
```
:::
~~~

- `log` lists event names separated by spaces, for example
  `log="click sf-button-press"`. Docara listens for them on the example's
  root before the example does, so the log also gets events the example
  stops. A row shows the time, the event name and short data: up to four
  fields of `event.detail`, long values cut with an ellipsis. For an event
  without data of its own, such as `click`, the row shows the element and
  its text instead.
- `log_console=true` adds the "Console" tab: the example's `console.log`,
  `console.info`, `console.warn` and `console.error` and its uncaught
  errors. Warnings and errors are coloured. This parameter turns the log on
  even without `log`.
- `log_open=false` opens the log collapsed.
- A line of the example's own from its code:
  `docara.example.log('submit', { plan: 'pro' })` — a name and optional
  data. The call is available in the code of any example with a log.

New rows appear at the top, and the counter on a tab shows how many there
are (the last 200 are kept). The trash button clears the open tab, the arrow
collapses the log. The log works in an isolated frame, in an inline example
and in the check at different widths.

An inline example can keep a log too — for example, collapsed until the
reader looks:

:::example {label="Inline example" log=click log_open=false}
```html
<button type="button" class="sf-button sf-button--outline sf-button--primary sf-button--size-1 inline-flex items-center"><span class="sf-button-text-container">Press</span></button>
```
:::

An inline example runs no scripts of its own: a script moves the example
into an isolated frame. That is why the "Console" tab of an inline example
shows the console of the whole page, not only of the example. In the check
at different widths an inline example opens in a frame, and there the log
gets the console of the example itself.

An invalid or repeated event name, or more than 16 names, stops the build
with `MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID`; `log_open` without
`log` and `log_console=true` stops it with `MARKDOWN_EXAMPLE_LOG_REQUIRED`.
The log's captions come from `lang.json` (the `examples.log_*` strings).

### The example's own theme

An example is usually shown in the page's theme, and theme switches inside
an example do not work: the reader chooses the theme for the whole site. To
show a theme switch itself, add `theme_switch=true`: the theme then changes
only inside the example's frame:

:::example {label="Theme switch" theme_switch=true}
```html
<div class="flex flex-col gap-2 items-cross-start p-1 radius-1 bg-surface-1">
  <sf-button-group appearance="segmented" theme-switch size="1/2"></sf-button-group>
  <p class="m-0 text-1/2">The theme changes only in this frame.</p>
</div>
```
:::

- The example always opens in an isolated frame and starts in the page's
  theme. Until the reader chooses a theme in the example itself, the frame
  follows the page's theme.
- Framework switches (`sf-button-group theme-switch`, `sf-theme-button`
  buttons, `SF.Loader.setTheme()`) work inside the frame. The page's theme
  and the reader's choice in the site settings do not change, and the choice
  made in the example is saved nowhere: after a reload the example is in the
  page's theme again.
- `preview=inline` with `theme_switch=true` stops the build with
  `MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED`: on the page the switch would change
  the theme of the whole site. An invalid value stops it with
  `MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID`.

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
- `id`, `preview`, the log (`log`, `log_console`, `log_open`) and
  `theme_switch` do not combine with `src`: the page shown lives a life of
  its own.

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

### Result only

On a home page or a landing page the code next to an example is usually not
needed. `sources=false` removes the source tabs and the line-wrap button, so
the reader sees only the result. The source stays in the block and the result
is still built from it. The width-check button stays.

:::example {label=Result sources=false}
```markdown
**Done.** The site is built and checked.
```
:::

The source of this block:

~~~markdown
:::example {label=Result sources=false}
```markdown
**Done.** The site is built and checked.
```
:::
~~~

A whole-page example with `sources=false` does not need to list the page's
files: `:::example {src="/en/start/" sources=false}` with `:::` on the next
line.

### Full page width

On a landing page (`"preset": "landing"`), `layout=wide` stretches the
example from edge to edge of the window, like a full-width surface. On a
documentation page the example stays in the text column. Use it for a showcase on a home page or a
landing page; with `sources=false` only the result remains:
`:::example {layout=wide sources=false}`.

### Loading

An isolated frame loads when it comes close to the screen (within 600 px),
and a frame in a hidden tab waits until the tab is opened. Examples below
the first screen do not slow down the page load. The frame gets the page's
theme, writing direction, fonts and colour tokens, so it looks like the
surrounding documentation.

## Accessibility

- The example tabs switch with the arrow keys; Tab moves focus to the copy,
  wrap and width check buttons.
- The example frame and its tabs are named by the `label` caption: give a clear caption.
- The width check mode is a dialog: focus stays inside it, Escape closes it
  and returns focus to the button.
- The example log is a region captioned "Example log". Its tabs switch with
  the arrow keys, a screen reader announces its rows as a log, and the
  collapse button tells whether the log is expanded.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `id` | string | — | The identifier of a shared example from the project's `examples/` folder. |
| `src` | string | — | The address of a built page of this site that the example shows whole; the page's files are listed as tabs inside the block. |
| `label` | string | `Example` | The caption under the example. Without it there is no caption and the frame is called "Example". |
| `preview` | `auto`, `inline`, `sandbox` | `auto` | How to show the result: `auto` chooses by content, `inline` shows it right on the page, `sandbox` in an isolated window. |
| `fullscreen` | `true`, `false` | — | The button for checking at desktop, tablet and phone widths. |
| `wrap` | `true`, `false` | — | The button for wrapping long lines in the source code. |
| `sources` | `true`, `false` | `true` | The source code tabs; `false` shows only the result. |
| `layout` | `column`, `wide` | `column` | The example's width: `column` follows the text column, `wide` spans the window edge to edge on a landing page. |
| `log` | string | — | The example log: event names separated by spaces that are recorded from the example's root, for example `"click sf-button-press"`. |
| `log_console` | `true`, `false` | `false` | The "Console" tab in the log: the example's `console.log`, `info`, `warn` and `error`. Turns the log on even without `log`. |
| `log_open` | `true`, `false` | `true` | Whether the log opens expanded; `false` opens it collapsed. |
| `theme_switch` | `true`, `false` | `false` | The example's own theme: with `true` a theme switch in the example changes the theme only in its frame, not on the page. |

<!-- docara:component-parameters:end -->

## See also

- [Code](../code/)
- [Code from a file](../code-from-file/)
- [Site, section and page](/en/settings/levels-and-inheritance/) — inheritance of `examples.fullscreen` and `examples.wrap`
