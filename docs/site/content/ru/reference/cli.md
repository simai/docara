# CLI

Все portable-команды запускаются из корня сайта, где находятся `docara.json`
и `simai-framework.lock.json`.

## `init`

```bash
php vendor/bin/docara init --portable
php vendor/bin/docara init --portable --update
```

Первая команда создаёт starter в пустом каталоге. `--update` добавляет только
отсутствующие starter-файлы и сохраняет существующие Markdown/JSON. Legacy
markers останавливают implicit migration.

## `build`

```text
php vendor/bin/docara build [environment]
```

Environment по умолчанию — `local`; output называется
`build_<environment>`. Для публикационного результата используйте:

```bash
php vendor/bin/docara build production
```

Portable builder создаёт pretty routes детерминированно. Общие CLI options не
расширяют portable content contract.

## `verify-static`

Явный output:

```bash
php vendor/bin/docara verify-static build_production
```

Default output:

```bash
php vendor/bin/docara verify-static
```

Без аргумента проверяется `build_production`. Verifier читает готовый
статический каталог и не выполняет project PHP configuration.

[Полный состав проверки](/build/verify/).

## `serve`

```text
php vendor/bin/docara serve [environment] --host=127.0.0.1 --port=8000 [--no-build]
```

Без `--no-build` команда сначала собирает environment. С `--no-build` она
показывает существующий `build_<environment>` — используйте этот режим после
успешного `verify-static`, чтобы видеть те же bytes.

После строки `Server started on http://127.0.0.1:8000` откройте адрес по HTTP.
Команда блокирует терминал до `Ctrl+C`. `file://` не является проверкой
portable routes.

## Полный первый путь

До публичного release portable-команды проверяются из одного local source
candidate.
Обычный стабильный пакет `1.x` не поддерживает `--portable`.

```bash
cd /path/to/docara
git rev-parse HEAD
composer install
php docara init --portable /path/to/disposable-site
cd /path/to/disposable-site
php /path/to/docara/docara build production
php /path/to/docara/docara verify-static build_production
php /path/to/docara/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

## Legacy-команды

Обычный `init`, `translate`, `source/_core` и frontend-команды старого шаблона
относятся к legacy-контуру. Не добавляйте их в portable quick start и не
считайте обычный `init` миграцией.
