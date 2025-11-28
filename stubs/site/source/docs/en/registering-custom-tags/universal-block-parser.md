---
extends: _core._layouts.documentation
section: content
title: UniversalBlockParser
description: UniversalBlockParser
---

# UniversalBlockParser

This parser is responsible for recognizing custom **block** tags (`!type` … `!endtype`), creating AST nodes, and delegating inner content parsing to the CommonMark pipeline.

---

## Location & signature
- Namespace: `App\Helpers\CommonMark`
- Class: `UniversalBlockParser`

---

## Role in the pipeline
- Installed by `CustomTagExtension`.
- Runs during block start detection for each line.
- For every registered `CustomTagSpec`, it tries to match the **open** regex on the current line. On success, it:
    1) Builds a `CustomTagNode` with merged attributes and meta;
    2) Returns a container **continue parser** which:
        - Accepts any child blocks/inline content;
        - Finishes when the **close** regex is matched (or immediately for single‑line tags).

---

## Constructor
```php
public function __construct(private CustomTagRegistry $registry) {}
```
Takes the **registry** which provides a list of `CustomTagSpec` instances: `{ type, openRegex, closeRegex, allowNestingSame, baseAttrs, attrsFilter }`.

---

## `tryStart()` — step by step
```php
public function tryStart(Cursor $cursor, MarkdownParserStateInterface $state): ?BlockStart
```

1. **Read current line**
   ```php
   $line = $cursor->getLine();
   ```
2. **Try each spec** in registry, match the open marker
   ```php
   if (!preg_match($spec->openRegex, $line, $m)) continue;
   ```
3. **Nesting rule** for same‑type blocks
    - If the active block is a `CustomTagNode` with the same `type` and `allowNestingSame === false`, the new start is **suppressed**:
   ```php
   return BlockStart::none();
   ```
4. **Consume the whole line**
   ```php
   $cursor->advanceToEnd();
   ```
5. **Parse attributes** from the named group `attrs` and **merge** with spec defaults
   ```php
   $attrStr   = $m['attrs'] ?? '';
   $userAttrs = Attrs::parseOpenLine($attrStr);
   $attrs     = Attrs::merge($spec->baseAttrs, $userAttrs);
   ```
6. **Create node** with meta
   ```php
   $node = new CustomTagNode($spec->type, $attrs, [
       'openMatch' => $m,
       'attrStr'   => $attrStr,
   ]);
   ```
7. **Early attribute filtering (optional)**
    - If the spec provides `attrsFilter`, it’s applied **immediately** to the node’s attributes:
   ```php
   if ($spec->attrsFilter instanceof \Closure) {
       $node->setAttrs(($spec->attrsFilter)($node->getAttrs(), $node->getMeta()));
   }
   ```
   **Signature note:** `attrsFilter` is called with **two arguments** `($attrs, $meta)` where `meta` contains at least `openMatch` and `attrStr`.
8. **Return a container parser** (anonymous `AbstractBlockContinueParser`) which:
    - `getBlock()` returns the `CustomTagNode`.
    - `isContainer()` is `true` — inner content is parsed **as normal Markdown**.
    - `canContain()` returns `true` — accepts any children.
    - `canHaveLazyContinuationLines()` is `false` — explicit lines only.
    - `tryContinue()` finishes when the **close** regex matches the current line (and consumes the line); otherwise continues.

---

## Closing behavior
```php
if ($this->spec->closeRegex === null) {
    return BlockContinue::finished(); // single-line tag
}

if (preg_match($this->spec->closeRegex, $line)) {
    $cursor->advanceToEnd();
    return BlockContinue::finished();
}
```
- **Single‑line tags**: if `closeRegex` is `null`, the block ends immediately after the open line (no inner content).
- **Standard blocks**: the block remains open until a line matches `closeRegex` (`!end<type>` by default). The close line is consumed and not emitted as content.

---

## Nesting semantics
- If `allowNestingSame` is **false** and the active block is the **same type**, an inner open is **ignored** (treated as normal text until the outer close).
- Different tag types may still nest freely, since `canContain()` returns `true`.

---

## Attributes and meta
- Attributes are parsed from the open line via `Attrs::parseOpenLine()` supporting:
    - Key–value pairs: `key="value"`, `key:'value'`, unquoted tokens
    - Shorthands: `.class` appends to `class`, `#id` sets `id`
    - Unicode spaces and smart quotes are normalized; classes are deduplicated
- Merging is performed with `Attrs::merge($spec->baseAttrs, $userAttrs)`.
- **Meta** stored on the node:
    - `openMatch` — full regex match array for `openRegex`
    - `attrStr` — raw attribute substring from the opening line

> Implement your `attrsFilter` as `fn(array $attrs, array $meta): array` to leverage this information (e.g., derive attributes from capture groups).

---

## Container responsibilities
The inner anonymous continue parser:
- **does not** collect text lines itself (`addLine()` is a no‑op);
- **delegates** inner content parsing to the standard block/inline parsers since it’s marked as a container accepting any children;
- **signals close** via `tryContinue()` when `closeRegex` matches.

---

## Edge cases & behavior
- **Unclosed block at EOF**: CommonMark will close the container at end of document; no explicit cleanup is needed.
- **Extra `!end<type>`**: a close line without a matching open will not be claimed by this parser (it only reacts when already inside a tag); it will be treated as plain text by other parsers.
- **Mixed indentation / leading spaces**: open/close regexes are built to tolerate leading whitespace via `^\s*` (see `BaseTag::openRegex()`/`closeRegex()`).
- **Suppressed same‑type nesting**: when `allowNestingSame=false`, authors will see the inner `!<type>` rendered as text; document this in authoring guidelines if needed.

---

## Performance notes
- The parser iterates specs and executes a single anchored `preg_match` per spec for the **current line**.
- Keep `openRegex` **anchored** at start (`^`) and avoid overly expensive subpatterns.
- The number of specs is typically small; if it grows large, consider grouping common prefixes or precomputing a faster dispatch in the registry.

---

## Testing suggestions
Create fixtures covering:
1. Simple block: open → text → close.
2. Attribute parsing: quoted/unquoted, `.class`, `#id`.
3. Nested different tags vs suppressed same‑type nesting.
4. Single‑line tags with `closeRegex = null`.
5. Unclosed block at EOF.
6. Lines that resemble markers inside code fences (should be parsed correctly by CommonMark since inner content is delegated).

---

## Troubleshooting
- *Open not detected*: verify `openRegex` for the tag/spec; ensure the line starts with `!type` and there’s no trailing characters after attributes.
- *Close not detected*: confirm `closeRegex` and that the close line has no extra content.
- *Attributes missing*: check the named capture `(?<attrs>...)` in the **open** regex and your `Attrs` logic.
- *Unexpected inner text*: you might be hitting same‑type nesting suppression; set `allowNestingSame()` to `true` in your tag.

---

## Example
**Markdown**
```md
!example class:"mb-4 border" .demo
Inner *markdown* content.
!endexample
```

**Result** (simplified)
```html
<div class="example overflow-hidden radius-1/2 overflow-x-auto mb-4 border demo">
  <p>Inner <em>markdown</em> content.</p>
</div>
```
