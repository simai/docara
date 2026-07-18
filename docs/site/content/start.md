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
content/
  _section.json
  index.md
  index.page.json
  landing.md
  landing.page.json
  guides/
    _section.json
    getting-started.md
    getting-started.page.json
```

## Измените первую страницу

Откройте `content/index.md`, измените заголовок или текст и повторите сборку.
Настройки страницы можно вынести в соседний `index.page.json`.

Дальше прочитайте [модель файлов](/authoring/project-files/) и
[настройки сайта](/authoring/configuration/).
