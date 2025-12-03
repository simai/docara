---
extends: _core._layouts.documentation
section: content
title: Project Structure
description: Project Structure
---

# Project Structure

Now that the custom Markdown tags engine ships inside Docara itself, this page maps the core files that power it, what each piece does, and where you plug in your own tag classes.

---

## Directory tree

Rooted in Docara core at `src/CustomTags`:

<div class="files">
    <div class="folder folder--open">src
        <div class="folder folder--open">CustomTags
  <div class="file">Attrs.php</div>
  <div class="file">BaseTag.php</div>
  <div class="file">CustomTagAdapter.php</div>
  <div class="file">CustomTagNode.php</div>
  <div class="file">CustomTagRegistry.php</div>
  <div class="file">CustomTagRenderer.php</div>
  <div class="file">CustomTagsExtension.php</div>
  <div class="file">CustomTagSpec.php</div>
  <div class="file">TagRegistry.php</div>
  <div class="file">UniversalBlockParser.php</div>
  <div class="file">UniversalInlineParser.php</div>
        </div>
        <div class="folder folder--open">Interface
  <div class="file">CustomTagInterface.php</div>
        </div>
        <div class="file">Parser.php</div>
        <div class="folder folder--open">Providers
  <div class="file">CustomTagServiceProvider.php</div>
        </div>
    </div>
</div>

---

## Namespaces & autoload
- Core classes live under `Simai\Docara\CustomTags\*` (plus `Simai\Docara\Parser` and `Simai\Docara\Interface\CustomTagInterface`).
- `CustomTagServiceProvider` expects your tag classes under `App\Helpers\CustomTags\` and loads them by short class name from `config('tags')`.
- Ensure your project composer autoload maps `"App\\": "source/_core"` (or your source dir) and run `composer dump-autoload` after adding/removing tag classes.

---

## High-level architecture

```
Markdown source
   |
CustomTagsExtension (installs)
   |-- UniversalBlockParser --> CustomTagNode (AST)
   |-- UniversalInlineParser (reserved for inline forms)

CustomTagRegistry --> CustomTagSpec (per-tag rules)

CustomTagRenderer --> HTML (uses BaseTag::htmlTag/renderer)

Attrs --> parse/merge attributes
```

---

## Components and responsibilities

### Authoring API
- **Interface/CustomTagInterface.php**
    - The formal contract for a tag: `type()`, `openRegex()`, `closeRegex()`, `htmlTag()`, `baseAttrs()`, `allowNestingSame()`, optional `attrsFilter()` and `renderer()`.
- **CommonMark/BaseTag.php**
    - Default implementation of the interface with sensible behaviors.
    - Extend this for new tags instead of implementing the interface from scratch.
- **Your project (`App\Helpers\CustomTags\*`)**
    - Your tag classes live in your app. Each returns a unique `type()` and may override defaults.

### Registration & discovery
- **CommonMark/TagRegistry.php**
    - Helper/factory that accepts an array of tag instances and produces a read-only registry.
- **CommonMark/CustomTagRegistry.php** (core service)
    - Container-bound service built by `CustomTagServiceProvider` from `config('tags')` and exposed to the CommonMark layer.
- **Config (`config.php`)**
    - `tags` array lists short class names to register (resolved to `App\Helpers\CustomTags\<Short>`).

### Parsing layer
- **CommonMark/CustomTagSpec.php**
    - A compiled, immutable spec per tag: type, open/close regex, nesting rules, wrapper tag, base attrs.
- **CommonMark/UniversalBlockParser.php**
    - Line-based block parser that recognizes `!type` / `!endtype` using specs from the registry, captures inner Markdown, and builds `CustomTagNode`.
- **CommonMark/UniversalInlineParser.php**
    - Reserved for inline patterns/shorthands; kept for symmetry and future use.
- **CommonMark/CustomTagNode.php**
    - AST node carrying the tag type, merged attributes (raw), and child nodes (parsed inner Markdown).
- **CommonMark/CustomTagAdapter.php**
    - Bridge that registers specs, parsers, and renderers into the League CommonMark environment.
- **CommonMark/CustomTagsExtension.php**
    - The CommonMark extension entry point; installs the adapter into the environment.

### Rendering layer
- **CommonMark/CustomTagRenderer.php**
    - Rendering pipeline for `CustomTagNode`:
        1. Parse/normalize inline attributes via **Attrs** and merge with `baseAttrs()`.
        2. Apply per-tag `attrsFilter()` if present.
        3. If the tag provides a `renderer()`, call it with `(innerHtml, attrs)`.
        4. Otherwise, emit `<htmlTag ...attrs>innerHtml</htmlTag>`.

### Utilities
- **CommonMark/Attrs.php**
    - Robust attribute parsing for the open line:
        - Key-value pairs: `key="value"`, `key:'value'`, unquoted tokens.
        - Shorthands: `.class` (append), `#id` (set).
        - Unicode spaces/smart quotes are normalized; classes are concatenated and de-duplicated.
    - Attribute set merging with class de-duplication.

### Parser integration
- **Parser.php**
    - Core replacement for Jigsaw's `FrontMatterParser`.
    - Builds the CommonMark environment and installs `CustomTagsExtension` so tags work during `build`/`serve`.

---

## File interaction (lifecycle)
1. `Parser` builds CommonMark environment and installs `CustomTagsExtension`.
2. `CustomTagsExtension` uses `CustomTagAdapter` to register:
    - `UniversalBlockParser`, `UniversalInlineParser`, and a renderer for `CustomTagNode`.
3. `CustomTagRegistry` supplies `CustomTagSpec` instances derived from registered tag classes.
4. Parsing:
    - `UniversalBlockParser` matches open/close lines, constructs `CustomTagNode` with raw attrs and child nodes.
5. Rendering:
    - `CustomTagRenderer` merges attributes (`Attrs`) and renders via wrapper or per-tag `renderer()`.

---

## Where to add things
- **New tag** -> `App\Helpers\CustomTags/YourTag.php` (extend `BaseTag`), add to `config('tags')`.
- **New parsing behavior** -> `UniversalBlockParser` / `UniversalInlineParser`.
- **Custom rendering logic for a specific tag** -> override `renderer()` in your tag class.
- **Global attribute rules** -> extend logic in `Attrs`.

---

## Conventions
- One class per file; class basename matches filename.
- `type()` must be globally unique across all tags.
- Keep `baseAttrs()` minimal/semantic; let authors add presentation in Markdown.
- Avoid HTML injection: escape values in custom `renderer()` implementations.

---

## Troubleshooting pointers
- **Tag not recognized**: Check `config('tags')`, namespace, and run `composer dump-autoload`.
- **Attributes missing**: Confirm they're on the **open line**; check `Attrs` normalization for quotes/spaces.
- **Wrong wrapper**: Verify `htmlTag()` override; if using `renderer()`, remember it bypasses the default wrapper.
- **Nesting issues**: Adjust `allowNestingSame()` in the tag class.
