# Сборка и публикация

Обычному автору не нужен frontend toolchain. Команда Docara читает Markdown и
JSON, проверяет Framework lock и записывает готовый статический сайт.

:::steps
1. Проверьте исходники командой `php vendor/bin/docara build local`.
2. Для итогового каталога выполните `php vendor/bin/docara build production`.
3. Проверьте HTML, внутренние ссылки, ассеты и файл `.docara/resolved-page-plans.json`.
4. Размещайте только собранный каталог на выбранном статическом хостинге.

:::

## Подробнее

- [PHP-only сборка](/build/php-only/)
- [Локальный просмотр](/build/local-preview/)
- [Статический результат](/build/static-output/)
- [Воспроизводимость](/build/determinism/)

Vite относится к изменению исходных ассетов темы разработчиком Docara и
описан отдельно в [разделе разработки](/development/vite-assets/).
