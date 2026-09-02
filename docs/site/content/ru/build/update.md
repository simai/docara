# Обновление Docara без потери сайта

Обычное обновление выполняется из самого проекта одной явно запущенной
командой. Docara не обновляется в фоне и не использует сеть при build или
serve.

## Обычное обновление

```bash
git status --short
php vendor/bin/docara upgrade
```

Docara автоматически:

1. проверяет project-local Composer runtime, PHP, ownership, Framework lock и
   незавершённые транзакции;
2. выбирает максимальную стабильную patch/minor-версию внутри текущего major и
   ограничения из `composer.json`;
3. собирает отдельный кандидат под `.docara/upgrades/<id>/`;
4. запускает кандидатом `doctor`, `validate project`, синхронизацию engine,
   production build и `verify-static` на изолированной копии проекта;
5. повторно сверяет hashes всех входов;
6. заменяет dependencies, engine и verified build только после зелёной
   проверки.

Если любой шаг завершается ошибкой, прежние `composer.lock`, `vendor/`, engine
и последний проверенный build восстанавливаются локально. Markdown, примеры,
assets, настройки, переводы, Smart/design-источники и Framework lock не
редактируются.

## Проверка и точный план

```bash
php vendor/bin/docara upgrade --check --json
php vendor/bin/docara upgrade --to=2.7.0 --dry-run --json
php vendor/bin/docara upgrade --apply=<plan-sha256> --json
```

`--to` принимает только точную стабильную версию `X.Y.Z`. Ветка, `dev`,
`latest`, диапазон или prerelease отклоняются. Версия должна находиться в
текущем major и удовлетворять Composer constraint проекта. Любое изменение
`composer.json`, `composer.lock`, engine, контента, examples, assets, настроек,
Framework lock либо старого verified build делает план stale.

## Rollback без сети

```bash
php vendor/bin/docara upgrade --rollback=latest
# или точный rollback_id из результата apply
php vendor/bin/docara upgrade --rollback=<id>
```

До применения локально сохраняются обе dependency-сборки. Поэтому rollback не
зависит от Packagist или другого внешнего сервиса. Если текущий runtime был
изменён после apply, автоматический rollback останавливается, чтобы не затереть
новую работу.

## Major-версия

Major не применяется автоматически. `upgrade --to=3.0.0` для проекта Docara
2.x возвращает `MAJOR_UPGRADE_REQUIRED`. Сначала нужен отдельный migration
report и явное решение по project-owned изменениям.

## Старый проект без ownership manifest

Старый engine не удаляется и не перемещается автоматически. Если проект уже
содержит документацию, но ещё не имеет `.docara/engine`, один раз создайте
project-local runtime и явно примите package-owned engine:

```bash
composer require simai/docara:^2.0
php vendor/bin/docara capabilities --json
php vendor/bin/docara update --dry-run --adopt --json
php vendor/bin/docara update --apply --json
php vendor/bin/docara update --verify --json
php vendor/bin/docara upgrade --check --json
```

Перед `--apply` просмотрите операции dry-run. Принятие создаёт только
package-owned `.docara/engine`; Markdown, настройки, примеры и assets не
изменяются. Если запустить `upgrade` раньше, Docara возвращает
`UPGRADE_ENGINE_ADOPTION_REQUIRED` с этим же маршрутом. После принятия все
следующие совместимые обновления выполняются через `upgrade`.

## Низкоуровневый `update`

`update` сохранён для синхронизации только package-owned `.docara/engine` из
уже выбранной точной версии по проверяемому ownership manifest:

```bash
php vendor/bin/docara update --verify --json
php vendor/bin/docara update --dry-run --json
php vendor/bin/docara update --apply --json
php vendor/bin/docara update --rollback=latest
```

Он не запускает Composer и не меняет dependency lock. Не редактируйте
`.docara`, `vendor/` или `build_production` вручную. Его `--dry-run` и
`--apply` по-прежнему связаны неизменяемым hash-bound plan.

`init --update` не является сокращением этого процесса: команда отключена и
только подсказывает безопасный `upgrade`/`update` workflow.
