# Поиск и чтение

`search.enabled/indexed` управляет UI и включением страницы в index. `reading` включает breadcrumbs, TOC, mobile TOC, глубину outline и previous/next.

```json
{"search":{"enabled":true,"indexed":true},"reading":{"breadcrumbs":true,"toc":true,"toc_depth":3,"previous_next":true}}
```

Search text строится из typed/rendered document semantics без component-ID allowlist. Navigation, outline, breadcrumbs и pager используют тот же PageBuilder context. Изменение shared search/reading policy требует full build.
