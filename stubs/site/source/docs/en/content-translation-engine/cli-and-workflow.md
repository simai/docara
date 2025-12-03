---
extends: _core._layouts.documentation
section: content
title: Content Translation Engine - CLI & Workflow
description: Content Translation Engine - CLI & Workflow
---

# CLI & Workflow

This chapter explains how to run translations from the command line, what happens step-by-step, and how caches make subsequent runs incremental.

---

## Prerequisites

- **Composer** installed and vendor deps present.
- **.env** has Azure Translator credentials:

    ```dotenv
    AZURE_KEY=...
    AZURE_REGION=...
    AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
    ```

- **Configuration** file (e.g., `translate.config.php`) is filled out (see the Configuration chapter).

---

## Entry points

### Docara CLI

Run the bundled command directly:

```bash
php vendor/bin/docara translate
```

> Autoloading stays in the CLI; you don't need to require `vendor/autoload.php` yourself.

### Optional Composer script

If you want a shorter alias, add this to `composer.json`:

```json
"scripts": {
  "translate": "php vendor/bin/docara translate"
}
```

Then run `composer translate`.

---

## What happens when you run it

1) **Boot**: load `.env`, read `translate.config.php` and project `config.php`.
2) **Build CommonMark env**: install **Custom Tags** extension to parse Markdown safely.
3) **Scan source**: locate the base tree `source/_docs-<target_lang>`.
4) **Plan targets**: for each `lang` in `languages`, ensure/prepare `source/_docs-<lang>`.
5) **Collect strings**:
   - Markdown: traverse AST, collect Text nodes (code blocks/inline code/tags remain intact).
   - Front matter: collect only keys listed in `frontMatter`.
   - Language packs: mirror `.lang.php` / `.settings.php`.
6) **Cache check**: normalize → hash → lookup in `<cache_dir>/translations/translate_<lang>.json`.
7) **Batch & throttle**: group untranslated strings (~9k chars/batch) and send to Azure; respect chars-per-minute with jitter.
8) **Apply translations**: replace bottom-up by line ranges to avoid shifting positions.
9) **Write output**: update/create files under `source/_docs-<lang>`.
10) **Persist caches**: update `translate_<lang>.json`, `hash.json`, and `.config.json` (locales map).

---

## Incremental behavior

- Cache ensures **idempotency**: unchanged strings are skipped.
- Re-running after small edits only translates **new/changed** text.
- To force retranslate a specific string, remove its entry from `<cache_dir>/translations/translate_<lang>.json` (or delete the file for a clean slate).

---

## Output & cache layout

Relative to project root (`main`):

<div class="files">
    <div class="folder folder--open">source
        <div class="folder folder--open">_docs-&lt;target_lang&gt; (input)</div>
        <div class="folder">_docs-&lt;lang&gt; (outputs per target)</div>
    </div>
    <div class="folder folder--open">&lt;cache_dir&gt;
        <div class="folder folder--open">translations
            <div class="file">translate_&lt;lang&gt;.json (key→translated string)</div>
            <div class="file">hash.json (bookkeeping)</div>
            <div class="file">.config.json (locales map, read by Docara beforeBuild)</div>
        </div>
    </div>
</div>

---

## Docara build integration

During `beforeBuild`, if `<cache_dir>/translations/.config.json` exists, it merges into `config('locales')` so new languages appear in the same build run.

---

## Logs & exit codes

- CLI emits summary per language (discovered strings, translated count, reused from cache).
- Non-zero exit means unrecoverable error (invalid config, provider error, I/O issues).

### Provider errors (Azure)

- Check HTTP status/body; retry 429/5xx.
- If a batch fails, report which file/chunk caused it and continue when safe.

---

## Common workflows

### First full pass

```bash
php vendor/bin/docara translate
```

Generates `_docs-ru`, fills caches, writes `.config.json`.

### After content edits

```bash
php vendor/bin/docara translate
```

Only new/changed strings are sent; everything else comes from cache.

### Add a new language

1. Add it to `languages` in `translate.config.php`.
2. Run `php vendor/bin/docara translate`.
3. Docara picks it up via `.config.json` during build.

---

## Tips

- Keep `cache_dir` aligned with the path used in core `bootstrap.php` for `.config.json`.
- Use **absolute paths** in config to avoid CWD issues in CI.
- Watch chars/minute limits if you bulk-edit many files.

---

## Troubleshooting

- **No new locale in the site**: ensure `.config.json` is under `<cache_dir>/translations/` and the bootstrap merges it.
- **Markup broken**: verify only text nodes are replaced; check for manual post-processing that might alter HTML.
- **Attributes translated**: confirm you aren’t translating inside tag attributes; only text nodes should be collected.
- **Autoload errors**: run from the project root so `php vendor/bin/docara translate` finds `vendor/autoload.php`.

---
