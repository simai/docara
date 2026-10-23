---
description: Обновление Docara одной командой upgrade с проверкой кандидата и откатом без сети, низкоуровневый update и ручной переход с 2.x на 3.0.
tags: [обновление, upgrade, update, откат, rollback, миграция-3]
---

# Обновление Docara

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

Если любой шаг завершается ошибкой, исходные `composer.lock`, `vendor/`, engine
и последний проверенный build восстанавливаются локально. Markdown, примеры,
assets, настройки, переводы, Smart/design-источники и Framework lock не
редактируются.

## Проверка и точный план

```bash
php vendor/bin/docara upgrade --check --json
php vendor/bin/docara upgrade --to=3.6.0 --dry-run --json
php vendor/bin/docara upgrade --apply=<plan-sha256> --json
```

`--to` принимает только точную стабильную версию `X.Y.Z`. Ветка, `dev`,
`latest`, диапазон или prerelease отклоняются. Версия должна находиться в
текущем major и удовлетворять Composer constraint проекта. Любое изменение
`composer.json`, `composer.lock`, engine, контента, examples, assets, настроек,
Framework lock либо старого verified build делает план stale.

## Откат без сети

```bash
php vendor/bin/docara upgrade --rollback=latest
# или точный rollback_id из результата apply
php vendor/bin/docara upgrade --rollback=<id>
```

До применения локально сохраняются обе dependency-сборки. Поэтому rollback не
зависит от Packagist или другого внешнего сервиса. Если текущий runtime был
изменён после apply, автоматический rollback останавливается, чтобы не затереть
новую работу.

## Новая major-версия

Major не применяется автоматически: `upgrade` работает только внутри текущей
major-версии. Запрос другой major, например `upgrade --to=3.0.0` для проекта
на Docara 2.x, возвращает `MAJOR_UPGRADE_REQUIRED`. Порядок ручного перехода
описан ниже, в разделе [«Переход с 2.x на 3.0»](#переход-с-2-x-на-3-0).

## Старый проект без ownership manifest

Старый engine не удаляется и не перемещается автоматически. Если проект уже
содержит документацию, но ещё не имеет `.docara/engine`, один раз создайте
project-local runtime и явно примите package-owned engine:

```bash
composer require simai/docara:^3.0
php vendor/bin/docara capabilities --json
php vendor/bin/docara update --dry-run --adopt --json
php vendor/bin/docara update --apply --json
php vendor/bin/docara update --verify --json
php vendor/bin/docara upgrade --check --json
```

Перед `--apply` просмотрите операции dry-run. Принятие создаёт только
package-owned `.docara/engine`; Markdown, настройки, примеры и assets не
изменяются. Если запустить `upgrade` до принятия, Docara возвращает
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

`update` без флага действия выполняет `--verify`. Он не запускает Composer и
не меняет dependency lock. Не редактируйте
`.docara`, `vendor/` или `build_production` вручную. Его `--dry-run` и
`--apply` связаны неизменяемым hash-bound plan.

`init --update` не является сокращением этого процесса: команда отключена,
завершается ошибкой и подсказывает порядок `update --verify`, затем
`--dry-run`, затем явный `--apply`.

## Переход с 2.x на 3.0

Docara 3.0 — новая major-версия, поэтому проект на 2.x переводится вручную по
шагам ниже. Markdown, настройки, переводы и assets проекта при этом не
переписываются.

### Что изменилось

#### Framework входит в пакет

Docara 3.0 содержит закреплённый релиз SIMAI Framework из публичного
репозитория `simai/ui` (Core из `distr/`, Smart из `distr/smart/` того же
коммита) вместе со средой выполнения Composition Recipe: `ui-82fc0ba882ba` в
версиях 3.0.0–3.0.5, `ui-25beeb814ae8` в версиях 3.1.0–3.5.0,
`ui-32c7a64e4575` начиная с 3.6.0. Для сборки
больше не нужны отдельная копия Framework и переменные окружения:
`DOCARA_SIMAI_UI_ROOT` и `SIMAI_UI_ROOT` можно убрать из окружения сборки и
CI. Эти переменные остаются необязательными и нужны только для сборки с другой
поставкой, которая реализует ту же спецификацию Recipe.

Node.js 20+ по-прежнему нужен на компьютере сборки: он запускает среду выполнения
Recipe. Если `node` нет в `PATH`, задайте `DOCARA_NODE_BINARY`. Готовый сайт
остаётся статическим.

#### Framework lock проекта

- Lock `simai-framework.lock.json`, который поставлялся с Docara 2.18,
  собирается с релизом из пакета без изменений.
- Lock, который называет более старую ревизию Smart, переключает сборку на
  устаревшую переносимую копию Smart. Чтобы собирать сайт с релизом пакета,
  замените lock проекта на поставляемый пакетом:
  `vendor/simai/docara/stubs/portable/simai-framework.lock.json`.

#### Компоненты `sf-admin-menu` и `sf-table`

Свободный релиз SIMAI Framework больше не содержит `sf-admin-menu` и
`sf-table`: они перешли в коммерческий пакет компонентов SIMAI. Страницы, которые их используют, нужно
изменить — например, заменить `sf-table` обычной Markdown-таблицей (см.
[Таблица Markdown](/ru/authoring/markdown/#таблицы)).

#### Новый проект и командная строка

- `docara init` принимает `--template=minimal|showcase` и `--lang=en|ru`.
  Заготовка по умолчанию — `minimal` на английском: главная страница,
  страница о написании страниц и страница компонентов, один язык, маршруты без
  префикса. Каталог компонентов создаётся командой
  `init . --template=showcase --lang=ru`. Подробнее — в разделе
  [Быстрый старт](/ru/start/quick-start/).
- `docara` без аргументов и `docara list` без вида выводят список команд,
  `docara --version` — версию. Полный список — в [справочнике CLI](/reference/cli/).

#### Результат сборки

- Общие ресурсы публикуются один раз в `<base_url>_docara/`, а не повторно
  под каждым языковым префиксом вроде `/ru/_docara/`.
- Встроенные строки, которые Docara пишет в страницы (заголовки таблиц
  справочника схем, текст индекса Atlas, заголовок `internal_preview` по
  умолчанию, подпись примера в заготовке), следуют языку страницы: русские
  для `ru`, английские для остальных языков. На сайтах не на русском языке
  эти строки стали английскими.
- Метаданные каталога компонентов (`_docara/component-catalog.json`, поле
  `metadata` записей) содержат `release` — версию Docara — и ссылки
  `source_url` и `history_url` на тег выпуска. Поля `revision`, `author` и
  `changed_at` убраны. Если внешний инструмент читал эти поля, обновите его.

#### Требования

`composer.json` пакета объявляет PHP 8.2+ и расширения `ctype`, `dom`,
`intl`, `json`, `libxml` и `mbstring`. Composer откажется устанавливать
Docara 3.0, если какого-то расширения нет.

### Порядок перехода

Выполняйте шаги в каталоге проекта.

#### 1. Сохраните текущее состояние

```bash
git status --short
```

Зафиксируйте или сохраните незавершённые изменения и сохраните последний
проверенный `build_production`, чтобы к нему можно было вернуться.

#### 2. Установите Docara 3.0

```bash
composer require simai/docara:^3.0 -W
```

Первая команда добавляет в проект репозиторий Docara на GitHub: версия 3
ставится оттуда.

`-W` разрешает Composer обновить зависимости Docara вместе с ней.

#### 3. Синхронизируйте engine проекта

```bash
php vendor/bin/docara update --verify
php vendor/bin/docara update --dry-run
php vendor/bin/docara update --apply
```

`--verify` проверяет ownership, `--dry-run` показывает точные операции, а
`--apply` атомарно обновляет package-owned `.docara/engine` по этому плану.
Контент, `docara.json`, настройки разделов и страниц не меняются. Если проект
ещё не имеет ownership manifest, запустите dry-run с `--adopt` — см.
[Обновление Docara](/build/update/).

#### 4. Проверьте Framework lock

Посмотрите значение `runtime.pair_id` в `simai-framework.lock.json`:

```bash
php -r 'echo json_decode(file_get_contents("simai-framework.lock.json"), true)["runtime"]["pair_id"], PHP_EOL;'
```

Значения `ui-32c7a64e4575` (текущий релиз), `ui-25beeb814ae8` (Docara
3.1.0–3.5.0), `ui-82fc0ba882ba` (Docara 3.0.0–3.0.5) и
`ui-4f5e5067afeb-smart-7d932ed51408` (lock Docara 2.18)
собираются с релизом пакета. При любом другом значении замените lock:

```bash
cp vendor/simai/docara/stubs/portable/simai-framework.lock.json simai-framework.lock.json
```

#### 5. Найдите удалённые компоненты

```bash
grep -rn "sf-admin-menu\|sf-table" content
```

Измените найденные страницы.

#### 6. Уберите переменные Framework

Удалите `DOCARA_SIMAI_UI_ROOT` и `SIMAI_UI_ROOT` из окружения сборки и CI,
если они указывали на копию Framework только ради сборки.

#### 7. Соберите и проверьте

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Откройте `http://127.0.0.1:8000`, проверьте главную и вложенную страницы,
поиск, переключение темы и страницы с компонентами. Затем опубликуйте
каталог по разделам [Размещение на хостинге](/build/hosting/) и
[Публикация и rollback](/build/publish/).

Следующие совместимые обновления внутри 3.x выполняет команда
`php vendor/bin/docara upgrade`.

## См. также

- [Публикация и откат](/ru/build/publish/)
- [Коды ошибок обновления](/ru/reference/errors/upgrade/)
- [Команда upgrade](/ru/reference/cli/)
