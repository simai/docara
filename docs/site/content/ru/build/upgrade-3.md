# Переход с 2.x на 3.0

Docara 3.0 — новая major-версия. Команда `docara upgrade` работает только
внутри текущей major-версии: для проекта на 2.x запрос версии 3.0 возвращает
`MAJOR_UPGRADE_REQUIRED`. Переход выполняется вручную по шагам ниже. Markdown,
настройки, переводы и assets проекта при этом не переписываются.

## Что изменилось

### Framework входит в пакет

Docara 3.0 содержит закреплённый релиз SIMAI Framework из публичного
репозитория `simai/ui` (Core из `distr/`, Smart из `distr/smart/` того же
коммита) вместе со средой выполнения Composition Recipe: `ui-82fc0ba882ba` в
версиях 3.0.0–3.0.5, `ui-45384c434d3f` начиная со следующего выпуска. Для сборки
больше не нужны отдельная копия Framework и переменные окружения:
`DOCARA_SIMAI_UI_ROOT` и `SIMAI_UI_ROOT` можно убрать из окружения сборки и
CI. Эти переменные остаются необязательными и нужны только для сборки с другой
поставкой, которая реализует ту же спецификацию Recipe.

Node.js по-прежнему нужен на компьютере сборки: он запускает среду выполнения
Recipe. Если `node` нет в `PATH`, задайте `DOCARA_NODE_BINARY`. Готовый сайт
остаётся статическим.

### Framework lock проекта

- Lock `simai-framework.lock.json`, который поставлялся с Docara 2.18,
  собирается с релизом из пакета без изменений.
- Lock, который называет более старую ревизию Smart, переключает сборку на
  устаревшую переносимую копию Smart. Чтобы собирать сайт с релизом пакета,
  замените lock проекта на поставляемый пакетом:
  `vendor/simai/docara/stubs/portable/simai-framework.lock.json`.

### Компоненты `sf-admin-menu` и `sf-table`

Свободный релиз SIMAI Framework больше не содержит `sf-admin-menu` и
`sf-table`: они входят в пакет Larena. Страницы, которые их используют, нужно
изменить — например, заменить `sf-table` обычной Markdown-таблицей (см.
[Таблица Markdown](/components/table/)).

### Новый проект и командная строка

- `docara init` принимает `--template=minimal|showcase` и `--lang=en|ru`.
  Заготовка по умолчанию — `minimal` на английском: главная страница,
  страница о написании страниц и страница компонентов, один язык, маршруты без
  префикса. Каталог компонентов создаётся командой
  `init . --template=showcase --lang=ru`. Подробнее — в разделе
  [Быстрый старт](/start/).
- `docara` без аргументов и `docara list` без вида выводят список команд,
  `docara --version` — версию. Полный список — в [справочнике CLI](/reference/cli/).

### Результат сборки

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

### Требования

`composer.json` пакета объявляет PHP 8.2+ и расширения `ctype`, `dom`,
`intl`, `json`, `libxml` и `mbstring`. Composer откажется устанавливать
Docara 3.0, если какого-то расширения нет.

## Порядок перехода

Выполняйте шаги в каталоге проекта.

### 1. Сохраните текущее состояние

```bash
git status --short
```

Зафиксируйте или сохраните незавершённые изменения и сохраните последний
проверенный `build_production`, чтобы к нему можно было вернуться.

### 2. Установите Docara 3.0

```bash
composer config repositories.docara vcs https://github.com/simai/docara.git
composer require simai/docara:^3.0 -W
```

Первая команда добавляет в проект репозиторий Docara на GitHub: версия 3
ставится оттуда.

`-W` разрешает Composer обновить зависимости Docara вместе с ней.

### 3. Синхронизируйте engine проекта

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

### 4. Проверьте Framework lock

Посмотрите значение `runtime.pair_id` в `simai-framework.lock.json`:

```bash
php -r 'echo json_decode(file_get_contents("simai-framework.lock.json"), true)["runtime"]["pair_id"], PHP_EOL;'
```

Значения `ui-45384c434d3f` (текущий релиз), `ui-82fc0ba882ba` (Docara
3.0.0–3.0.5) и `ui-4f5e5067afeb-smart-7d932ed51408` (lock Docara 2.18)
собираются с релизом пакета. При любом другом значении замените lock:

```bash
cp vendor/simai/docara/stubs/portable/simai-framework.lock.json simai-framework.lock.json
```

### 5. Найдите удалённые компоненты

```bash
grep -rn "sf-admin-menu\|sf-table" content
```

Измените найденные страницы.

### 6. Уберите переменные Framework

Удалите `DOCARA_SIMAI_UI_ROOT` и `SIMAI_UI_ROOT` из окружения сборки и CI,
если они указывали на копию Framework только ради сборки.

### 7. Соберите и проверьте

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
