# Файлы переносимого проекта

Минимальный сайт состоит из двух корневых JSON-файлов и каталога содержания.

```text
docara.json
simai-framework.lock.json
assets/
  logo.svg
  logo-dark.svg
  favicon.svg
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
Корневой `assets/` удобен для логотипов и favicon; содержательные изображения
можно хранить рядом с Markdown. Брендовые пути указываются в `docara.json` и
проверяются до очистки предыдущей сборки.

Далее: [конфигурация](/authoring/configuration/) и
[наследование](/authoring/inheritance/).
