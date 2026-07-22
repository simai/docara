# CLI

Команды запускаются из каталога сайта с `docara.json` и
`simai-framework.lock.json`.

## `init`

```bash
php vendor/bin/docara init [path]
php vendor/bin/docara init --update [path]
```

Первая команда создаёт проект в пустом каталоге. `--update` обновляет
engine-owned файлы starter и сохраняет документированные project-owned файлы.

## `build`

```text
php vendor/bin/docara build [environment]
```

Environment по умолчанию — `local`, каталог результата —
`build_<environment>`. Для публикационного результата используйте
`build production`.

## `verify-static`

```text
php vendor/bin/docara verify-static [build-directory]
```

Без аргумента проверяется `build_production`. Проверка не выполняет проектный
PHP-код: она читает статический каталог, receipts и manifest.

## `serve`

```text
php vendor/bin/docara serve [environment] --host=127.0.0.1 --port=8000 [--no-build]
```

Без `--no-build` сайт сначала собирается. После проверки используйте
`--no-build`, чтобы открыть по HTTP те же байты. Сервер работает до `Ctrl+C`.

## Первый проверяемый запуск

```bash
git rev-parse HEAD
composer install
php docara init /path/to/site
cd /path/to/site
php /path/to/docara/docara build production
php /path/to/docara/docara verify-static build_production
php /path/to/docara/docara serve production --host=127.0.0.1 --port=8000 --no-build
```
