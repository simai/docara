---
extends: _core._layouts.documentation
section: content
title: Build Integration & Locales Merge
description: Build Integration & Locales Merge
---

# Build Integration & Locales Merge

This chapter explains how translated locales are **injected into the Jigsaw build** and how the cache directory ties everything together.

---

## Where the merge happens
In `bootstrap.php`, during Jigsaw’s `beforeBuild` hook we read a generated locales file and merge it into the runtime config:

```php
$events->beforeBuild(function ($jigsaw) use ($container) {
    $locales   = $jigsaw->getConfig('locales');
    $tempConfig = __DIR__ . '/temp/translations/.config.json'; // must match cache_dir

    if (is_file($tempConfig)) {
        $allLocales = [];
        $tempConfigJson = json_decode(file_get_contents($tempConfig), true) ?: [];

        foreach ($locales as $key => $locale) {
            $allLocales[$key] = $locale; // project locales
        }
        foreach ($tempConfigJson as $key => $value) {
            $allLocales[$key] = $value;  // generated locales override or add
        }

        $jigsaw->setConfig('locales', $allLocales);
    }
});
```

**Important:** The path must align with your translator config:
- `main` (project root) + `cache_dir` + `translations/.config.json`
- With the default config, that is `temp/translations/.config.json`.

---

## What the translator writes
When a translation run finishes, `saveCache()` writes three files under `<cache_dir>/translations/`:

```
translate_<lang>.json  # cache: normalized-string-hash → translated text
hash.json              # per-file MD5 to skip unchanged files next run
.config.json           # locales map consumed by Jigsaw beforeBuild
```

### `.config.json` format
The translator writes a **flat map**: `code → display name`, using `Symfony\Component\Intl\Languages::getName()`
```json
{
  "en": "English",
  "ru": "Русский"
}
```
> If your project expects a richer shape (e.g., `{ "en": { "name": "English" } }`), adjust either the bootstrap merge or `saveCache()` accordingly. By default, a simple string works as a locale display name.

---

## End‑to‑end build flow
1. **Translate**
   ```bash
   composer translate
   ```
   – Populates/updates `temp/translations/*.json` and `.config.json` with any new languages.

2. **Build the site**
   ```bash
   vendor/bin/jigsaw build
   ```
   – `beforeBuild` merges `.config.json` into `config('locales')` so the new languages become available **in the same build**.

3. **Serve (dev)**
   ```bash
   vendor/bin/jigsaw serve
   ```
   – Restart `serve` after a translation run to pick up newly added locales.

---

## CI/CD example
```yaml
steps:
  - run: composer install --no-interaction --prefer-dist
  - run: php bin/translate           # or composer translate
  - run: vendor/bin/jigsaw build
  - persist_to_workspace: public/    # or upload artifacts
```

**Notes**
- Ensure `.env` is present in CI with `AZURE_KEY`, `AZURE_REGION`, `AZURE_ENDPOINT`.
- Make sure the working directory is the project root (so `bin/translate` can find `vendor/autoload.php`).

---

## Keeping paths consistent
- `translate.config.php` → `'cache_dir' => 'temp/'`
- `bootstrap.php` → `__DIR__ . '/temp/translations/.config.json'`

If you change `cache_dir`, update the bootstrap path accordingly. A mismatch will result in new locales **not** being merged.

---

## Conflict semantics
- The merge copies project `locales` **first**, then overlays generated ones from `.config.json`.
- If a locale code exists in both, the **generated value wins** for that key. This is intended to allow runtime naming sourced from the translator.

---

## Cleaning & re‑running
- To force a full rebuild of translations, delete the cache directory:
  ```bash
  rm -rf temp/translations
  ```
- The next `composer translate` will recreate caches and `.config.json` from scratch.

---

## Troubleshooting
- **New language doesn’t show up**
    - Verify `temp/translations/.config.json` exists and is valid JSON.
    - Confirm the bootstrap path matches `cache_dir`.
    - Restart `jigsaw serve`.

- **Locale label looks wrong**
    - The translator writes display names via `Languages::getName($code)` (then `mb_ucfirst`). If you need custom labels, post‑process `.config.json` or override during merge.

- **Build fails on missing autoload**
    - Ensure `bin/translate` requires `vendor/autoload.php` via an **absolute path** and `chdir($root)` before running.

- **Stale cache**
    - If content changed but translations didn’t, remove the per‑language cache file `translate_<lang>.json` or the entire `temp/translations` folder.

---

## Checklist
- [ ] `cache_dir` in `translate.config.php` matches the path in `bootstrap.php`.
- [ ] `.env` with Azure credentials is available.
- [ ] `composer translate` runs before `jigsaw build` in CI.
- [ ] Restart `serve` after adding languages.
- [ ] `.config.json` contains all expected locale codes.

