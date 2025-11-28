---
extends: _core._layouts.documentation
section: content
title: Content Translation Engine - Cli & Workflow
description: Content Translation Engine - Cli & Workflow
---

# CLI & Workflow

This chapter explains how to run translations from the command line, what happens step‑by‑step, and how caches make subsequent runs incremental.

---

## Prerequisites
- **Composer** is installed and vendor deps are present.
- **.env** has Azure Translator credentials:

  ```
  AZURE_KEY=...
  AZURE_REGION=...
  AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
  ```
- **Configuration** file (e.g., `translate.config.php`) is filled out (see the *Configuration* chapter).

---

## Entry points
### Composer script
```json
"scripts": {
  "translate": "php bin/translate"
}
```
Run:
```bash
composer translate
```

### `bin/translate`
Recommended structure:
```php
#!/usr/bin/env php
<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
chdir($root);

use App\Helpers\Translate;
exit((new Translate())->run($argv));
```
> Autoloading lives in the **entrypoint**; classes don’t `require 'vendor/autoload.php'` themselves.

---

## What happens when you run it
1. **Boot**: load `.env`, read `translate.config.php` and project `config.php`.
2. **Build CommonMark env**: install **Custom Tags** extension to parse Markdown safely.
3. **Scan source**: locate the base tree `source/_docs-<target_lang>`.
4. **Plan targets**: for each `lang` in `languages`, ensure/prepare `source/_docs-<lang>`.
5. **Collect strings**:
    - Markdown: traverse AST, collect **Text** nodes (code blocks/inline code/tags remain intact).
    - Front matter: collect only keys listed in `frontMatter`.
    - Language packs: mirror `.lang.php` / `.settings.php`.
6. **Cache check**: normalize → hash → lookup in `temp/translations/translate_<lang>.json`.
7. **Batch & throttle**: group untranslated strings (~9k chars/batch) and send to Azure; respect chars‑per‑minute with jitter.
8. **Apply translations**: replace **bottom‑up by line ranges** to avoid shifting positions.
9. **Write output**: update/create files under `source/_docs-<lang>`.
10. **Persist caches**: update `translate_<lang>.json`, `hash.json`, and `.config.json` (locales map).

---

## Incremental behavior
- The cache ensures **idempotency**: unchanged strings are skipped.
- Re‑running the command after small edits only translates **new/changed** text.
- To **force retranslate** a specific string, remove its entry from `temp/translations/translate_<lang>.json` (or delete the file for a clean slate).

---

## Output & cache layout
Relative to `main` (project root):
```
source/
  _docs-<target_lang>/   # input (base locale)
  _docs-<lang>/          # outputs per target language

<cache_dir>/translations/
  translate_<lang>.json  # key → translated string
  hash.json              # bookkeeping
  .config.json           # locales map (consumed by Jigsaw beforeBuild)
```

---

## Jigsaw build integration
In `bootstrap.php` (beforeBuild):
- If `<cache_dir>/translations/.config.json` exists (e.g., `temp/translations/.config.json`), merge it into `config('locales')` so new languages appear in the **same** build run.

---

## Logs & exit codes
- The CLI should emit summary lines per language (discovered strings, translated count, reused from cache).
- Non‑zero exit code indicates an unrecoverable error (invalid config, provider error, I/O issues).

### Provider errors (Azure)
- Check HTTP status and body; implement retries for 429/5xx.
- If a batch fails, the tool should report which file/chunk caused it and continue with others when safe.

---

## Common workflows
### First full pass
```bash
composer translate
```
Generates `_docs-ru`, fills caches, and writes `.config.json`.

### After content edits
```bash
composer translate
```
Only new/changed strings are sent; everything else comes from cache.

### Add a new language
1) Add it to `languages` in `translate.config.php`.
2) Run `composer translate`.
3) Jigsaw picks it up via `.config.json` during build.

---

## Tips
- Keep `cache_dir` consistent with the path used in `bootstrap.php` for `.config.json`.
- Use **absolute paths** in config to avoid CWD issues in CI.
- Keep an eye on chars/minute limits if you bulk‑edit many files.

---

## Troubleshooting
- **No new locale in the site**: ensure `.config.json` is under `<cache_dir>/translations/` and the bootstrap merges it.
- **Markup broken**: verify only text nodes are replaced; check for manual post‑processing that might alter HTML.
- **Attributes translated**: confirm you aren’t translating inside tag attributes; only text nodes should be collected.
- **Autoload errors**: ensure `bin/translate` requires `vendor/autoload.php` via an **absolute path** and calls `chdir($root)`.

