---
extends: _core._layouts.documentation
section: content
title: Content Translation Engine - Configuration
description: Content Translation Engine - Configuration
---

# Configuration

This chapter documents all configuration options for the translation pipeline and how they affect input/output directories, providers, and the Jigsaw build.

---

## Minimal config (example)

```php
<?php
return [
    'source_dir'          => __DIR__ . '/source/',
    'target_lang'         => 'en',          // base locale (source of truth)
    'main'                => __DIR__ . 'configuration.md/', // project root
    'cache_dir'           => 'temp/',

    'frontMatter'         => ['title', 'description'],
    'languages'           => ['ru'],        // target locales to generate
    'output_dir'          => __DIR__ . '/source',
    'preserve_structure'  => true,
];
```

---

## Keys & semantics

### `source_dir` (string, required)
Absolute path to your **content root**. The translator scans locale folders inside it.

**Convention**: docs per locale live under `source/_docs-<locale>` (e.g., `_docs-en`, `_docs-ru`).

### `target_lang` (string, required)
The **base locale** to read from (e.g., `en`). Files in `source/_docs-<target_lang>` are treated as the source of truth.

> Name is historical; think of it as `base_lang`.

### `languages` (string[], required)
List of **target locales** to produce (e.g., `['ru', 'de']`). For each language, the translator writes/updates `source/_docs-<lang>`.

### `output_dir` (string, required)
Root path where localized trees are written. Normally the same as `source_dir` (so folders become siblings like `_docs-en`, `_docs-ru`).

### `preserve_structure` (bool, default: `true`)
When `true`, the directory/file structure under `_docs-<target_lang>` is **mirrored** into each target locale folder.

### `cache_dir` (string, required)
Relative (to `main`) or absolute path for translation caches and runtime config.

**Layout (relative to `main`)**:
```
<cache_dir>/translations/
  translate_<lang>.json   # key→translation cache
  hash.json               # checksum/housekeeping
  .config.json            # generated locales map for Jigsaw
```

### `frontMatter` (string[], optional)
List of **front matter keys** to translate (e.g., `['title','description']`). Only these keys are sent to the provider.

### `main` (string, required)
Project root; used to resolve `cache_dir` and other relative paths.

---

## Environment variables (provider)
The Azure Translator provider expects the following in your `.env`:

```
AZURE_KEY=...
AZURE_REGION=...
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
```

> The CLI (`bin/translate`) loads `.env` before running. Ensure the keys are present in your build environment.

---

## Directory conventions
```
source/
  _docs-en/      # base locale (target_lang)
  _docs-ru/      # generated locale(s)
  ...
```
- **Input**: `source/_docs-<target_lang>`
- **Output**: `source/_docs-<lang>` for each entry in `languages`

**File types translated**
- Markdown/MDX (`.md`), front matter keys listed in `frontMatter`
- Language packs: `.lang.php`, `.settings.php` (mirrored per locale)

> The Markdown translator is **AST‑aware**: only text nodes are translated; code blocks, inline code, links, and custom tags remain intact.

---

## Jigsaw integration (locales discovery)
During `beforeBuild`, if `<cache_dir>/translations/.config.json` exists, it is **merged** into `config('locales')`. This makes newly generated languages available **without** editing project config.

**Example `.config.json` (generated):**
```json
{
  "en": { "name": "English" },
  "ru": { "name": "Русский" }
}
```

Place this file at `temp/translations/.config.json` if `cache_dir` is `temp/` and `main` points to the project root.

---

## Path resolution & tips
- Prefer **absolute** paths in config (`__DIR__`) to avoid CWD issues.
- Keep trailing slashes consistent (as in the example) to avoid duplicate separators when concatenating.
- If you change `cache_dir`, update the Jigsaw bootstrap path which reads `.config.json`.

---

## Multi‑locale examples
### EN → RU, DE
```php
return [
  'target_lang' => 'en',
  'languages'   => ['ru','de'],
  'frontMatter' => ['title','description','summary'],
  // other keys as above
];
```

### RU → EN (base locale is Russian)
```php
return [
  'target_lang' => 'ru',
  'languages'   => ['en'],
  'source_dir'  => __DIR__ . '/source/',
  'output_dir'  => __DIR__ . '/source',
  'cache_dir'   => 'temp/',
];
```

---

## Validation checklist
- `source_dir` exists and contains `_docs-<target_lang>`.
- `output_dir` is writable (or identical to `source_dir`).
- `languages` is non‑empty and does **not** include `target_lang`.
- `.env` has valid Azure credentials.
- Jigsaw `beforeBuild` reads `<cache_dir>/translations/.config.json`.

---

## Common mistakes
- **Mismatched cache path**: you configured `cache_dir` but bootstrap still reads `temp/translations/.config.json`. Fix the bootstrap path or set `cache_dir` back to `temp/`.
- **Confusing `target_lang`**: remember this is the **source** language. Targets go into `languages`.
- **Relative paths + CWD**: use absolute paths with `__DIR__` to avoid surprises when running from CI.

---
