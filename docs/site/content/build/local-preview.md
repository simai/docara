# Локальный просмотр

Команда `serve` сначала собирает выбранное окружение, а затем запускает
встроенный PHP-сервер.

```bash
php vendor/bin/docara serve local --host=localhost --port=8000
```

Если результат уже собран, добавьте `--no-build`:

```bash
php vendor/bin/docara serve local --host=localhost --port=8000 --no-build
```

Для ServBay или другого локального веб-сервера укажите document root на
содержимое `build_local` или `build_production`. Не направляйте сервер на
Markdown и JSON исходники.

После запуска проверьте корневую страницу, вложенный маршрут, светлую и тёмную
темы, Network и Console.
