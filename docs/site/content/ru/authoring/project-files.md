# Файлы переносимого проекта

Starter состоит из трёх корневых JSON-файлов, каталога содержания и
machine-readable ownership state.

```text
docara.json
redirects.json
simai-framework.lock.json
assets/
  logo.svg
  logo-dark.svg
  favicon.svg
examples/
  utilities/
    animation-duration/
      index.html
      index.css
      index.js
      assets/
content/
  ru/
    lang.json
    section.json
    index.md
    guides.md
    guides/
      section.json
      install.md
      install.page.json  # необязательная композиция только этой страницы
translations.lock.json  # необязательное принятое состояние переводов
.docara/
  engine/
    ownership.json
```

`docara.json` задаёт общие параметры и указывает `redirects_file`.
`redirects.json` хранит старые внутренние маршруты и их существующие targets;
если совместимость URL не нужна, поле и файл можно не создавать. Каждый
`section.json` действует на свой каталог и потомков. Не создавайте его, если
ветке не нужны собственные настройки. Sidecar `<page>.page.json`
переопределяет настройки одной страницы и тоже необязателен: `install.md`
соберётся без `install.page.json`, унаследовав сайт и родительские разделы.
JSON нужен только когда требуется изменить slug, preset, навигацию, макет или
другое поведение именно этой страницы. `title`, `description`, `tags`, `draft`
и `translation_key` задавайте в front matter Markdown.

Рекомендуемая форма раздела плоская: `content/ru/guides.md` владеет
`/ru/guides/`, а соседний каталог `content/ru/guides/` содержит дочерние
страницы и `section.json`. Runtime также понимает
`content/ru/guides/index.md`, но одновременно создавать обе формы нельзя:
сборка завершится `PAGE_SOURCE_ROUTE_AMBIGUOUS`.

Для всех уровней используется одно имя `section.json`, без начального
подчёркивания. Если в старом проекте остался `_section.json`, переименуйте его:
сборка остановится с кодом `SECTION_DESCRIPTOR_LEGACY_NAME`, чтобы настройки
не потерялись незаметно.

`build_local` и `build_production` — результаты сборки, а не источник истины.
В `.docara/engine` находится package-owned snapshot и ownership manifest;
рядом update временно хранит план и проверяемые rollback packages. Эти файлы
не редактируют вручную. Внутренний `_docara` внутри build-каталога также
генерируется. `content`, `assets`, корневые JSON и consumer-owned
`composer.lock` остаются пользовательскими файлами и не являются update
targets.
`examples/` также является project-owned source: один каталог примера может
обслуживать несколько страниц и локалей. `translations.lock.json` меняется
только после защищённого `translations accept`; status и build его не
перезаписывают.
Корневой `assets/` удобен для логотипов и favicon; содержательные изображения
можно хранить рядом с Markdown. Брендовые пути указываются в `docara.json` и
проверяются до очистки предыдущей сборки.

Далее: [конфигурация](/authoring/configuration/) и
[наследование](/authoring/inheritance/).
