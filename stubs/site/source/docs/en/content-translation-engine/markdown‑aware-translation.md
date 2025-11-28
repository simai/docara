---
extends: _core._layouts.documentation
section: content
title: Markdown‑aware Translation
description: Markdown‑aware Translation
---

# Markdown‑aware Translation 

This chapter documents the **actual PHP implementation** that extracts translatable text from Markdown, sends it to Azure, and writes translations back **without breaking markup**.

---

## Where it happens
- **Class:** `App\Helpers\Translate`
- **Entry:** `generateTranslateContent(string $file, string $lang): string`
- **Pre‑step:** Front matter is handled separately via `frontMatterParser()` and `translateFromMatter()`.

---

## Parser setup
We build a CommonMark **Environment** with our Custom Tags extension and create a **MarkdownParser** (not a converter):

```php
private function initParser(): void
{
    $environment = new Environment([]);
    $environment->addExtension(new CustomTagsExtension($this->registry));
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new FrontMatterExtension());
    $this->parser = new MarkdownParser($environment);
}
```

---

## Collecting text nodes
We parse the Markdown into an AST and walk it. Only **`Text`** nodes are collected; code blocks/inline code are distinct node types and are therefore skipped implicitly.

```php
$document  = $this->parser->parse($file);
$textNodes = [];
$walker = $document->walker();
while ($event = $walker->next()) {
    $node = $event->getNode();
    if ($event->isEntering() && ($node instanceof Text)) {
        $text = trim($node->getLiteral());
        if ($text !== '') {
            $textNodes[] = $node;
        }
    }
}
```

### Line ranges of a text segment
For each `Text` node we bubble up to the nearest **`AbstractBlock`** and use its start/end lines:

```php
private function getNodeLines(Node $node): array
{
    $parent = $node;
    $range  = ['start' => 0, 'end' => 0];
    while ($parent !== null && !$parent instanceof AbstractBlock) {
        $parent = $parent->parent();
    }
    if ($parent !== null) {
        if (method_exists($parent, 'getStartLine')) $range['start'] = $parent->getStartLine();
        if (method_exists($parent, 'getEndLine'))   $range['end']   = $parent->getEndLine();
    }
    return $range;
}
```

### Filtering non‑linguistic strings
We only send strings that contain at least one **Unicode letter**:

```php
if (!preg_match('/\p{L}/u', $text)) continue; // skip numbers, symbols, etc.
```

We then build the candidate list:
```php
$textsToTranslateArray[] = [
  'text'  => $text,
  'start' => $lines['start'],
  'end'   => $lines['end'],
];
```

---

## Cache pass
Before hitting the provider, we replace any strings found in the cache and only send **misses**.

```php
$flatten = array_map(fn($x) => $x['text'], $textsToTranslateArray);
[$cachedIdx, $flatten] = $this->checkCached($flatten, $lang);
$keys      = array_keys($textsToTranslateArray);
$keysAssoc = array_flip($cachedIdx);
$extracted = array_intersect_key($textsToTranslateArray, $keysAssoc);

// carry cached translations
foreach ($extracted as $k => $val) {
    $extracted[$k]['translated'] = $flatten[$k];
}

// keep only misses for API calls
$textsToTranslateArray = array_values(array_diff_key($textsToTranslateArray, $keysAssoc));
```

> **Cache keys** are SHA‑1 over a normalized form of the source string (`normalize()` strips CRLF and collapses horizontal whitespace).

---

## Batching & sending
We split the remaining items into **≈ 9000‑char** chunks and call Azure. After each request we throttle by **characters‑per‑minute**.

```php
$chunks = $this->chunkTextArray($textsToTranslateArray);
$finalTranslated = [];
foreach ($chunks as $chunk) {
    $translatedChunk = $this->translateText($chunk, $lang); // uses curlRequest()
    $finalTranslated = array_merge($finalTranslated, $translatedChunk);

    $chars = 0; foreach ($chunk as $c) $chars += mb_strlen($c['text']);
    $this->throttleByCharsPerMinute($chars);
}
```

`translateText()` maps responses back **by index** and updates the cache:
```php
foreach ($textsToTranslate as $i => &$original) {
    $original['translated'] = $translateData[$i]['translations'][0]['text'] ?? $original['text'];
    $this->setCached($toLang, $original['translated'], $original['text']);
}
```

---

## Re‑assembling results in original order
We merge cached hits (`$extracted`) and fresh translations into a single array aligned to the **original indices**:

```php
$finalBlock = $finalTranslated; // only API results
$i = 0;
foreach ($keys as $k) {
    if (array_key_exists($k, $extracted)) {
        $finalTranslated[$k] = $extracted[$k];
    } else {
        $finalTranslated[$k] = $finalBlock[$i++];
    }
}
```

---

## Bottom‑up replacement by line ranges
We normalize EOLs to `\n`, split into lines, then apply edits **from bottom to top**. For each block we:
1) slice the affected line range;
2) find the **last** occurrence of the original text in that slice;
3) replace it with the translation;
4) splice the changed lines back into the document.

```php
$normalized = str_replace("\r\n", "\n", $file);
$lines = preg_split('/\R/u', $normalized);

foreach (array_reverse($finalTranslated) as $block) {
    $start = $block['start'];
    $end   = $block['end'];
    $slice = implode("\n", array_slice($lines, $start - 1, $end - $start + 1));

    $replaced = $this->replace_last_literal($slice, $block['text'], $block['translated']);
    $replacedLines = explode("\n", $replaced);

    array_splice($lines, $start - 1, $end - $start + 1, $replacedLines);
}

return implode("\n", $lines);
```

**Exact helper used:**

```php
private function replace_last_literal(string $haystack, string $search, string $replace): string {
    $pos = mb_strrpos($haystack, $search);
    if ($pos === false) return $haystack;
    return markdown‑aware-translation.mdmb_substr($haystack, 0, $pos)
         . $replace
         . mb_substr($haystack, $pos + mb_strlen($search));
}
```

> Using the **last** occurrence reduces the chance of touching earlier duplicates within the same block when multiple `Text` nodes share identical content.

---

## What remains untouched
- **Code blocks** (`FencedCode`, `IndentedCode`) and **inline code** (`Code`).
- **URLs** and link/image destinations; only human‑readable labels/alt text are translated.
- **Custom tag attributes**; only inner text content is processed.

---

## Edge cases & notes
- **Start/end lines = 0**: If a node’s ancestor doesn’t expose line info, `start/end` may be `0`. Guard against negative indices when slicing; in practice CommonMark block nodes provide line numbers for author content.
- **Duplicate phrases in one range**: We target the **last** match in the block. If you need precise targeting for multiple identical phrases, add column offsets.
- **CRLF**: Input is normalized to LF for processing; output is joined with `\n`.

---

## Safety checklist
- [ ] Gather only `Text` nodes (`instanceof Text`).
- [ ] Skip non‑linguistic strings (`/\p{L}/u`).
- [ ] De‑dup via cache before sending to the provider.
- [ ] Batch by size and throttle by CPM.
- [ ] Replace **bottom‑up** using captured line ranges.
- [ ] Persist caches after the run.

---

## Related code paths
- **Front matter**: `frontMatterParser()` + `translateFromMatter()`
- **PHP arrays**: `translateLangFiles()`, `generateSettingsTranslate()`, `makeContent()`
- **Azure calls**: `curlRequest()`, `translateText()`

