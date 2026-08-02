# Обновление Docara без потери сайта

Docara обновляет только package-owned state. Пользовательский контент,
настройки сайта и dependency lock принадлежат проекту и не перезаписываются.
Операция всегда разделена на проверку, preview, явный apply и rollback.

## 1. Зафиксируйте желаемую версию engine

Команду update запускают из точной новой версии Docara. До выпуска это exact
source checkout:

```bash
cd /path/to/exact-docara-candidate
git rev-parse HEAD
composer install --no-interaction
php docara update /path/to/my-docara --verify
```

После выпуска consumer выбирает точную package-версию средствами Composer и
фиксирует результат в своём `composer.lock`. Moving branch и `latest`
запрещены. Сам `docara update` не меняет Composer dependencies или lock.

## 2. Проверьте текущее состояние

```bash
git status --short
php vendor/bin/docara verify-static build_production
php vendor/bin/docara update --verify
```

Ownership manifest различает:

- engine-owned `.docara/engine/**`;
- project-owned `content/**`, `assets/**`, `docara.json`, redirects,
  `section.json`, `.page.json`, locale files и `composer.lock`;
- generated `build_*/**`, update plan и rollback packages.

Unknown files, локальные изменения engine state, symlinks, ownership conflict
или несовпадающий immutable package/Framework/dependency tuple дают ошибку без
изменения проекта.

## 3. Создайте и прочитайте план

```bash
php vendor/bin/docara update --dry-run
```

Команда записывает hash-bound plan и печатает точные `add`, `replace` и
`delete` только внутри engine-owned state. `--diff` означает то же самое. Для
автоматизации добавьте `--json`.

Не продолжайте, если в плане есть пользовательский путь или непонятная
операция. После preview не меняйте package, Framework lock, plan или текущее
engine state: apply обязан отказаться от stale-плана.

## 4. Примените план

```bash
php vendor/bin/docara update --apply
```

Apply сначала собирает новое состояние во временном каталоге, затем атомарно
заменяет `.docara/engine`. Предыдущее состояние, ownership manifest, Framework
lock и hashes сохраняются в `.docara/rollbacks/<id>/`. Команда сообщает
`rollback_id`.

## 5. Соберите и проверьте сайт

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте главную страницу, вложенное меню, поиск, темы, локали и ключевые
компоненты. Только проверенный каталог переносите в staging по
[сценарию публикации](/build/publish/).

## Rollback

```bash
php vendor/bin/docara update --rollback=latest
# или точный id из apply
php vendor/bin/docara update --rollback=20260802000000-012345abcdef
```

Rollback проверяет manifest, hashes и Framework lock до изменения текущего
state. Повреждённый package fail-closed. После восстановления снова выполните
полную сборку, `verify-static` и HTTP smoke.

`init --update` не является сокращением этого процесса: команда отключена и
только подсказывает безопасный update workflow. Не редактируйте `.docara`,
`vendor/` или `build_production` вручную.
