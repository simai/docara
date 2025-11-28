---
extends: _core._layouts.documentation
section: content
title: Custom Markdown Tags — Overview
description: Custom Markdown Tags — Overview
---

# Custom Markdown Tags — Overview

This page introduces our **custom Markdown tag** system: block-level components delimited by `!type` and `!endtype` markers, implemented on top of League CommonMark and wired into Jigsaw.

---

## What are custom tags?

Custom tags are reusable **block components** rendered from Markdown using a concise syntax:

```md
!<type> [attributes]
...inner markdown content...
!end<type>
```

They’re ideal for callouts, examples, embeds, and any structured content that should remain author-friendly while producing consistent HTML.

---

## Anatomy of a tag

* **Open marker**: `!<type>` at the start of a line; may include inline attributes.
* **Close marker**: `!end<type>` at the start of a line.
* **Inner content**: parsed as Markdown into HTML, then injected into the tag’s wrapper element.
* **Wrapper**: by default `<div>`, configurable per tag via `htmlTag()`.

> Nesting of the **same** tag type is allowed by default and can be disabled via `allowNestingSame()`.

---

## Attribute syntax (short)

Attributes on the open line support:

* Key–value pairs with quotes or unquoted: `key="value"`, `key:'value'`, `key=value`
* Short-hands: `.class` appends to `class`, `#id` sets `id`
* Multiple classes are merged and **deduplicated**
* Unicode spaces and smart quotes are normalized for robust parsing

> A per-tag `attrsFilter()` can sanitize/transform attributes before rendering.

---

## Quick start

1. **Create a tag class** that extends `BaseTag` and returns a unique `type()`.
2. **Register** the class name in `config.php` under the `tags` array.
3. **Build the site** so Docara picks up the registry and our custom parser.

---

## Minimal example

### PHP (tag definition)

```php
namespace App\Helpers\CustomTags;

use App\Helpers\CommonMark\BaseTag;

final class ExampleTag extends BaseTag
{
    public function type(): string { return 'example'; }

    public function baseAttrs(): array
    {
        return ['class' => 'example overflow-hidden radius-1/2 overflow-x-auto'];
    }
}
```

### Markdown (usage)

```md
!example .mb-4.border
**Inside** the example tag.
!endexample
```

### HTML (simplified result)

```html
<div class="example overflow-hidden radius-1/2 overflow-x-auto mb-4 border">
  <p><strong>Inside</strong> the example tag.</p>
</div>
```

---

## Rendering flow (high level)

1. **Scan**: universal parser matches `openRegex()` / `closeRegex()` from each registered tag.
2. **Capture**: everything between markers becomes the tag’s inner Markdown.
3. **Parse**: inner Markdown → HTML.
4. **Attributes**: parse and **merge** inline attributes with `baseAttrs()`; optional `attrsFilter()` runs.
5. **Render**: use `renderer()` if provided; otherwise emit `<htmlTag ...attrs>innerHtml</htmlTag>`.

---

## Docara integration (summary)

* Tag classes live in `App\Helpers\CustomTags` and extend `BaseTag`.
* Short class names are listed in `config.php => tags`.
* `bootstrap.php` binds the tag registry and swaps Docara’s default parser with our `Parser`, which installs a `CustomTagExtension` for CommonMark.

> See the dedicated page **Registering Custom Tags** for the exact configuration snippets.

---

## Do & Don’t

**Do**

* Keep `baseAttrs()` semantic and minimal
* Use `attrsFilter()` to normalize/whitelist
* Provide `renderer()` only when you need full control

**Don’t**

* Hardcode presentation that authors may want to override
* Depend on fragile attribute formats—prefer tolerant parsing

---

## FAQ (quick)

* **Can I nest tags?** Yes. Same-type nesting can be disabled with `allowNestingSame()`.
* **How do I change the wrapper element?** Override `htmlTag()` in your tag class.
* **How do I add default classes?** Return them from `baseAttrs()`; author classes are merged and deduplicated.

