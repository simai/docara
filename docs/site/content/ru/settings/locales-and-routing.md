# Локали и routing

Каждая объявленная locale имеет собственный `content/<locale>/<route>.md`, direction, public prefix и `content/<locale>/lang.json`. Silent editorial fallback запрещён; `missing_page_policy` явно выбирает `skip` или `error`.

```json
{
  "default_locale": "ru",
  "locales": {
    "missing_page_policy": "skip",
    "ru": {"label": "Русский", "direction": "ltr", "content_root": "content/ru", "public_prefix": "ru", "fallbacks": []}
  },
  "locale_routing": {"strategy": "prefixed", "root": "redirect", "detect_browser_language": false, "legacy_unprefixed_redirects": true}
}
```

UI copy хранится только в `content/<locale>/lang.json`; page prose и component presentation туда не переносятся. Root/legacy redirects создаёт builder, project route redirects — `redirects.json`.
