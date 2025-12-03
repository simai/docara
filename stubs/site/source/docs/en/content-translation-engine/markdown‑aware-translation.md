---
extends: _core._layouts.documentation
section: content
title: Markdown-aware Translation
description: Markdown-aware Translation
---

# Markdown-aware Translation

Docara’s translator (in core) extracts text from Markdown, sends it to Azure, and writes translations back **without breaking markup**.

---

## Where it happens
- **Class:** core translator (see `Simai\Docara\Translation`)
- **Entry:** `generateTranslateContent(string $file, string $lang): string`
- **Pre-step:** front matter is handled separately (`frontMatterParser()`, `translateFromMatter()`).

---

## Parser setup
CommonMark environment with Custom Tags + core extensions, using a **MarkdownParser** (not converter):

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
Parse to AST, walk, and collect **`Text`** nodes only; code blocks/inline code are separate node types and skipped.

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
Bubble up to the nearest **`AbstractBlock`** to get start/end lines:

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

### Filtering non-linguistic strings
Skip strings without letters:

```php
if (!preg_match('/\p{L}/u', $text)) continue; // skip numbers, symbols, etc.
```

Build the candidate list:
```php
$textsToTranslateArray[] = [
  'text'  => $text,
  'start' => $lines['start'],
  'end'   => $lines['end'],
];
```

---

## Cache pass
Replace any strings found in cache; send only misses:

```php
$flatten = array_map(fn($x) => $x['text'], $textsToTranslateArray);
[$cachedIdx, $flatten] = $this->checkCached($flatten, $lang);
$keys      = array_keys($textsToTranslateArray);
$keysAssoc = array_flip($cachedIdx);
$extracted = array_intersect_key($textsToTranslateArray, $keysAssoc);

foreach ($extracted as $k => $val) {
    $extracted[$k]['translated'] = $flatten[$k];
}

$textsToTranslateArray = array_values(array_diff_key($textsToTranslateArray, $keysAssoc));
```

> Cache keys are SHA-1 over normalized source strings (`normalize()` strips CRLF and collapses whitespace).

---

## Batching & sending
Split remaining items into ~9,000-char chunks, call Azure, then throttle by characters-per-minute:

```php
$chunks = $this->chunkTextArray($textsToTranslateArray);
$finalTranslated = [];
foreach ($chunks as $chunk) {
    $translatedChunk   = $this->translateText($chunk, $lang); // uses curlRequest()
    $finalTranslated   = array_merge($finalTranslated, $translatedChunk);
    $chars = array_sum(array_map(fn($c) => mb_strlen($c['text']), $chunk));
    $this->throttleByCharsPerMinute($chars);
}
```

`translateText()` maps responses back **by index** and updates cache:
```php
foreach ($textsToTranslate as $i => &$original) {
    $original['translated'] = $translateData[$i]['translations'][0]['text'] ?? $original['text'];
    $this->setCached($toLang, $original['translated'], $original['text']);
}
```

---

## Re-assembling results in original order
Merge cached hits and fresh translations, aligned to original indices:

```php
$finalBlock = $finalTranslated; // API results
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

## Bottom-up replacement by line ranges
Normalize EOLs, split into lines, then apply edits **from bottom to top**:

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

**Helper:**

```php
private function replace_last_literal(string $haystack, string $search, string $replace): string {
    $pos = mb_strrpos($haystack, $search);
    if ($pos === false) return $haystack;
    return mb_substr($haystack, 0, $pos)
         . $replace
         . mb_substr($haystack, $pos + mb_strlen($search));
}
```

Using the **last** occurrence reduces the chance of touching earlier duplicates within the same block when multiple `Text` nodes share identical content.

---

## What remains untouched
- **Code blocks** (`FencedCode`, `IndentedCode`) and **inline code** (`Code`).
- **URLs** and link/image destinations; only human-readable labels/alt text are translated.
- **Custom tag attributes**; only inner text content is processed.

---

## Edge cases & notes
- **Start/end lines = 0**: if a node’s ancestor doesn’t expose line info, `start/end` may be `0`. Guard against negative indices when slicing; CommonMark block nodes usually provide line numbers.
- **Duplicate phrases in one range**: we target the **last** match in the block. For precise targeting of multiple identical phrases, add column offsets.
- **CRLF**: input is normalized to LF; output is joined with `\n`.

---

## Safety checklist
- [ ] Gather only `Text` nodes (`instanceof Text`).
- [ ] Skip non-linguistic strings (`/\p{L}/u`).
- [ ] De-dupe via cache before sending to the provider.
- [ ] Batch by size and throttle by CPM.
- [ ] Replace **bottom-up** using captured line ranges.
- [ ] Persist caches after the run.

---

## Related code paths
- **Front matter**: `frontMatterParser()`, `translateFromMatter()`
- **PHP arrays**: `translateLangFiles()`, `generateSettingsTranslate()`, `makeContent()`
- **Azure calls**: `curlRequest()`, `translateText()`
