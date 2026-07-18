# Переход с Mix на Vite

Laravel Mix и пакет `laravel-mix-docara` относятся к старому watcher/build
контуру. Canonical theme development использует Vite, а portable authoring не
использует Node вообще.

Для каждого legacy consumer переход выполняется отдельно:

:::steps
1. Найдите `laravel-mix-docara`, `webpack.mix.js` и связанные lockfile-записи.
2. Перенесите только реальные entrypoints и static-copy правила в Vite.
3. Обновите scripts и lockfile чистой установкой выбранного package manager.
4. Проверьте production build и development/watch сценарий.
5. Убедитесь, что собранные URL и ассеты не изменились неожиданно.
6. Повторите zero-reference scan по всем активным consumers.

:::

Перед запуском `docara init --update` оставьте ровно один `yarn.lock` и
уберите собственные frontend-зависимости из корневого `package.json` либо
оформите их через отдельно принятый extension contract. Init проверяет это до
создания `.env`, очистки кэша и копирования `_core`.

Не удаляйте `docara-mix` как способ «завершить миграцию». Сначала каждый
consumer должен пройти clean install/build/dev smoke, затем независимая
приёмка подтверждает отсутствие активных ссылок. После этого repository можно
архивировать с сохранённым backup, но не удалять.
