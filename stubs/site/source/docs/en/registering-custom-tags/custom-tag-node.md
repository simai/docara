---
extends: _core._layouts.documentation
section: content
title: CustomTagNode & Spec Adapter
description: CustomTagNode & Spec Adapter
---

# CustomTagNode & Spec Adapter

This page documents the **AST node** used to represent a custom tag and the **adapter** that translates a tag class into a runtime `CustomTagSpec` consumed by the parsers/renderers.

---

## CustomTagNode (AST)
**Location:** `App\Helpers\CommonMark\CustomTagNode`

Represents a parsed custom tag block in the CommonMark AST.

### Constructor
```php
public function __construct(
    private string $type,
    private array  $attrs = [],  // merged + filtered attributes
    private array  $meta  = [],  // parser metadata (e.g., openMatch, attrStr)
) {}
```

### Core methods
- `getType(): string` — Returns the tag type (e.g., `example`).
- `isContainer(): bool` — Always `true`; the node can contain any child blocks/inline nodes.
- `getAttrs(): array` — Current attribute map (merged defaults + inline, optionally filtered).
- `setAttrs(array $attrs): void` — Replace the attribute map (used after `attrsFilter`).
- `addClass(string $class): void` — Append one or more classes; whitespace‑split, deduplicate, preserve order for stable diffs.
- `getMeta(): array` — Parser metadata (e.g., `['openMatch' => ..., 'attrStr' => 'class:"mb-4"']`).

> **Tip:** Use `getMeta()['openMatch']` in a per‑tag `attrsFilter($attrs, $meta)` or renderer to derive attributes from named regex captures.

### Typical lifecycle
1. Created by **UniversalBlockParser** when the open line matches a tag’s `openRegex()`.
2. Attributes are parsed via **Attrs** and merged with `baseAttrs()`.
3. Optional per‑tag `attrsFilter($attrs, $meta)` may update `attrs` (via `setAttrs`).
4. Rendered by **CustomTagRenderer** (default wrapper or per‑tag renderer closure).

---

## CustomTagAdapter (Tag → Spec)
**Location:** `App\Helpers\CommonMark\CustomTagAdapter`

Converts a tag class (implementing `CustomTagInterface` / extending `BaseTag`) into an immutable **`CustomTagSpec`** consumed by the parsing/rendering layer.

### API
```php
public static function toSpec(CustomTagInterface $tag): CustomTagSpec
```

### Responsibilities
- **Extract identity & regexes**
    - `type`: from `type()`
    - `openRegex`: from `openRegex()`; must be non‑empty or an exception is thrown.
    - `closeRegex`: from `closeRegex()`; coerced to `null` for single‑line tags.
- **Carry presentation defaults**
    - `htmlTag`: from `htmlTag()` (default wrapper element).
    - `baseAttrs`: from `baseAttrs()`.
- **Behavioral flags**
    - `allowNestingSame`: from `allowNestingSame()`.
- **Customization hooks**
    - `attrsFilter`: from `attrsFilter()`; expected signature `fn(array $attrs, array $meta): array`.
    - `renderer`: from `renderer()`; expected signature `fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed`.

### Error handling
- If `openRegex()` returns an empty value, the adapter throws `InvalidArgumentException` identifying the tag.

---

## How they work together
- During environment setup, each tag class is converted by **CustomTagAdapter** into a `CustomTagSpec` and stored in **CustomTagRegistry**.
- **UniversalBlockParser** consumes specs to:
    - detect opens/closes;
    - create **CustomTagNode** with merged attributes and meta;
    - apply early `attrsFilter($attrs, $meta)`.
- **CustomTagRenderer** uses the spec to either:
    - call a per‑tag `renderer($node, $children)`; or
    - render a default `HtmlElement($spec->htmlTag, $node->getAttrs(), ...)`.

---

## Usage patterns
- **Add derived classes** at render time:
  ```php
  $node->addClass('variant-' . ($meta['openMatch']['variant'] ?? 'default'));
  ```
  Prefer doing this in `attrsFilter()` for clearer separation of concerns.

- **Single‑line tags**: Return an empty/falsey `closeRegex()` from the tag; the adapter will pass `null` to the spec, and the block will close immediately after the opening line.

---

## Pitfalls & recommendations
- Keep `type()` globally unique; specs are keyed by type.
- Ensure `openRegex()` exposes a named `attrs` group if you expect inline attributes.
- Don’t mutate attributes inside renderers unless necessary; use `attrsFilter()` or `addClass()` earlier in the flow.
- If you override `openRegex()`/`closeRegex()`, maintain `^` anchors and the Unicode `u` modifier.

---

## Testing checklist
- Spec creation fails when `openRegex()` is empty.
- Node attributes merge correctly and `addClass()` de‑duplicates.
- `attrsFilter($attrs, $meta)` sees both `openMatch` and `attrStr`.
- Renderer path respects per‑tag renderer vs default wrapper.

