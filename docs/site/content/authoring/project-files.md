# Файлы переносимого проекта

Минимальный сайт состоит из двух корневых JSON-файлов и каталога содержания.

```text
docara.json
simai-framework.lock.json
content/
  _section.json
  index.md
  index.page.json
  guides/
    _section.json
    install.md
    install.page.json
```

`docara.json` задаёт общие параметры. Каждый `_section.json` действует на свой
каталог и потомков. Sidecar `<page>.page.json` переопределяет настройки одной
страницы. Markdown остаётся содержанием.

`build_local` и `build_production` — результаты сборки, а не источник истины.
Служебные `_docara` и `.docara` зарезервированы генератором.

Далее: [конфигурация](/authoring/configuration/) и
[наследование](/authoring/inheritance/).
