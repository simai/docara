# Матрица доступа

| Поверхность | Доступ | Использование |
|---|---|---|
| Локальный Git/worktree | read | exact target и история |
| `/tmp` | write, disposable | clean install/build/runtime |
| `source/qa` | write, ignored | доказательства аудита |
| Browser loopback | read/interaction | UI acceptance |
| GitHub/Packagist | не требуется | публикация вне scope |
| Production | запрещено | вне scope |

