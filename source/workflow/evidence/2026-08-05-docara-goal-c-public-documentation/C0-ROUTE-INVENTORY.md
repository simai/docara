# C0 — route/source inventory

Entry revision: `481e34cccade12a0d7f8d2dbf9b4d37933e49419`

Command:

```bash
find docs/site/content -type f -name '*.md' -print | LC_ALL=C sort
```

Result: 104 physical Russian Markdown owners. Every route below is retained as
canonical; project redirects are empty. The builder continues to own root and
legacy-unprefixed locale redirects.

| Route | Physical prose owner | Decision |
| --- | --- | --- |
| `/ru/` | `docs/site/content/ru/index.md` | keep canonical |
| `/ru/start/` | `docs/site/content/ru/start.md` | keep canonical |
| `/ru/landing/` | `docs/site/content/ru/landing.md` | keep canonical |
| `/ru/migration/` | `docs/site/content/ru/migration.md` | keep canonical |
| `/ru/troubleshooting/` | `docs/site/content/ru/troubleshooting.md` | keep canonical |
| `/ru/authoring/` | `docs/site/content/ru/authoring.md` | keep canonical |
| `/ru/authoring/*/` | `docs/site/content/ru/authoring/*.md` (16 owners) | keep canonical |
| `/ru/build/` | `docs/site/content/ru/build.md` | keep canonical |
| `/ru/build/*/` | `docs/site/content/ru/build/*.md` (7 owners) | keep canonical |
| `/ru/components/` | `docs/site/content/ru/components.md` | keep canonical |
| `/ru/components/*/` | `docs/site/content/ru/components/*.md` (32 owners) | keep canonical |
| `/ru/development/` | `docs/site/content/ru/development.md` | keep canonical |
| `/ru/development/*/` | `docs/site/content/ru/development/*.md` (8 owners) | keep canonical |
| `/ru/examples/` | `docs/site/content/ru/examples.md` | keep canonical |
| `/ru/examples/*/` | `docs/site/content/ru/examples/*.md` (12 owners) | keep canonical |
| `/ru/demonstrator-results/*/` | `docs/site/content/ru/demonstrator-results/**/*.md` (13 owners) | keep canonical, hidden derived-result documentation |
| `/ru/reference/` | `docs/site/content/ru/reference.md` | keep canonical |
| `/ru/reference/*/` | `docs/site/content/ru/reference/*.md` (5 owners) | keep canonical |

The wildcard rows are inventory compression, not shared ownership: the command
above resolves each route to one actual file, and the build rejects output
collisions. C6 will preserve the exact route-owner receipt with the expanded
post-Goal-C route set.

## Navigation freeze

| Surface | Labels/order |
| --- | --- |
| Header | Главная → Быстрый старт → Компоненты → GitHub |
| Existing sections | Содержание и макет → Сборка и публикация → Компоненты → Разработка Docara → Справочник |
| Goal C roots | Компоненты → Дизайн и интерфейс → Настройки → Путь разработчика и агента |

## Redirect boundary

- `docs/site/redirects.json`: `redirects=[]` at entry.
- Existing routes are not moved, so no project redirect is required.
- New Goal C pages use new routes and cannot shadow an existing owner.
- Locale redirects remain recorded in generated `.docara/locale-routes.json`.

