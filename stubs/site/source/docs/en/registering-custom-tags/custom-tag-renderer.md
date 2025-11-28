---
extends: _core._layouts.documentation
section: content
title: CustomTagRenderer
description: CustomTagRenderer
---

# CustomTagRenderer

This component renders `CustomTagNode` instances to HTML within the League CommonMark pipeline. It either delegates to a **per‑tag renderer** (if provided) or falls back to a default wrapper element with rendered children.

> Note: In some repositories the file may be named `CustomTagRender.php` while the class is `CustomTagRenderer`. The class documented here is the renderer used by CommonMark via `NodeRendererInterface`.

---

## Location & signature
- Namespace: `App\Helpers\CommonMark`
- Class: `CustomTagRenderer`
- Implements: `League\CommonMark\Renderer\NodeRendererInterface`

```php
final readonly class CustomTagRenderer implements NodeRendererInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): mixed
    {
        if (!$node instanceof CustomTagNode) return '';
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

---

## Rendering flow
1. **Type check**: non‑`CustomTagNode` ➜ return empty string.
2. **Spec lookup**: retrieves the tag’s `CustomTagSpec` from `CustomTagRegistry` by `type()`.
3. **Custom renderer?**
    - If the spec exposes a closure at `$spec->renderer`, it is invoked as:
      ```php
      fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed
      ```
      The closure should return an `HtmlElement` or a string.
4. **Default rendering**
    - If no per‑tag renderer is provided, a default `HtmlElement` is returned:
        - **Tag name**: `$spec->htmlTag` or `'div'` if missing.
        - **Attributes**: `$node->getAttrs()` (already merged/filtered earlier).
        - **Children HTML**: `$childRenderer->renderNodes($node->children())`.

---

## Per‑tag renderer: how to write one
A per‑tag renderer gives you full control over the output. Recommended pattern:

```php
public function renderer(): ?callable
{
    return function (CustomTagNode $node, ChildNodeRendererInterface $children): HtmlElement {
        $attrs = $node->getAttrs();
        $meta  = $node->getMeta();            // e.g., ['openMatch' => ..., 'attrStr' => ...]
        $inner = $children->renderNodes($node->children());

        // Read attributes safely; prefer HtmlElement for auto‑escaping
        $classes = $attrs['class'] ?? '';
        $caption = $attrs['caption'] ?? '';

        return new HtmlElement('figure', ['class' => $classes],
            $inner . ($caption !== '' ? new HtmlElement('figcaption', [], $caption) : '')
        );
    };
}
```

### Accessing data
- **Attributes**: `$node->getAttrs()` — merged defaults + inline, already normalized by `Attrs` and optionally filtered by `attrsFilter($attrs, $meta)`.
- **Meta**: `$node->getMeta()` — includes `openMatch` (regex captures) and `attrStr` (raw attribute segment).
- **Children**: `$children->renderNodes($node->children())` — the inner Markdown as HTML.

> Prefer `HtmlElement` over manual string concatenation; it handles attribute escaping for you.

---

## Division of responsibilities
- **Where to normalize/validate attributes?** In the tag’s `attrsFilter($attrs, $meta)`, not in the renderer. Keep renderers focused on structure.
- **Where are attributes merged?** During block start (see `UniversalBlockParser`) and prior to rendering.
- **Who chooses the wrapper tag?** The spec’s `htmlTag` for the default path; per‑tag renderers may ignore it and output any structure they need.

---

## Edge cases & behavior
- **Unknown spec**: If the registry returns `null` (mis‑configuration), the fallback tag defaults to `'div'` with whatever attributes are on the node.
- **Empty content**: Children may be empty; default path still returns the wrapper element.
- **Return type**: Return an `HtmlElement` or a string. Avoid returning raw unescaped user input.

---

## Testing checklist
- Default path: with no per‑tag renderer, confirm wrapper = `spec.htmlTag` (or `div`) and attributes are present.
- Custom path: ensure the closure is invoked; verify it uses `$children->renderNodes(...)` and respects attributes.
- Attributes: double‑check that classes are merged/deduped upstream; renderer should not re‑merge.
- Meta usage: if your renderer relies on named captures from `openRegex`, assert they appear in `$node->getMeta()['openMatch']`.

---

## Migration note (if you saw the old signature)
Earlier drafts sometimes described `renderer()` as `fn(string $innerHtml, array $attrs): string`. The current implementation passes the **node** and the **child renderer** instead. To adapt:

- Get inner HTML via `$children->renderNodes($node->children())`.
- Get merged attributes via `$node->getAttrs()`.
- Use `$node->getMeta()` to read regex captures or the raw attribute string if needed.


