# CLI

Команды запускаются из каталога сайта с `docara.json` и
`simai-framework.lock.json`.

## `init`

```bash
php vendor/bin/docara init [path]
```

Команда создаёт проект в пустом каталоге или в каталоге, где находятся только
проверенные project-local `composer.json`, `composer.lock` и `vendor/` с
`simai/docara`. Путь может быть абсолютным или относительным. `init --update`
намеренно отключён.

## `capabilities`

```bash
php vendor/bin/docara capabilities --json
```

Возвращает точную версию и revision установленной Docara, версию
`docara.ai_contract`, фактические команды и параметры, schemas, типы SDK,
receipts, tracking и update/rollback-возможности. Данные строятся из реального
Application и schema registry; отдельного списка команд для ИИ нет.

## `upgrade`

```bash
php vendor/bin/docara upgrade
php vendor/bin/docara upgrade --check --json
php vendor/bin/docara upgrade --to=2.5.0 --dry-run --json
php vendor/bin/docara upgrade --apply=<plan-sha256> --json
php vendor/bin/docara upgrade --rollback=<id|latest> --json
```

Явно запущенный `upgrade` выбирает и проверяет совместимую стабильную
patch/minor-версию внутри текущего major и Composer constraint. До apply
кандидат проходит doctor, validation, engine sync, production build и
`verify-static`. Ошибка вызывает компенсирующее восстановление прежних lock,
dependencies, engine и verified build. Major, moving reference и stale plan
отклоняются.

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

## Страницы в Developer/AI SDK

```bash
php vendor/bin/docara list page --json
php vendor/bin/docara inspect page /ru/start/ --json
php vendor/bin/docara schema authoring --json
php vendor/bin/docara validate page /ru/start/ --json
php vendor/bin/docara validate project --json
```

Необязательный `docara.authoring.json` назначает страницам один из встроенных
профилей: `landing`, `article`, `tutorial`, `how_to`, `reference` или
`explanation`. Без него прежнее поведение проекта сохраняется. Проверка
сообщает измеримые структурные пробелы и оставляет смысловой checklist со
статусом `review_required`; она не вызывает ИИ и не меняет Markdown.

Новую draft-страницу создают через тот же hash-bound plan/apply:

```bash
php vendor/bin/docara scaffold page guides/new-page \
  --locale=ru --title="Новая страница" --profile=how_to --dry-run --json
php vendor/bin/docara scaffold --apply=<plan-sha256> --json
```

Scaffold работает только для отсутствующего Markdown. Существующую страницу
редактируют напрямую, затем запускают `validate`, build и `verify-static`.

## `documentation`

```bash
php vendor/bin/docara list source --json
php vendor/bin/docara inspect source simai-framework:component.buttons --json
php vendor/bin/docara validate source simai-framework:component.buttons --json
php vendor/bin/docara documentation status --source=simai-framework --kind=component --json
php vendor/bin/docara documentation accept \
  --source=simai-framework --key=component.buttons \
  --route=/ru/components/buttons/ \
  --example=default=components/buttons/basic \
  --review=ai_verified --dry-run --json
php vendor/bin/docara documentation accept --apply=<plan-sha256> --json
```

Необязательный `documentation_tracking` сопоставляет Markdown и общие примеры
с публичным контрактом исходного проекта. `status`, `validate` и build только
читают входы; сборка создаёт `.docara/documentation-status.json`, но не
останавливается из-за редакционных статусов. Изменить
`documentation.lock.json` можно только неизменившимся hash-bound планом.

Source-aware scaffold получает заголовок и измеримую структуру из контракта,
но не сочиняет неизвестный API или пример:

```bash
php vendor/bin/docara scaffold page /ru/components/new-component/ \
  --source=simai-framework --entity=component.new-component \
  --locale=ru --profile=reference --dry-run --json
```

## `translations`

```text
php vendor/bin/docara translations status [--locale=en] [--status=stale] [--json]
php vendor/bin/docara translations accept --locale=en --key=<key> --review=ai_verified --dry-run --json
php vendor/bin/docara translations accept --apply=<plan-sha256> --json
```

`status` только читает исходники и lock-файл. При включённом
`translation_tracking` обычная сборка также создаёт
`.docara/translation-status.json`; найденные проблемы выводятся как
предупреждение и не делают сборку неуспешной. `accept --dry-run` создаёт
проверяемый план, а `--apply` атомарно меняет только указанный lock-файл, если
все входные hashes остались прежними. Для ключа `lang.json` добавьте
`--kind=lang`. Намеренное исключение задаётся через `--exclude-reason` и всегда
требует содержательную причину.

## `serve`

```text
php vendor/bin/docara serve [environment] --host=127.0.0.1 --port=8000 [--no-build]
```

Без `--no-build` сайт сначала собирается. После проверки используйте
`--no-build`, чтобы открыть по HTTP те же байты. Сервер работает до `Ctrl+C`.

## Первый проверяемый запуск

```bash
mkdir /path/to/site
cd /path/to/site
composer require simai/docara:^2.0
php vendor/bin/docara init .
php vendor/bin/docara capabilities --json
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```
