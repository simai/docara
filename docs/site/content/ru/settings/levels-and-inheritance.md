# Уровни и inheritance

Effective configuration собирается в порядке `site → section ancestors → page`. Более близкий уровень переопределяет только разрешённое поле; object merge не превращает неизвестные keys в допустимые.

| Уровень | Файл | Что хранит | Provenance в diagnostics |
| --- | --- | --- | --- |
| Site | `docara.json` | project-wide roots, locales, locks и shared presentation | `site` + JSON pointer |
| Section | ближайший `section.json` | настройки раздела и его descendants | относительный section path + pointer |
| Page | `<route>.page.json` или поддержанное front matter | page metadata и локальные presentation overrides | Markdown/sidecar path + line/pointer |

Чтобы сбросить override, удалите поле на более близком уровне: значение снова придёт из parent/default. Не копируйте весь effective object вниз — это скрывает provenance и мешает будущим default updates.

```bash
docara inspect page /ru/components/alert/ --json
docara build production --page=/ru/components/alert/
```

Global locale, route, navigation topology, shared lang или schema change требует full build; обычная правка prose/локального page setting допускает single-page rebuild при сохранённом accepted full receipt.

