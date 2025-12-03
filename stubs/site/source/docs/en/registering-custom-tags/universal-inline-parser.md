---
extends: _core._layouts.documentation
section: content
title: UniversalInlineParser
description: UniversalInlineParser
---

# UniversalInlineParser

This optional parser handles **single-line inline tags** written as `!type ... !endtype` inside a paragraph. It reuses the same tag specs as the block parser and supports inline attributes.

> Inline tags are disabled unless `CustomTagsExtension` registers the inline parser (Docara core does this).

---

## Syntax

```md
Use !label .pill #id text inside a sentence !endlabel for quick badges.
```

- Opens with `!type`, closes with `!endtype` on the **same line**.
- Attributes on the open marker are parsed/merged just like block tags.
- Inner text between the markers becomes the tag contents.

---

## How it works
- Scans specs from `CustomTagRegistry` and matches `!<type> ... !end<type>` inline.
- Parses `attrs` via `Attrs::parseOpenLine`, merges with `baseAttrs()`, and applies `attrsFilter()` if present.
- Builds a `CustomTagInline` node with the inner text as a child.
- Renders via `CustomTagRenderer` (default wrapper uses `htmlTag()`; per-tag `renderer()` is for block nodes only).

---

## Caveats
- Only works for tags that define a `closeRegex` (single-line block tags without a close marker are ignored).
- Per-tag `renderer()` closures currently target block nodes; inline tags use the default wrapper (`htmlTag()`) and rendered children.
- Keep inline content on the same line; multiline spans are for block tags.

---

## Minimal example

**Tag class**
```php
final class LabelTag extends BaseTag
{
    public function type(): string { return 'label'; }
    public function htmlTag(): string { return 'span'; }
    public function baseAttrs(): array { return ['class' => 'label']; }
}
```

**Markdown**
```md
This is !label .info Inline badge !endlabel inside text.
```

**Rendered (simplified)**
```html
<span class="label info">Inline badge</span>
```
