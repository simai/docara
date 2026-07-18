# Быстрый старт

Этот маршрут создаёт переносимый сайт в пустом каталоге. Он не копирует
`source/_core`, не создаёт `.env` и не устанавливает Node-зависимости.

:::steps
1. Создайте пустой каталог проекта и перейдите в него.
2. Выполните `composer require simai/docara`.
3. Запустите `php vendor/bin/docara init --portable`.
4. Соберите сайт командой `php vendor/bin/docara build local`.
5. Откройте результат из каталога `build_local`.

:::

## Что появится в проекте

```text
composer.json
composer.lock
vendor/              # локальные PHP-зависимости; не часть starter/mirror
docara.json
simai-framework.lock.json
assets/
  docara-mark.svg
content/
  _section.json
  index.md
  index.page.json
  landing.md
  landing.page.json
  guides/
    _section.json
    index.md
    getting-started.md
    getting-started.page.json
    platform/
      _section.json
      index.md
      configuration/
        _section.json
        index.md
        layout.md
```

## Измените первую страницу

Откройте `content/index.md`, измените заголовок или текст и повторите сборку.
Настройки страницы можно вынести в соседний `index.page.json`.

Готовый starter уже содержит логотип и настоящее дерево из четырёх уровней.
Измените `branding` в `docara.json` и структуру `content/`, чтобы получить свой
макет без правки PHP-шаблонов.

Локальный поиск уже включён через
`"search": {"enabled": true, "indexed": true}`. Он создаёт статический индекс
без внешнего сервиса; отдельные страницы можно исключить через
`search.indexed: false`.

Дальше прочитайте [модель файлов](/authoring/project-files/) и
[настройки сайта](/authoring/configuration/), затем откройте
[руководство по поиску](/authoring/search/).
