---
extends: _core._layouts.documentation
section: content
title: Content Translation Engine
description: Content Translation Engine
---

# Content Translation Engine

This section introduces our **content translation pipeline**: a Markdown‑aware system that translates documentation into multiple locales while preserving structure, custom tags, and front matter.

---

## What it does
- Translates source docs from a **base locale** (configured as `target_lang`, e.g., `en`) into one or more **target languages** (e.g., `ru`).
- Preserves Markdown formatting, code blocks, and **custom Markdown tags** by translating only **text nodes** in the CommonMark AST.
- Keeps front matter keys (like `title`, `description`) in sync across locales.
- Caches translations and merges locales into the Jigsaw build automatically.

---

## High‑level flow
```
Author Markdown (base locale)
        │
        ▼
bin/translate  (CLI) ──► Translator (Azure) ──► Cache (temp/translations/*.json)
        │                          │
        │                          └─ Rate limiting & batching
        ▼
Localized Markdown trees (source/_docs-<lang>)
        │
        ▼
Jigsaw build ── beforeBuild merges temp/translations/.config.json into config('locales')
        │
        ▼
Multi‑locale site output
```

---

## Key integration points
- **Composer script**: `"translate": "php bin/translate"` — runs the CLI translator.
- **Jigsaw bootstrap**: in `beforeBuild`, if `temp/translations/.config.json` exists, its locales are **merged** into `config('locales')`. This makes freshly translated languages available to the current build without changing project config.

---

## Markdown‑aware by design
- We build a CommonMark **Environment** and install our **Custom Tags extension** before translating.
- Only **textual segments** (AST `Text` nodes) are sent to the provider; code fences, inline code, links, and custom tags remain intact.
- Replacement is applied **bottom‑up** by source line ranges to avoid shifting positions while editing the document.

---

## Caching & idempotency
- Every source string is normalized and hashed; translations are cached under `temp/translations/translate_<lang>.json`.
- A `hash.json` and a human‑readable `.config.json` (locales listing) are also written for bookkeeping.
- Re‑runs are **incremental**: already translated strings are skipped.

---

## Rate limiting & batching
- Requests are chunked (≈ 9k chars per batch) and throttled by a **chars‑per‑minute** budget with jitter, minimizing provider throttling.

---

## What gets translated
- **Markdown bodies**: only textual nodes, preserving formatting.
- **Front matter**: selected keys (e.g., `title`, `description`).
- **Language packs**: `.lang.php` and `.settings.php` files are supported and mirrored per target locale.

---

## Configuration (quick look)
The root config (e.g., `translate.config.php`) controls input/output paths and languages:

```php
return [
    'source_dir'          => __DIR__ . '/source/',
    'target_lang'         => 'en',          // base locale (source of truth)
    'main'                => __DIR__ . 'content-translation-engine.md/', // project root
    'cache_dir'           => 'temp/',

    'frontMatter'         => ['title', 'description'],
    'languages'           => ['ru'],        // target locales to produce
    'output_dir'          => __DIR__ . '/source',
    'preserve_structure'  => true,
];
```
> Detailed options are covered in the next chapter (**Configuration**).

---

## Responsibilities at a glance
- **CLI Entry (bin/translate)**: bootstraps Composer autoload, invokes the translator.
- **Translator**: loads env/keys, builds CommonMark env (with Custom Tags), scans source trees, translates strings via provider, writes localized trees & caches.
- **Provider (Azure)**: actual machine translation; we handle batching, throttling, and error reporting.
- **Jigsaw**: merges runtime locales from `.config.json` before the build to make new languages visible.

---

## Safety & correctness principles
- **Preserve structure**: never translate tag names, code, or markup.
- **Deterministic merges**: classes and attributes in custom tags are left untouched; only text content changes.
- **Fail‑loud** on configuration errors; **cache** aggressively to avoid duplicate costs.

---

