---
extends: _core._layouts.documentation
section: content
title: Front Matter & PHP Language Packs
description: Front Matter & PHP Language Packs
---

# Front Matter & PHP Language Packs 

This chapter explains how **front matter** and PHP‑based **language packs** (`.lang.php`, `.settings.php`) are translated and written back, using the actual implementation from `App\Helpers\Translate`.

---

## Where this logic runs
- **Entry point (per file)**: `translateFiles()`
- **Helpers**: `frontMatterParser()`, `translateFromMatter()`, `translateLangFiles()`, `generateSettingsTranslate()`, `makeContent()`, `setByPath()`
- **I/O**: Symfony YAML for front matter; `var_export()` for PHP arrays

---

## Front matter translation
Front matter is parsed and translated before the Markdown body.

### Parsing
```php
private function frontMatterParser($originalMarkdown): array
{
    $parser   = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    $document = $parser->parse($originalMarkdown);
    $front    = $document->getFrontMatter();
    $content  = $document->getContent();
    return [$front, $content];
}
```

### Selecting keys to translate
Only keys configured under `frontMatter` are considered, and only if the value contains letters (`/\p{L}/u`). Cached entries are reused.
```php
private function translateFromMatter(array $frontMatter, string $lang): array
{
    if (empty($this->config['frontMatter']) || !is_array($this->config['frontMatter'])) {
        return $frontMatter;
    }

    [$cachedKeys, $frontMatter] = $this->checkCached($frontMatter, $lang);
    $items = $keys = [];
    foreach ($frontMatter as $k => $v) {
        if (!in_array($k, $cachedKeys, true)
            && in_array($k, $this->config['frontMatter'], true)
            && is_string($v)
            && preg_match('/\p{L}/u', $v)) {
            $keys[]  = $k;
            $items[] = ['Text' => $v];
        }
    }
    return $this->makeContent($items, $frontMatter, $lang, $keys);
}
```

### Writing back (Markdown flow)
Inside `translateFiles()` for `.md` files:
```php
[$front, $original] = $this->frontMatterParser($content);
$frontTranslated    = $this->translateFromMatter($front, $lang);
$bodyTranslated     = $this->generateTranslateContent($original, $lang);
$yamlBlock          = "---\n" . Yaml::dump($frontTranslated) . "---\n\n";
$translated         = $yamlBlock + $bodyTranslated; // concatenation
```
> The YAML dump preserves arrays/scalars and ensures valid front matter.

---

## PHP language packs (`.lang.php`)
Language pack files return associative arrays of UI strings. They are **loaded**, translated **per value**, and written back.

### Loading & caching
```php
$data = include $filePathName; // returns array
[$cachedKeys, $data] = $this->checkCached($data, $lang);
```

### Selecting values
Only **string** values that contain letters are translated; keys in `$cachedKeys` are kept as‑is.
```php
$items = $keys = [];
foreach ($data as $k => $v) {
    if (!in_array($k, $cachedKeys, true) && is_string($v) && preg_match('/\p{L}/u', $v)) {
        $keys[]  = $k;
        $items[] = ['Text' => $v];
    }
}
```

### Translating & writing
`makeContent()` calls Azure via `curlRequest()` and writes results back, updating the cache. The final PHP file is generated with `var_export()`:
```php
$translated = $this->makeContent($items, $data, $lang, $keys);
$phpOut     = "<?php\nreturn " . var_export($translated, true) . ";\n";
file_put_contents($destPath, $phpOut);
```

---

## Settings packs (`.settings.php`)
Settings files may have **nested** translatable values (e.g., a `menu` array). We collect **paths** to each translatable string and write them back with `setByPath()`.

### Collecting candidates
```php
private function generateSettingsTranslate(array $settings, string $lang): array
{
    $paths = [];
    $texts = [];

    if (isset($settings['title']) && is_string($settings['title']) && preg_match('/\p{L}/u', $settings['title'])) {
        $paths[] = ['title'];
        $texts[] = $settings['title'];
    }

    if (!empty($settings['menu']) && is_array($settings['menu'])) {
        foreach ($settings['menu'] as $menuKey => $menuVal) {
            if (is_string($menuVal) && preg_match('/\p{L}/u', $menuVal)) {
                $paths[] = ['menu', $menuKey];
                $texts[] = $menuVal;
            }
        }
    }

    if (!$paths) return $settings;

    [$cachedIdx, $strings] = $this->checkCached($texts, $lang);

    // Build translation batch only for misses
    $toTranslate = [];
    $mapIdx      = [];
    foreach ($strings as $i => $text) {
        if (!in_array($i, $cachedIdx, true) && $text !== '') {
            $mapIdx[]     = $i;
            $toTranslate[] = ['Text' => $text];
        }
    }

    $decoded = $toTranslate ? $this->curlRequest($toTranslate, $lang) : [];

    // Stitch results back by original indexes
    foreach ($strings as $i => $text) {
        $translated = in_array($i, $cachedIdx, true)
            ? $text
            : ($decoded[array_search($i, $mapIdx, true)]['translations'][0]['text'] ?? $text);

        $this->setByPath($settings, $paths[$i], $translated, $lang);
    }

    return $settings;
}
```

### Writing nested values
```php
private function setByPath(array &$arr, array $path, mixed $value, string $lang): void
{
    $ref =& $arr;
    foreach ($path as $idx => $key) {
        if ($idx === count($path) - 1) {
            $this->setCached($lang, $value, $ref[$key]); // update cache using original value
            $ref[$key] = $value;                         // write translation
            return;
        }
        if (!isset($ref[$key]) || !is_array($ref[$key])) $ref[$key] = [];
        $ref =& $ref[$key];
    }
}
```
Finally, we output the resulting array into `<?php return ...;` via `var_export()` exactly like for `.lang.php`.

---

## Destination paths & structure
Destination path for a translated file is derived by swapping the base locale suffix with the target language:
```php
$srcPath  = $file->getPathname();
$destPath = str_replace("_docs-{$this->config['target_lang']}", "_docs-{$lang}", $srcPath);
```
Directories are created on demand:
```php
$dir = dirname($destPath);
if (!is_dir($dir)) mkdir($dir, 0777, true);
```

---

## Caching behavior
All three flows (front matter, `.lang.php`, `.settings.php`) use the same cache API:
- **Keying**: `normalize($text)` ⇒ SHA‑1 over LF‑normalized, whitespace‑collapsed string.
- **Read**: `[$cachedKeys, $data] = checkCached($data, $lang)` marks cached positions and inlines cached translations.
- **Write**: `setCached($lang, $translated, $original)` updates the in‑memory cache.
- **Persist**: `saveCache()` writes `translate_<lang>.json`, `.config.json` (locale names via `Symfony\Component\Intl\Languages`), and `hash.json`.

---

## Incremental updates & guards
- **Per‑file hash**: `hashData[$lang][$filePath] = md5(file)` — unchanged files are skipped on subsequent runs.
- **Duplicate language guard**: if a target `lang` is already present in Docara `locales`, `translateFiles()` throws:
```php
if (in_array($lang, array_keys($this->usedLocales), true)) {
    throw new Exception('Language "' . $lang . '" is already translated.');
}
```

---

## Testing checklist
- Front matter keys in `frontMatter` are translated; others remain intact.
- `.lang.php`: only string values with letters are translated; arrays/numbers untouched.
- `.settings.php`: nested paths (e.g., `menu.*`) are translated; non‑string values skipped.
- Cache hit: repeated runs avoid API calls; outputs are stable.
- Destination path uses `_docs-<lang>` mirroring the base tree.

---

## Tips
- Keep the `frontMatter` list short and intentional (titles, descriptions).
- If you need additional nested settings translated (beyond `title` and `menu`), extend `generateSettingsTranslate()` with more path collectors.
- Consider wrapping `file_put_contents()` with an atomic write (tmp file → rename) in CI.

