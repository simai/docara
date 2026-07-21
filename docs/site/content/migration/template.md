# Переход с docara-template

Старый `docara-template` хранит собственные `source/docs`, `config.php`,
`.settings.php`, Codespaces-окружение и Node-настройку. Это второй изменяемый
источник starter и поэтому может расходиться с основным пакетом. Для новой
Docara отдельный вручную поддерживаемый template-репозиторий не нужен:
canonical starter принадлежит пакету `simai/docara`.

## Создайте новый проект

1. создайте пустой каталог и установите immutable portable candidate по
   [командам быстрого старта](/start/); обычный `composer require simai/docara`
   до публичного portable release устанавливает legacy-линию `1.x`;
2. запустите `php vendor/bin/docara init --portable`;
3. перенесите полезный Markdown в `content`;
4. выразите title, description, order и hidden через JSON;
5. не копируйте Mix/Vite scripts, `.settings.php`, `.lang.php` или `source/_core`;
6. выполните PHP-only build и проверку ссылок.

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=localhost --port=8000 --no-build
```

Остановите preview через `Ctrl+C`. Проверьте маршруты и содержание до
переключения hosting.

## Что делать с прежним repository

После консолидации repository template допустим только как автоматически
сгенерированное зеркало canonical starter для кнопки template или Codespaces.
Его содержимое нельзя редактировать независимо. Если зеркало не нужно
потребителям, отдельный repository можно архивировать после zero-reference
scan; удалять историю для завершения миграции не требуется.

Точный maintainer-контракт зеркала описан в
[Starter и template-зеркало](/development/starter-mirror/).
