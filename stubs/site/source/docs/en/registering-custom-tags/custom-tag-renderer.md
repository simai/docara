---
extends: _core._layouts.documentation
section: content
title: CustomTagRenderer
description: CustomTagRenderer
---

# CustomTagRenderer

This component renders `CustomTagNode` instances to HTML inside the League CommonMark pipeline. It either delegates to a per-tag renderer (if provided) or falls back to a default wrapper element with rendered children. It lives in Docara core.

---

## Location & signature
- Namespace: `Simai\Docara\CustomTags`
- Class: `CustomTagRenderer`
- Implements: `League\CommonMark\Renderer\NodeRendererInterface`

```php
final readonly class CustomTagRenderer implements NodeRendererInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): mixed
    {
        if (! $node instanceof CustomTagNode) {
            return '';
        }
        $spec = $this->registry->get($node->getType());

        if ($spec?->renderer instanceof \Closure) {
            return ($spec->renderer)($node, $childRenderer);
        }

        return new HtmlElement(
            $spec?->htmlTag ?? 'div',
            $node->getAttrs(),
            $childRenderer->renderNodes($node->children())
        );
    }
}
```

> Installed by `CustomTagsExtension` alongside `UniversalBlockParser`.

---

## Rendering flow
1. **Type check**: if the node is not `CustomTagNode`, return empty string.
2. **Spec lookup**: pull the tag's `CustomTagSpec` from `CustomTagRegistry` by `type()`.
3. **Custom renderer?**
   - If `$spec->renderer` is a closure, it is invoked as:
   ```php
   fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed
   ```
   - The closure should return an `HtmlElement` or a string.
4. **Default rendering**
   - If no per-tag renderer exists, return `HtmlElement`:
     - **Tag name**: `$spec->htmlTag` or `'div'` if missing.
     - **Attributes**: `$node->getAttrs()` (already merged/filtered).
     - **Children HTML**: `$childRenderer->renderNodes($node->children())`.

---

## Per-tag renderer: how to write one
A per-tag renderer gives full control over the output. Recommended pattern:

```php
public function renderer(): ?callable
{
    return function (CustomTagNode $node, ChildNodeRendererInterface $children): HtmlElement {
        $attrs = $node->getAttrs();
        $meta  = $node->getMeta(); // e.g., ['openMatch' => ..., 'attrStr' => ...]
        $inner = $children->renderNodes($node->children());

        $classes = $attrs['class'] ?? '';
        $caption = $attrs['caption'] ?? '';

        return new HtmlElement(
            'figure',
            ['class' => $classes],
            $inner . ($caption !== '' ? new HtmlElement('figcaption', [], $caption) : '')
        );
    };
}
```

### Accessing data
- **Attributes**: `$node->getAttrs()` - merged defaults + inline, normalized and optionally filtered by `attrsFilter($attrs, $meta)`.
- **Meta**: `$node->getMeta()` - includes `openMatch` (regex captures) and `attrStr` (raw attribute segment).
- **Children**: `$children->renderNodes($node->children())` - the inner Markdown as HTML.

> Prefer `HtmlElement` over manual string concatenation; it handles attribute escaping.

---

## Division of responsibilities
- Normalize/validate attributes in the tag's `attrsFilter($attrs, $meta)`, not in the renderer.
- Attributes are merged during block start (see `UniversalBlockParser`) before rendering.
- Default wrapper tag comes from `spec->htmlTag`; per-tag renderers may output any structure they need.

---

## Edge cases & behavior
- **Unknown spec**: if the registry returns `null`, the fallback tag defaults to `'div'` with whatever attributes are on the node.
- **Empty content**: children may be empty; default path still returns the wrapper element.
- **Return type**: return an `HtmlElement` or a string; avoid emitting unescaped user input.

---

## Testing checklist
- Default path: with no per-tag renderer, confirm wrapper = `spec->htmlTag` (or `div`) and attributes are present.
- Custom path: ensure the closure is invoked; verify it uses `$children->renderNodes(...)` and respects attributes.
- Attributes: double-check classes are merged/deduped upstream; renderer should not re-merge.
- Meta usage: if your renderer relies on named captures from `openRegex`, assert they appear in `$node->getMeta()['openMatch']`.

---

## Migration note (old signature)
Earlier drafts described `renderer()` as `fn(string $innerHtml, array $attrs): string`. The current implementation passes the **node** and the **child renderer** instead. To adapt:

- Get inner HTML via `$children->renderNodes($node->children())`.
- Get merged attributes via `$node->getAttrs()`.
- Use `$node->getMeta()` to read regex captures or the raw attribute string if needed.
