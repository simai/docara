---
description: Demos of the {set} set — whole screens built from its components.
---

# Demos

A demo shows a whole screen built from the components of the `{set}` set.
Every demo page has the same parts: what the screen is, the screen itself
as one full-screen example, and a "What it is built from" section.

## How to add a demo

1. Create an example `examples/demos/<name>/` with `index.html` and
   `index.js`.
2. Add a page `demos/<name>.md` and show the example with
   `:::example {id="demos/<name>" label="…" fullscreen=true}`.
3. In "What it is built from", list the screen's components with links.

Give a demo data that looks real: then the screen shows how it looks at
work. The example answers the component the way the application's server
would.

## See also

- [Components](../components/) — the demos' screens are built from them.
