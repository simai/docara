# Переход с Mix на Vite

Laravel Mix и пакет `laravel-mix-docara` относятся к старому watcher/build
контуру. Canonical theme development использует Vite, а portable authoring не
использует Node вообще.

## Сначала определите контур

- Если проект только хранит Markdown/JSON и собирает portable output, удалять
  или добавлять frontend toolchain не нужно: используйте PHP-команды Docara.
- Если проект развивает исходные JS/SCSS темы, мигрируйте его maintainer
  workflow на Vite.

Для каждого legacy consumer переход выполняется отдельно.

:::steps
1. Найдите `laravel-mix-docara`, `webpack.mix.js` и связанные lockfile-записи.
2. Перенесите только реальные entrypoints и static-copy правила в Vite.
3. Обновите scripts и lockfile чистой установкой выбранного package manager.
4. Проверьте production build и development/watch сценарий.
5. Убедитесь, что собранные URL и ассеты не изменились неожиданно.
6. Повторите zero-reference scan по всем активным consumers.

:::

Сравните имена и содержимое собранных ассетов, development/watch обновление и
production build. Успех одного consumer не доказывает готовность остальных.

Перед запуском `docara init --update` оставьте ровно один `yarn.lock` и
уберите собственные frontend-зависимости из корневого `package.json` либо
оформите их через отдельно принятый extension contract. Init проверяет это до
создания `.env`, очистки кэша и копирования `_core`.

`docara-mix` не нужен новым consumer-проектам. Не удаляйте repository как
способ «завершить миграцию»: сначала каждый активный consumer должен пройти
clean install, production build и development/watch smoke, затем независимая
приёмка подтверждает отсутствие package, config и CI-ссылок. После этого
repository можно архивировать с сохранённой историей и migration evidence.

Актуальный Vite-контракт и точные версии находятся в
[руководстве по ассетам](/development/vite-assets/).
