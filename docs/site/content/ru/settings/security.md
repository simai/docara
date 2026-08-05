# Безопасность настроек

Docara принимает IDs и данные, но не исполняемые project hooks.

Fail-closed запрещены:

- traversal, absolute/output-root escape и include вне roots;
- symlink/junction-like escape, hardlink target и case collision;
- class, callback, PHP/template/filesystem path из Markdown/config;
- raw HTML/CSS и external embed вне explicit allowlist/sandbox;
- duplicate namespace/ownership и implicit package shadowing;
- запись scaffold/QA/preview в engine, lock, generated или external roots;
- stale/hash-mismatched apply plan.

Ошибка не должна менять ни одного байта вне verified project root. Diagnostics используют safe relative path/pointer/location и не публикуют secrets/private absolute paths.

