# CLI

## `init`

```bash
php vendor/bin/docara init --portable
php vendor/bin/docara init --portable --update
```

Первая команда создаёт portable starter в пустом каталоге. `--update` добавляет
только отсутствующие starter-файлы и не перезаписывает существующие JSON или
Markdown. Если обнаружен legacy `config.php` или `source/`, implicit migration
отклоняется.

## `build`

```bash
php vendor/bin/docara build [environment]
```

По умолчанию environment равен `local`. Опции `--pretty=true|false` и
`--cache=true|false` относятся к общему CLI; portable output использует
безопасные pretty routes и собственный детерминированный builder.

## `serve`

```bash
php vendor/bin/docara serve [environment] --host=localhost --port=8000 [--no-build]
```

## `verify-static`

```bash
php vendor/bin/docara verify-static [build-directory]
```

Команда проверяет локальные ссылки, manifest результатов, поиск, эффективный
каталог компонентов и точную проекцию ассетов Simai Framework. Если аргумент
не указан, проверяется `build_production`.

## Legacy-команды

`translate`, обычный `init` и frontend-команды из `source/_core` относятся к
legacy-контуру. Не добавляйте их в portable quick start.
