---
extends: _core._layouts.documentation
section: content
title: BaseTag & Interfaces
description: BaseTag & Interfaces
---

# BaseTag & Interfaces

This page defines the contract for custom tags and documents the default behaviors provided by `BaseTag`. Start here before implementing your own tag classes.

---

## The contract: `CustomTagInterface`
Located at `App\Helpers\Interface\CustomTagInterface.php`.

```php
interface CustomTagInterface
{
    public function type(): string;

    /** Regex for the opening line. Must expose an `attrs` named group if attributes are supported. */
    public function openRegex(): string;

    /** Regex for the closing line. */
    public function closeRegex(): string;

    /** Wrapper element for default rendering (e.g., 'div', 'section'). */
    public function htmlTag(): string;

    /** Baseline attributes merged with inline attributes on the open line. */
    public function baseAttrs(): array;

    /** Allow nesting of the same tag type inside itself. */
    public function allowNestingSame(): bool;

    /** Optional filter to normalize/whitelist attributes. Signature: fn(array $attrs, array $meta): array */
    public function attrsFilter(): ?callable;

    /** Optional renderer to fully control output. Signature: fn(string $innerHtml, array $attrs): string */
    public function renderer(): ?callable;
}
```

> You normally **extend `BaseTag`** which implements this interface with safe defaults and proven regexes.

---

## Default implementation: `BaseTag`
Located at `App\Helpers\CommonMark\BaseTag.php`.

```php
abstract class BaseTag implements CustomTagInterface
{
    abstract public function type(): string;

    public function openRegex(): string {
        return '/^\s*!' . preg_quote($this->type(), '/') . '(?:\s+(?<attrs>.+))?$/u';
    }

    public function closeRegex(): string {
        return '/^\s*!end' . preg_quote($this->type(), '/') . '\s*$/u';
    }

    public function htmlTag(): string { return 'div'; }

    public function baseAttrs(): array { return []; }

    public function allowNestingSame(): bool { return true; }

    public function attrsFilter(): ?callable { return null; }

    public function renderer(): ?callable { return null; }
}
```

### Why these defaults?
- **Regexes** are anchored at the **start of the line** and tolerate leading whitespace. The `openRegex()` exposes a named capture `attrs` so the parser can extract inline attributes if present.
- **`htmlTag()`** defaults to `div`, which is the safest block wrapper.
- **`allowNestingSame()`** is `true` to keep authoring flexible; you can disable it where it makes semantic sense.
- **`attrsFilter()`** and **`renderer()`** are opt-in extension points: use them only when you need additional control.

---

## Method-by-method guidance

### `type(): string`
- Unique, short, lowercase by convention (e.g., `note`, `example`, `video`).
- Appears in Markdown as `!<type>` and `!end<type>`.

### `openRegex()` / `closeRegex()`
- If you override, preserve the **semantics**:
    - Anchor with `^` to avoid accidental matches mid-line.
    - Keep the **named group** `(?<attrs>...)` for the open line if you want inline attributes.
    - Use the Unicode `u` modifier so `\s` and character classes handle non-ASCII whitespace.
- Example (customizing to allow an alias):

```php
public function openRegex(): string {
    $t = preg_quote($this->type(), '/');
    return '/^\s*!(?:' . $t . '|ex)\b(?:\s+(?<attrs>.+))?$/u';
}

public function closeRegex(): string {
    $t = preg_quote($this->type(), '/');
    return '/^\s*!end(?:' . $t . '|ex)\b\s*$/u';
}
```

> Changing regexes is advanced: ensure you don’t break the parser’s ability to find boundaries or capture `attrs`.

### `htmlTag(): string`
- Return the wrapper element name, e.g., `'section'`, `'aside'`, `'figure'`.
- Keep it a **valid HTML tag name**; the renderer doesn’t validate element names.

### `baseAttrs(): array`
- Provide minimal semantic defaults, most commonly base CSS classes.
- Attributes merge order is: `baseAttrs()` → inline attributes from Markdown → renderer-time adjustments.
- Classes are **concatenated and de-duplicated**; scalars (like `id`) are overridden by later sources.

### `allowNestingSame(): bool`
- Return `false` to disallow `!note` inside `!note` (the block parser will treat inner opens as text until the outer close).

### `attrsFilter(): ?callable`
- Signature: `fn(array $attrs, array $meta): array`.
- `$meta` contains parser metadata from the opening line, including:
  - `openMatch` — the full regex match array for `openRegex()` (e.g., named groups)
  - `attrStr` — the raw attribute substring after `!type`
- Good for **whitelisting**, **mapping** semantic options into classes, or deriving attrs from **captured groups**.

Example: map `theme` to classes, strip unknown keys, and use a named capture `variant` from `openRegex()` if present:

```php
public function attrsFilter(): ?callable
{
    return function (array $attrs, array $meta): array {
        $out = [];
        $allowed = ['id', 'class', 'data-x', 'theme'];
        foreach ($attrs as $k => $v) if (in_array($k, $allowed, true)) $out[$k] = $v;

        // optional: derive from open regex capture
        $variant = $meta['openMatch']['variant'] ?? null; // requires a (?<variant>...) group in openRegex
        if ($variant) {
            $out['class'] = trim(($out['class'] ?? '') . ' variant-' . $variant);
        }

        if (isset($out['theme'])) {
            $map = ['info' => 'is-info', 'warning' => 'is-warn'];
            $cls = $map[$out['theme']] ?? null;
            unset($out['theme']);
            if ($cls) $out['class'] = trim(($out['class'] ?? '') . ' ' . $cls);
        }
        return $out;
    };
}
```

### `renderer(): ?callable`
- Signature: `fn(string $innerHtml, array $attrs): string`.
- Use when the default `<htmlTag ...>innerHtml</htmlTag>` is not enough.
- **Escape attributes** you re-inject; use `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
- Example: render as `<figure>` with an optional caption attribute:

```php
public function renderer(): ?callable
{
    return function (string $innerHtml, array $attrs): string {
        $classes = htmlspecialchars($attrs['class'] ?? '', ENT_QUOTES, 'UTF-8');
        $caption = htmlspecialchars($attrs['caption'] ?? '', ENT_QUOTES, 'UTF-8');
        $fig = '<figure class="' . $classes . '">' . $innerHtml;
        if ($caption !== '') $fig .= '<figcaption>' . $caption . '</figcaption>';
        return $fig . '</figure>';
    };
}
```

---

## Lifecycle of a tag (end-to-end)
1. **Open/Close detection**: `UniversalBlockParser` matches `openRegex()` / `closeRegex()` for the tag’s `type()`.
2. **Inner parse**: everything between markers is parsed as Markdown into child nodes.
3. **Attributes**: the open line’s `attrs` segment is parsed by `Attrs`, normalized (Unicode spaces/quotes), and merged with `baseAttrs()`.
4. **Filtering**: if `attrsFilter()` exists, it is called as `fn($attrs, $meta)` where `$meta` includes `openMatch` and `attrStr`.
5. **Rendering**: if `renderer()` exists, it is called; otherwise the default `<htmlTag ...attrs>innerHtml</htmlTag>` is emitted.

---

## Best practices
- Keep `type()` short and stable; changing it is a breaking authoring change.
- Prefer `baseAttrs()` + author classes over hardcoding heavy styling.
- Use `attrsFilter()` to **normalize** author input; avoid doing this in `renderer()`.
- Escape everything you output in a custom `renderer()`.
- Write a small Markdown fixture per tag; it doubles as documentation.

### Anti‑patterns
- Overriding `openRegex()` without keeping the `attrs` capture.
- Returning invalid element names from `htmlTag()`.
- Packing complex logic into `renderer()` that belongs in CSS or `attrsFilter()`.

---

## Minimal tag template
Use this as a starting point for new tags.

```php
namespace App\Helpers\CustomTags;

use App\Helpers\CommonMark\BaseTag;

final class MyTag extends BaseTag
{
    public function type(): string { return 'mytag'; }

    public function baseAttrs(): array { return ['class' => 'mytag']; }

    // Optional normalization
    public function attrsFilter(): ?callable
    {
        return fn(array $a) => $a; // no-op by default
    }

    // Optional custom render
    // public function renderer(): ?callable
    // {
    //     return fn(string $html, array $attrs): string => $html;
    // }
}
```

---

## Testing checklist
- Open/close markers recognized; nested same-type behavior matches `allowNestingSame()`.
- Attributes: quoted/unquoted, `.class`, `#id` are parsed and merged; classes de‑duplicated.
- `attrsFilter()` behaves as intended on valid/invalid inputs.
- Default wrapper vs custom `renderer()` both produce valid, escaped HTML.

---

## FAQ
**Q: Can I support boolean attributes (flag style)?**  
A: Prefer explicit `key="true"` or map via `attrsFilter()` (e.g., treat presence of `flag` key as `true`).

**Q: How do I provide multiple aliases for one tag?**  
A: Override `openRegex()`/`closeRegex()` carefully (see example), but keep the `attrs` capture and start anchors.

**Q: How do I prevent specific attributes?**  
A: Implement `attrsFilter()` and whitelist keys; drop anything else.

