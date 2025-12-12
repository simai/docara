---
extends: _core._layouts.documentation
section: content
title: Build Integration & Locales Merge
description: Build Integration & Locales Merge
---

# Build Integration & Locales Merge

How translated locales are injected into the Docara build and how the cache directory ties everything together.

---

## Where the merge happens

In core (`DocaraEventsServiceProvider`), the `beforeBuild` hook reads a generated locales file and merges it into runtime config:

```php
$events->beforeBuild(function (Docara $docara) {
    $locales    = $docara->getConfig('locales');
    $tempConfig = $this->app->path('temp/translations/.config.json'); // must match cache_dir

    if (is_file($tempConfig)) {
        $allLocales    = $locales;
        $tempConfigRaw = json_decode(file_get_contents($tempConfig), true) ?: [];
        $tempLocales   = $tempConfigRaw['locales'] ?? $tempConfigRaw;

        if (is_array($tempLocales)) {
            foreach ($tempLocales as $key => $value) {
                if ($key === 'sha') continue; // skip metadata
                $allLocales[$key] = $value;   // generated locales override or add
            }
            $docara->setConfig('locales', $allLocales);
        }
    }
});
```

**Important:** Path must align with the translator `cache_dir`: `project root` + `cache_dir` + `translations/.config.json`. With defaults that is `temp/translations/.config.json`.

---

## What the translator writes

After a translation run, caches are written under `<cache_dir>/translations/`:

!folders
- {$cache-dir}
   - translations
      -- {$lang}.json (cache: normalized-string-hash → translated text)
      -- hash.json (per-file MD5 to skip unchanged files)
      -- .config.json (locales map for Docara beforeBuild)
!endfolders

`.config.json` is a flat map `code → display name` (from `Symfony\Component\Intl\Languages::getName()`), e.g.:

```json
{
    "en": "English",
    "ru": "Русский"
}
```

If your project expects a richer shape, adjust merge logic or translator output accordingly.

---

## End-to-end build flow

1. **Translate**

    ```bash
    php vendor/bin/docara translate
    ```

    → Populates/updates `temp/translations/*.json` and `.config.json`.

2. **Build the site**

    ```bash
    php vendor/bin/docara build
    ```

    → `beforeBuild` merges `.config.json` into `config('locales')` so new languages are available in the same build.

3. **Serve (dev)**
    ```bash
    php vendor/bin/docara serve
    ```
    → Restart after a translation run to pick up newly added locales.

---

## CI/CD example

```yaml
steps:
    - run: composer install --no-interaction --prefer-dist
    - run: php vendor/bin/docara translate
    - run: php vendor/bin/docara build
    - persist_to_workspace: public/ # or upload artifacts
```

Notes:

-   Ensure `.env` is present in CI with `AZURE_KEY`, `AZURE_REGION`, `AZURE_ENDPOINT`.
-   Run from project root so `vendor/bin/docara` finds `vendor/autoload.php`.

---

## Keeping paths consistent

-   `translate.config.php` → `'cache_dir' => 'temp/'`
-   Docara merge path → `temp/translations/.config.json`

If you change `cache_dir`, update the merge path in core config or mirror the directory structure; otherwise new locales won’t merge.

---

## Conflict semantics

-   Project `locales` are loaded first, then generated ones overlay.
-   If a locale code exists in both, the generated value wins (allows runtime naming).

---

## Cleaning & re-running

-   To force a full rebuild of translations, delete the cache directory:
    ```bash
    rm -rf temp/translations
    ```
-   Next `php vendor/bin/docara translate` recreates caches and `.config.json`.

---

## Troubleshooting

-   **New language doesn’t show up**: check `temp/translations/.config.json` exists/valid, path matches `cache_dir`, restart `docara serve`.
-   **Locale label looks wrong**: labels come from `Languages::getName($code)`; override during merge if needed.
-   **Stale cache**: remove `translate_<lang>.json` or `temp/translations` and rerun translate.

---
