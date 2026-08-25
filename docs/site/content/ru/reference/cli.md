# CLI

Команды запускаются из каталога сайта с `docara.json` и
`simai-framework.lock.json`.

## `init`

```bash
php vendor/bin/docara init [path]
```

Команда создаёт проект только в пустом каталоге. Путь может быть абсолютным или
относительным к текущему каталогу. Если он не передан, используется текущий
каталог. `init --update` намеренно отключён: обновление имеет отдельный
транзакционный контракт.

## `update`

```bash
php vendor/bin/docara update [path] --verify
php vendor/bin/docara update [path] --dry-run
php vendor/bin/docara update [path] --apply
php vendor/bin/docara update [path] --rollback=<id|latest>
```

`--verify` проверяет ownership и сообщает наличие изменений. `--dry-run`
создаёт hash-bound план и показывает точные операции; `--diff` — его alias.
`--apply` применяет только неизменившийся план атомарной заменой engine-owned
state и сохраняет rollback. `--rollback` сначала проверяет manifest и hashes,
затем восстанавливает прежний engine state. `--json` даёт стабильный
machine-readable ответ и exit code; `--adopt` разрешён только вместе с dry-run
для явного принятия старого pre-manifest проекта.

Update никогда не перезаписывает `content/**`, `assets/**`, `docara.json`,
`section.json`, `.page.json`, locale files или consumer-owned `composer.lock`.

## `build`

```text
php vendor/bin/docara build [environment] [--page=/public/url/]
```

Environment по умолчанию — `local`, каталог результата —
`build_<environment>`. Для публикационного результата используйте
`build production`.

После первой полной сборки можно быстро обновить одну существующую страницу:

```bash
php vendor/bin/docara build production --page=/ru/components/badge/
```

Docara проверит accepted full-build receipts, выберет route до компиляции
остальных страниц и сформирует выбранный HTML тем же PageBuilder. Навигация и
поиск остаются из принятой полной сборки. После добавления, удаления или
переименования страниц, изменения меню, `docara.json` либо Framework lock
выполняйте обычную полную сборку без `--page`.

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
mkdir /path/to/docara-engine
cd /path/to/docara-engine
composer require simai/docara:^2.0
php vendor/bin/docara init /path/to/site
cd /path/to/site
php /path/to/docara-engine/vendor/bin/docara build production
php /path/to/docara-engine/vendor/bin/docara verify-static build_production
php /path/to/docara-engine/vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```
