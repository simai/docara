---
extends: _core._layouts.documentation
section: content
title: Azure Provider & Rate Limiting
description: Azure Provider & Rate Limiting
---

# Azure Provider & Rate Limiting (PHP)

Docara ships a built-in translator (in core) that calls Azure Cognitive Services — Translator, batches requests, and throttles by characters-per-minute.

---

## Environment & endpoint
- `.env` variables loaded at runtime:
  - `AZURE_KEY`
  - `AZURE_REGION`
  - `AZURE_ENDPOINT` (default: `https://api.cognitive.microsofttranslator.com`)
- Effective endpoint per request:

```php
$url = $this->endpoint . '/translate?api-version=3.0&to=' . $toLang;
// We do not set &from=...; Azure auto-detects source language.
```

Headers:
```php
$headers = [
    'Content-Type: application/json',
    'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
    'Ocp-Apim-Subscription-Region: ' . $this->region,
];
```

---

## HTTP client (`curlRequest`)
```php
private function curlRequest(array $data, string $toLang): array
{
    $url = $this->endpoint . '/translate?api-version=3.0&to=' . $toLang;
    $headers = [
        'Content-Type: application/json',
        'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        'Ocp-Apim-Subscription-Region: ' . $this->region,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'ERROR CURL: ' . curl_error($ch) . "\n";
    }

    return json_decode($response, true);
}
```
- Body format: JSON array of objects with a single `Text` property, e.g. `[{"Text":"Hello"}, {"Text":"World"}]`.
- Response mapping: array aligned by index with the request.

---

## Building requests
We send text in three contexts, all via `curlRequest()`:

1) **Markdown text nodes** — `translateText()`
```php
private function translateText($textsToTranslate, $toLang): array
{
    $postData = array_map(fn($item) => ['Text' => $item['text']], $textsToTranslate);
    $translateData = $this->curlRequest($postData, $toLang);

    foreach ($textsToTranslate as $index => &$original) {
        $original['translated'] = $translateData[$index]['translations'][0]['text'] ?? $original['text'];
        $this->setCached($toLang, $original['translated'], $original['text']);
    }
    return $textsToTranslate;
}
```

2) **Front matter** — `translateFromMatter()` → `makeContent()`
```php
private function translateFromMatter(array $frontMatter, string $lang): array
{
    [$cachedIdx, $frontMatter] = $this->checkCached($frontMatter, $lang);
    $items = $keys = [];
    foreach ($frontMatter as $k => $v) {
        if (!in_array($k, $cachedIdx, true)
            && in_array($k, $this->config['frontMatter'], true)
            && is_string($v) && preg_match('/\p{L}/u', $v)) {
            $keys[]  = $k;
            $items[] = ['Text' => $v];
        }
    }
    return $this->makeContent($items, $frontMatter, $lang, $keys);
}
```

3) **PHP language/settings arrays** — `translateLangFiles()` / `generateSettingsTranslate()` → `makeContent()`
```php
private function makeContent(array $items, $langContent, $lang, array $keys): mixed
{
    if (!$items) return $langContent;

    $translateData = $this->curlRequest($items, $lang);
    foreach ($translateData as $i => $entry) {
        $translated = $entry['translations'][0]['text'] ?? null;
        if ($translated !== null) {
            $this->setCached($lang, $translated, $langContent[$keys[$i]]);
            $langContent[$keys[$i]] = $translated;
        }
    }
    return $langContent;
}
```

---

## Batching (`chunkTextArray`) and where it’s used
Large Markdown texts are split into ~9,000-character batches before sending:

```php
private function chunkTextArray(array $items, int $maxChars = 9000): array
{
    $chunks = [];
    $currentChunk = [];
    $currentLength = 0;

    foreach ($items as $item) {
        $length = mb_strlen($item['text']);

        if ($length >= $maxChars) {
            if (!empty($currentChunk)) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentLength = 0;
            }
            $chunks[] = [$item];
            continue;
        }

        if ($currentLength + $length > $maxChars) {
            $chunks[] = $currentChunk;
            $currentChunk = [];
            $currentLength = 0;
        }

        $currentChunk[] = $item;
        $currentLength += $length;
    }

    if (!empty($currentChunk)) {
        $chunks[] = $currentChunk;
    }

    return $chunks;
}
```

**Usage (Markdown path):**
```php
$chunks = $this->chunkTextArray($textsToTranslateArray);
$finalTranslated = [];
foreach ($chunks as $chunk) {
    $translatedChunk   = $this->translateText($chunk, $lang);
    $finalTranslated   = array_merge($finalTranslated, $translatedChunk);
    $chars = array_sum(array_map(fn($c) => mb_strlen($c['text']), $chunk));
    $this->throttleByCharsPerMinute($chars);
}
```

---

## Throttling by characters-per-minute
The translator enforces a CPM budget with a small random jitter per batch.

```php
private function throttleByCharsPerMinute(int $chars): void
{
    $limit   = $this->config['chars_per_minute'] ?? 30000; // default
    $seconds = ($chars / max(1, $limit)) * 60.0;           // proportional delay
    $seconds += mt_rand(0, 200) / 1000.0;                  // +0..200 ms jitter
    usleep((int) round($seconds * 1_000_000));
}
```
- Config key: `chars_per_minute` (set in `translate.config.php` if needed).
- Delay is applied **after** each request with that batch size.

---

## Response mapping & caching
### Mapping by index
All translation responses are mapped back by **array index** to the original items:
```php
$original['translated'] = $translateData[$index]['translations'][0]['text'] ?? $original['text'];
```

### Cache API
We cache by a stable hash of the normalized source string:
```php
private function setCached(string $toLang, string $translatedText, string $originalText): void
{
    $key = $this->normalize($originalText); // SHA-1 over trimmed, whitespace-normalized text
    $this->prevTranslation[$toLang][$key] = $translatedText;
}

private function getCached(string $toLang, string $originalText): ?string
{
    $key = $this->normalize($originalText);
    return $this->prevTranslation[$toLang][$key] ?? null;
}
```
Cache files are persisted under `<cache_dir>/translations/`:
- `translate_<lang>.json` — key → translated text
- `hash.json` — per-file checksums to skip unchanged files
- `.config.json` — locale names for Docara’s `beforeBuild`

---

## Error handling (current behavior)
- `curlRequest()` prints `ERROR CURL: ...` when cURL fails and returns `json_decode($response, true)` (which may be `null`).
- Callers use null-coalescing fallbacks, e.g. `...['text'] ?? $original` — the original text is kept if the response is missing.

> For stricter handling, add HTTP status checks and retries.

---

## End-to-end example (Markdown)
```php
// Gather text nodes with line ranges
$texts = [
  ['text' => 'Hello **world**', 'start' => 12, 'end' => 12],
  ['text' => 'Read more',        'start' => 18, 'end' => 18],
];

// Batch → request → throttle
$chunks = $this->chunkTextArray($texts);
$result = [];
foreach ($chunks as $chunk) {
    $tr = $this->translateText($chunk, 'ru');
    $result = array_merge($result, $tr);
    $chars = array_sum(array_map(fn($x) => mb_strlen($x['text']), $chunk));
    $this->throttleByCharsPerMinute($chars);
}

// Apply bottom-up by lines (see Markdown chapter)
```

---

## Notes
- We rely on **auto-detect** for the source language; if your base locale is fixed, extend `curlRequest()` to append `&from=<base>` from config.
- Batch size and CPM are defaults; tune them in config/wrapper logic as needed.
