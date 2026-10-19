# CLI

Команды запускаются из каталога сайта с `docara.json` и
`simai-framework.lock.json` через project-local `php vendor/bin/docara`.
`docara` без аргументов и `docara list` без вида выводят список команд,
`docara --version` — установленную версию. Общие параметры Symfony Console
(`--help`, `--quiet`, `--verbose` и другие) на этой странице не перечислены.

Параметр `--json` у команд Developer/AI SDK возвращает стабильный
machine-readable результат операции и exit code.

## `init`

```bash
php vendor/bin/docara init [path] [--template=minimal|showcase] [--lang=en|ru]
```

Создаёт проект в пустом каталоге или в каталоге, где находятся только
проверенные project-local `composer.json`, `composer.lock` и `vendor/` с
`simai/docara`. Путь может быть абсолютным или относительным; по умолчанию
используется текущий каталог.

- `--template` (`-t`) — заготовка: `minimal` (по умолчанию; главная страница,
  страница о написании страниц и страница компонентов) или `showcase`
  (каталог компонентов, лендинг, вложенные руководства и проектные
  Smart-примеры).
- `--lang` (`-l`) — язык содержимого заготовки: `en` (по умолчанию) или `ru`.
  `showcase` доступна только с `--lang=ru`; сочетание с `--lang=en`
  завершается ошибкой и ничего не записывает.
- `--update` (`-u`) — защитный флаг: команда отказывается и подсказывает
  явный порядок `upgrade`/`update`.

## `build`

```text
php vendor/bin/docara build [environment] [--page=/public/url/]
```

Environment по умолчанию — `local`, каталог результата —
`build_<environment>`. Для публикационного результата используйте
`build production`. Сборка атомарна: при ошибке предыдущий результат
сохраняется.

`--page` быстро обновляет одну существующую страницу после первой полной
сборки:

```bash
php vendor/bin/docara build production --page=/ru/components/badge/
```

Docara проверит accepted full-build receipts, выберет route до компиляции
остальных страниц и сформирует выбранный HTML тем же PageBuilder. Навигация и
поиск остаются из принятой полной сборки. После добавления, удаления или
переименования страниц, изменения меню, `docara.json` либо Framework lock
выполняйте обычную полную сборку без `--page`.

Если в `docara.json` задан `site_url`, сборка также пишет `sitemap.xml`,
`robots.txt` и `llms.txt`, абсолютные `canonical`/`hreflang` и теги Open Graph;
см. [Настройки сайта](/settings/site/).

## `verify-static`

```text
php vendor/bin/docara verify-static [directory] [--receipts=DIR]
```

Без аргумента проверяется `build_production`. Квитанции сборки читаются из
соседнего каталога `<directory>.docara/`; `--receipts` указывает их каталог
явно, например для копии, уже размещённой на хостинге. Проверка не выполняет проектный
PHP-код: она читает статический каталог, receipts и manifest и выводит JSON с
`html_pages`, `local_references_checked` и списком `broken`. Подробности — в
разделе [Проверка статического результата](/build/verify/).

Для сайта с `site_url` проверка также читает `sitemap.xml`, `robots.txt` и
`llms.txt`: файлы корректны, каждый адрес лежит под `site_url`, каждый адрес
sitemap ведёт на собранную индексируемую страницу и каждая такая страница есть
в sitemap. Ошибки выводятся с `reference: "@discovery-files"`.

## `portal-artifact`

```text
php vendor/bin/docara portal-artifact [environment] --release=TAG --repository=OWNER/NAME --commit=SHA [--product-version=VERSION] [--output=DIR]
```

Упаковывает портальную сборку (`docara.json` с секцией `portal`) из
`build_<environment>` в архив `<product>-<version>-portal.tar.gz`, его
`.sha256` и квитанцию `simai.io.portal-artifact`. Без аргумента читается
`build_production`, без `--output` файлы пишутся в
`build_<environment>.portal/`. Перед записью команда проверяет каждую страницу:
язык, заголовок, описание, `canonical` под `site_url`, ссылку на главную
портала и одну пару Framework. Подробности — в разделе
[Сборка для портала](/build/portal/).

## `serve`

```text
php vendor/bin/docara serve [environment] [--host=localhost] [--port=8000] [--no-build]
```

Собирает выбранное окружение и запускает встроенный PHP-сервер. `--host`
задаёт адрес (по умолчанию `localhost`), `--port` (`-p`) — порт (по умолчанию
`8000`). С `--no-build` сервер открывает уже собранный и проверенный каталог
без пересборки. Сервер работает до `Ctrl+C`.

## `capabilities`

```bash
php vendor/bin/docara capabilities [--json]
```

Возвращает точную версию и revision установленной Docara, версию
`docara.ai_contract`, фактические команды и параметры, schemas, типы SDK,
receipts, tracking и update/rollback-возможности. Данные строятся из реального
Application и schema registry; отдельного списка команд для ИИ нет.
`--json` выводит результат в JSON.

## `doctor`

```bash
php vendor/bin/docara doctor [--json]
```

Проверяет действующие контракты проекта: конфигурацию, реестры Smart,
design-артефактов и bindings, каталог schemas — и сообщает их fingerprints.
`--json` выводит результат в JSON.

## `upgrade`

```bash
php vendor/bin/docara upgrade [path]
php vendor/bin/docara upgrade --check --json
php vendor/bin/docara upgrade --to=3.0.1 --dry-run --json
php vendor/bin/docara upgrade --apply=<plan-sha256> --json
php vendor/bin/docara upgrade --rollback=<id|latest> --json
```

Явно запущенный `upgrade` выбирает и проверяет совместимую стабильную
patch/minor-версию внутри текущего major и Composer constraint. До apply
кандидат проходит doctor, validation, engine sync, production build и
`verify-static`. Ошибка вызывает компенсирующее восстановление исходных lock,
dependencies, engine и verified build.

- `--check` — проверить, доступно ли совместимое обновление;
- `--to` — точная стабильная целевая версия `X.Y.Z`;
- `--dry-run` — подготовить и сохранить неизменяемый проверенный план;
- `--apply` — применить план по его SHA-256, если входы не изменились;
- `--rollback` — восстановить применённое обновление по id или `latest` без
  сети;
- `--json` — результат в JSON.

Major, moving reference и stale plan отклоняются; запрос другой major
возвращает `MAJOR_UPGRADE_REQUIRED`. Переход между major описан на странице
[Переход с 2.x на 3.0](/build/upgrade-3/).

## `update`

```bash
php vendor/bin/docara update [path] --verify
php vendor/bin/docara update [path] --dry-run
php vendor/bin/docara update [path] --apply
php vendor/bin/docara update [path] --rollback=<id|latest>
```

Синхронизирует package-owned `.docara/engine` с установленной версией.

- `--verify` проверяет ownership и сообщает наличие изменений;
- `--dry-run` создаёт hash-bound план и показывает точные операции; `--diff` —
  его alias;
- `--apply` применяет только неизменившийся план атомарной заменой
  engine-owned state и сохраняет rollback;
- `--rollback` сначала проверяет manifest и hashes, затем восстанавливает
  предыдущий engine state;
- `--adopt` разрешён только вместе с dry-run для явного принятия проекта без
  ownership manifest;
- `--json` даёт стабильный machine-readable ответ и exit code.

Update никогда не перезаписывает `content/**`, `assets/**`, `docara.json`,
`section.json`, `.page.json`, locale files или consumer-owned `composer.lock`.

## `list`

```bash
php vendor/bin/docara list [kind] [--json]
```

Без вида выводит список команд. С видом перечисляет зарегистрированные
объекты SDK: `page`, `source`, `smart`, `binding`, `layout`, `view`,
`section`, `block`, `provider`, `fixture`, `state` или `schema`. Вид `page`
показывает физические Markdown-страницы. `--json` выводит результат в JSON.

```bash
php vendor/bin/docara list page --json
```

## `inspect`

```bash
php vendor/bin/docara inspect <kind> <id> [--json]
```

Показывает один зарегистрированный объект вместе с ownership и provenance.
Вид: `page`, `source`, `smart`, `binding`, `layout`, `view`, `section`,
`block`, `provider`, `schema`, `fixture` или `state`. `--json` выводит
результат в JSON.

```bash
php vendor/bin/docara inspect page /ru/start/ --json
php vendor/bin/docara inspect source simai-framework:component.buttons --json
```

## `schema`

```bash
php vendor/bin/docara schema <kind> [--json]
```

Показывает действующую JSON Schema поверхности SDK: `smart`, `layout`,
`view`, `section`, `block`, `binding`, `authoring`, `documentation-source`,
`documentation-tracking`, `capabilities`, `upgrade-plan`, `upgrade-result`,
`upgrade-journal` или имя файла `*.schema.json`. `--json` выводит результат в
JSON.

## `validate`

```bash
php vendor/bin/docara validate <kind> [id] [--json]
```

Проверяет проект, страницу, источник документации, Smart или design-артефакт
теми же production-сервисами, что и сборка. Вид: `project`, `page`,
`source`, `smart`, `layout`, `view`, `section` или `block`. `--json` выводит
результат в JSON.

```bash
php vendor/bin/docara validate project --json
php vendor/bin/docara validate page /ru/start/ --json
```

Необязательный `docara.authoring.json` назначает страницам один из встроенных
профилей: `landing`, `article`, `tutorial`, `how_to`, `reference` или
`explanation`. Без него проверка профиля не применяется. Проверка сообщает
измеримые структурные пробелы и оставляет смысловой checklist со статусом
`review_required`; она не вызывает ИИ и не меняет Markdown.

## `scaffold`

```bash
php vendor/bin/docara scaffold <kind> <id> [--locale=ru] [--title="..."] \
  [--profile=how_to] [--source=<id>] [--entity=<key>] --dry-run [--json]
php vendor/bin/docara scaffold --apply=<plan-sha256> [--json]
```

Создаёт hash-bound план заготовки и применяет его только явно. Вид: `smart`,
`design` или `page`; `id` — id артефакта или locale-relative route страницы.

- `--locale` — настроенная локаль для страницы;
- `--title` — заголовок страницы;
- `--profile` — встроенный профиль страницы;
- `--source` — id настроенного источника документации;
- `--entity` — стабильный ключ сущности источника;
- `--dry-run` — создать детерминированный план для проверки;
- `--apply` — применить точный план по SHA-256 после повторной проверки входов;
- `--json` — результат в JSON.

```bash
php vendor/bin/docara scaffold page guides/new-page \
  --locale=ru --title="Новая страница" --profile=how_to --dry-run --json
php vendor/bin/docara scaffold --apply=<plan-sha256> --json
```

Scaffold работает только для отсутствующего Markdown. Существующую страницу
редактируют напрямую, затем запускают `validate`, build и `verify-static`.
Source-aware scaffold получает заголовок и измеримую структуру из контракта,
но не сочиняет неизвестный API или пример:

```bash
php vendor/bin/docara scaffold page /ru/components/new-component/ \
  --source=simai-framework --entity=component.new-component \
  --locale=ru --profile=reference --dry-run --json
```

## `documentation`

```bash
php vendor/bin/docara documentation status [--source=<id>] [--kind=<kind>] [--status=<status>] [--json]
php vendor/bin/docara documentation accept --source=<id> --key=<key> \
  --route=<route> --example=<case>=<example-id> \
  --review=ai_verified|human_reviewed --dry-run [--json]
php vendor/bin/docara documentation accept --apply=<plan-sha256> [--json]
```

Действие: `status` или `accept`. Необязательный `documentation_tracking`
сопоставляет Markdown и общие примеры с публичным контрактом исходного
проекта.

- `--source` — id настроенного источника документации;
- `--kind` — фильтр по виду сущности;
- `--status` — фильтр отчёта по статусу;
- `--key` — стабильный ключ сущности источника;
- `--route` — публичный route страницы в исходной локали;
- `--example` — соответствие обязательного случая примеру `case=example/id`,
  параметр можно повторять;
- `--review` — `ai_verified` (по умолчанию) или `human_reviewed`;
- `--exclude-reason` — намеренно исключить сущность с содержательной причиной;
- `--dry-run` — создать hash-bound план принятия;
- `--apply` — применить точный план по SHA-256;
- `--json` — результат в JSON.

`status`, `validate` и build только читают входы; сборка создаёт квитанцию
`build_<environment>.docara/documentation-status.json`, но не останавливается из-за редакционных
статусов. Изменить `documentation.lock.json` можно только неизменившимся
hash-bound планом.

```bash
php vendor/bin/docara documentation status --source=simai-framework --kind=component --json
php vendor/bin/docara documentation accept \
  --source=simai-framework --key=component.buttons \
  --route=/ru/components/buttons/ \
  --example=default=components/buttons/basic \
  --review=ai_verified --dry-run --json
```

## `translations`

```text
php vendor/bin/docara translations status [--locale=en] [--status=stale] [--kind=page|lang] [--json]
php vendor/bin/docara translations accept --locale=en --key=<key> --review=ai_verified --dry-run --json
php vendor/bin/docara translations accept --locale=en [--key=<key> ...] [--prefix=<prefix>] [--status=<status>] [--kind=page|lang] --dry-run --json
php vendor/bin/docara translations accept --apply=<plan-sha256> --json
```

Действие: `status` или `accept`.

- `--locale` — целевая локаль;
- `--status` — фильтр отчёта по статусу; для `accept` — принять всё, что
  показывает такой отфильтрованный отчёт;
- `--kind` — `page` (по умолчанию) или `lang` для ключей `lang.json`;
- `--key` — ключ перевода; повторите, чтобы принять набор ключей;
- `--prefix` — принять все `unverified` и `stale` ключи, начинающиеся с
  префикса, например раздел `guide/`;
- `--review` — `ai_verified` (по умолчанию) или `human_reviewed`;
- `--exclude-reason` — намеренно исключить пару локаль/ключ; всегда требует
  содержательную причину;
- `--dry-run` — создать проверяемый план принятия;
- `--apply` — атомарно применить точный план по SHA-256;
- `--json` — результат в JSON.

Один `--key` без `--prefix` и `--status` создаёт прежний план для одного
ключа (с ним работает `--exclude-reason`). Несколько ключей, префикс или
статус создают план набора: в `accepted` — принимаемые `unverified` и `stale`
пункты, в `skipped` — пункты выборки в других статусах, которые не меняются.
Пустая выборка — ошибка `TRANSLATION_ACCEPT_SELECTION_EMPTY`.

При `translation_tracking.mode: "publish-current"` сборка публикует только
актуальные переводы и выводит список задержанных страниц и строк; см.
[Мультиязычный сайт](/authoring/multilingual-site/).

`status` только читает исходники и lock-файл. При включённом
`translation_tracking` обычная сборка также создаёт квитанцию
`build_<environment>.docara/translation-status.json`; найденные проблемы выводятся как
предупреждение и не делают сборку неуспешной. `--apply` меняет только
указанный lock-файл и только если все входные hashes совпадают с планом.

## `atlas`

```bash
php vendor/bin/docara atlas [--json]
```

Показывает детерминированный Design Atlas, построенный из допущенных
реестров: design-артефакты, Smart-компоненты с их presets и bindings с их
представлениями.
`--json` выводит результат в JSON.

## `preview`

```bash
php vendor/bin/docara preview <target> [--page=/route/] [--selector=<id>] [--json]
php vendor/bin/docara preview <target> --watch [--interval=250] [--max-cycles=0]
```

Рендерит ограниченный preview по production-пути. `target`: `smart`,
`region`, `layout` или `page`.

- `--page` — публичный route, который задаёт production-контекст;
- `--selector` — id Smart-компонента или имя области для узкой цели;
- `--json` — результат в JSON;
- `--watch` — следить за зависимостями цели средствами только PHP;
- `--interval` — интервал проверки в миллисекундах (по умолчанию `250`);
- `--max-cycles` — предел циклов наблюдения; `0` — до прерывания.

## `test`

```bash
php vendor/bin/docara test <kind> <id> [--page=/route/] [--json]
```

Проверяет и рендерит одну объявленную fixture Smart-компонента или layout
через PreviewKernel. `kind`: `smart` или `layout`. `--page` задаёт публичный
route для production-контекста, `--json` выводит результат в JSON.

## `qa`

```bash
php vendor/bin/docara qa [kind] [id] [--page=/route/] --dry-run [--json]
php vendor/bin/docara qa --finalize-reference=<draft> [--json]
php vendor/bin/docara qa --verify=<plan-sha256> [--json]
```

Планирует или проверяет необязательную браузерную, a11y и визуальную проверку
на изолированном preview по production-пути. `kind`: `smart`, `region` или
`layout`.

- `--page` — публичный route страницы для проверки;
- `--dry-run` — опубликовать preview и создать детерминированный план
  сценариев;
- `--finalize-reference` — проверить записанный черновик эталона и привязать
  его manifest к новому неизменяемому плану;
- `--verify` — проверить внешний QA-отчёт по SHA-256 плана;
- `--json` — результат в JSON.

## Первый проверяемый запуск

```bash
mkdir /path/to/site
cd /path/to/site
composer require simai/docara:^3.0
php vendor/bin/docara init . --lang=ru
php vendor/bin/docara capabilities --json
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```
