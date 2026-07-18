# Переход с docara-template

Старый `docara-template` хранит собственные `source/docs`, `config.php`,
`.settings.php`, Codespaces-окружение и Node-настройку. Это второй изменяемый
источник starter и поэтому может расходиться с основным пакетом.

Для нового portable-проекта:

1. создайте пустой каталог и выполните `composer require simai/docara`;
2. запустите `php vendor/bin/docara init --portable`;
3. перенесите полезный Markdown в `content`;
4. выразите title, description, order и hidden через JSON;
5. не копируйте Mix/Vite scripts, `.settings.php`, `.lang.php` или `source/_core`;
6. выполните PHP-only build и проверку ссылок.

После консолидации отдельный repository template допустим только как
автоматически сгенерированное зеркало canonical starter.
