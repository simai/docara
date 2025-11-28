---
extends: _core._layouts.documentation
section: content
title: Attribute Syntax & Parsing
description: Attribute Syntax & Parsing
---

# Attribute Syntax & Parsing

This page documents how inline attributes on the **opening tag line** are parsed and merged, and how to use them effectively.

---

## Quick summary
- Supported forms on the open line:
    - Key–value pairs: `key="value"`, `key:'value'`, `key=value`
    - Shorthands: `.class` (append to `class`), `#id` (set `id`)
- Classes are concatenated **and de‑duplicated**; other keys override by **last write wins**.
- Parsing assumes **ASCII quotes** (`"'`) and standard spaces. See **Unicode notes** if your content uses smart quotes/NBSP.

---

## API
### `Attrs::parseOpenLine(?string $attrStr): array`
Parses the substring after `!type` on the opening line and returns an associative array of attributes.

**Behavior (current implementation):**
1. Trim the string; return `[]` when empty.
2. Initialize an empty class list; set `$attrs['class']` temporarily.
3. Collect all `key=value` pairs using the regex:
   ```php
   /(\w[\w:-]*)\s*=\s*(?:"([^"]*)"|'([^']*)'|(\S+))/
   ```
    - **Key**: letters/digits/underscore, then any of `[\w:-]` (ASCII only)
    - **Value**: either double‑quoted, single‑quoted, or an unquoted token (no whitespace or `"'=<>
`` `)
    - Special case: for `key === 'class'` values are split by whitespace and appended to the class list
4. Remove all matched `key=value` pairs from the string.
5. Parse shorthands with:
   ```php
   /([.#])([\w:-]+)/
   ```
    - `.` appends to the class list, `#` sets `id`
6. If the class list is non‑empty, set `$attrs['class']` to the **deduplicated** whitespace‑joined list; otherwise remove the temporary `class` key.
7. Return `$attrs`.

> **Note:** The current regexes are ASCII‑centric (no `u` modifier). If you expect Unicode letters/spaces/quotes, see **Unicode notes** below for a recommended enhancement.

### `Attrs::merge(array ...$sets): array`
Merges multiple attribute maps into a new array.

- Iterates left‑to‑right; for **non‑`class`** keys, later values override earlier ones.
- For `class`, splits each value by whitespace, concatenates all classes, **deduplicates**, and rejoins with a single space.
- Returns the combined map. (If no classes present, `class` is omitted.)

**Example:**
```php
Attrs::merge(
  ['class' => 'a b', 'id' => 'x'],
  ['class' => 'b c', 'data-x' => '42'],
);
// => ['id' => 'x', 'data-x' => '42', 'class' => 'a b c']
```

---

## Examples
### 1) Quoted value with spaces
**Open line**
```md
!example class:"mb-4 border" data-x=42
```
**Parsed attrs**
```php
['data-x' => '42', 'class' => 'mb-4 border']
```

### 2) Shorthands and id
**Open line**
```md
!example .card .shadow #hero
```
**Parsed attrs**
```php
['id' => 'hero', 'class' => 'card shadow']
```

### 3) Merge with base attributes
**Base**
```php
['class' => 'example overflow-hidden']
```
**Inline**
```php
['class' => 'mb-4 overflow-hidden', 'data-x' => '1']
```
**Result of `Attrs::merge(base, inline)`**
```php
['data-x' => '1', 'class' => 'example overflow-hidden mb-4']
```
> Note how `overflow-hidden` is **deduplicated**.

### 4) Duplicate id (last wins)
```php
Attrs::merge(['id' => 'a'], ['id' => 'b']); // ['id' => 'b']
```

---

## Unicode notes (smart quotes, NBSP, non‑ASCII keys)
The stock regexes in `Attrs::parseOpenLine()` use ASCII classes (e.g., `\w`) and no `u` modifier, which means:
- “Smart quotes” (`“ ” ‘ ’`) won’t match the quoted branches.
- Non‑breaking spaces (NBSP `\xC2\xA0`) won’t be treated as whitespace.
- Keys with non‑ASCII letters won’t match `\w`.

**Authoring guideline:** Use plain ASCII quotes and spaces in the opening line, e.g., `class:"a b"`.

**Recommended enhancement (optional):**
- Normalize smart quotes/NBSP to ASCII **before** parsing, or
- Switch to Unicode‑aware regexes (add the `u` modifier and use `\p{L}`/`\p{N}`), e.g.:

```php
// Pre-normalize
$attrStr = str_replace(["\xC2\xA0", "“", "”", "‘", "’"], [' ', '"', '"', "'", "'"], $attrStr);

// Unicode-aware patterns
if (preg_match_all('/([\p{L}\p{N}_][\p{L}\p{N}_:-]*)\s*=\s*(?:"([^"]*)"|' .
                   "([^']*)" .
                   '|([^\s"\'=<>`]+))/u', $attrStr, $m, PREG_SET_ORDER)) {
    // ...
}
```

> If you adopt the Unicode version, update **both** the `key=value` and the shorthand patterns, and prefer `preg_split('/\s+/u', ...)` when splitting classes.

---

## Security & validation
- `Attrs` **does not escape** values; escaping happens at render time (`HtmlElement` or your custom renderer) — always escape any value you concatenate manually.
- To prevent unwanted attributes (e.g., `onclick`), implement a per‑tag `attrsFilter(array $attrs, array $meta): array` and **whitelist** allowed keys.

---

## Edge cases & tips
- Boolean flags are **not parsed**; prefer `flag="true"` or map presence in `attrsFilter()`.
- Repeated shorthand `#id` — the last value wins after `Attrs::merge`.
- Extra text after the closing marker is ignored by the parser; attributes must be on the **opening** line.
- Empty `class` entries are filtered out; no trailing spaces in the result.

---

## Tests you should have
1. Quoted/unquoted values; spaces inside quotes.
2. Multiple classes with duplicates ➜ deduped.
3. `.class` + `class:"..."` together ➜ merged predictably.
4. `#id` overrides base `id` via `merge()`.
5. (If enabled) Unicode quotes/NBSP normalization.

---

## Authoring cheatsheet
- Add classes: `.box .rounded` or `class:"box rounded"`
- Set id: `#anchor`
- Key–value with spaces: `title:"Complex value here"`
- Data attributes: `data-x=42 data-name:'Alice'`

