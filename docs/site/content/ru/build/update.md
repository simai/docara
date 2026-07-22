# Обновление Docara без потери сайта

Обновление состоит из трёх независимых действий: Composer меняет package,
`docara init --update` обновляет engine-owned starter-файлы, новая сборка
создаёт проверяемый static output. Пользовательские Markdown и JSON не должны
перезаписываться автоматически.

## 1. Зафиксируйте исходное состояние

В Git-проекте сначала убедитесь, что понятны все текущие изменения:

```bash
git status --short
php vendor/bin/docara verify-static build_production
```

Создайте отдельную backup-ветку или commit средствами вашего Git workflow.
Дополнительно сохраните `composer.json`, `composer.lock`, `docara.json`,
`content`, `languages`, `assets` и текущий опубликованный static-каталог вне
рабочего каталога. Не продолжайте, если не можете назвать путь восстановления.

## 2. Обновите source candidate

До публичного выпуска Docara 2 используйте тот же локальный checkout, что и в
быстром старте:

```bash
cd /path/to/docara
git rev-parse HEAD
composer install
```

Запишите SHA и не меняйте checkout между init, build и verify. Release-инструкция
в будущем заменит local source candidate точной опубликованной версией.

## 3. Добавьте только отсутствующие starter-файлы

```bash
php vendor/bin/docara init --update
```

Команда сохраняет каждый существующий файл. Она не обновляет старый
`docara.json`, не переписывает Markdown и не выполняет миграцию schema. Если в
новой Docara изменился canonical starter, сравните проект с
`vendor/simai/docara/stubs/portable` и перенесите нужные изменения вручную.

## 4. Соберите кандидат и проверьте его

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте главную страницу, вложенное меню, поиск, темы, локали и ключевые
компоненты. Только проверенный каталог переносите в staging и переключайте по
[сценарию публикации](/build/publish/).

## Rollback

Если package или сборка не прошли проверку:

1. верните сохранённые `composer.json` и `composer.lock`;
2. выполните `composer install --no-interaction`;
3. верните сохранённые пользовательские файлы только если вы меняли их вручную;
4. восстановите прежний static-каталог или переключите hosting на его backup;
5. повторите `verify-static` и HTTP smoke для восстановленной версии.

Не лечите проблему редактированием `vendor/` или `build_production`: такие
изменения исчезнут при следующей установке или сборке.
