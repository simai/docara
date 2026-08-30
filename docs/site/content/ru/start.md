# Быстрый старт

Этот путь начинается в пустом каталоге и заканчивается страницей, открытой по
HTTP. Для пользовательской сборки достаточно PHP и Composer.

## 1. Создайте проект

Установите стабильный пакет Docara 2 прямо в каталог будущего проекта.

```bash
mkdir /path/to/my-docara
cd /path/to/my-docara
composer require simai/docara:^2.0
php vendor/bin/docara init .
```

Composer фиксирует точную установленную версию в `composer.lock`, а
`vendor/bin/docara` всегда относится именно к этому проекту. `init` допускает
пустой каталог или каталог, где уже находятся только проверенные
`composer.json`, `composer.lock` и `vendor/` с `simai/docara`.

```bash
php vendor/bin/docara capabilities --json
php vendor/bin/docara doctor --json
php vendor/bin/docara build production
```

Для обычного совместимого обновления patch/minor позднее достаточно явно
запустить одну команду:

```bash
php vendor/bin/docara upgrade
```

Команда сама готовит независимый Composer-кандидат, запускает doctor,
validation, engine sync, production build и `verify-static`, повторно проверяет
hashes входов и только затем применяет результат. Вернуться к предыдущей
проверенной версии без сети можно командой
`php vendor/bin/docara upgrade --rollback=latest`. Markdown, examples, assets,
настройки, переводы и Framework lock не редактируются. Для просмотра плана
используйте `upgrade --dry-run --json`; низкоуровневый `update` нужен только
для синхронизации `.docara/engine` уже выбранной точной версии.

Путь может быть абсолютным или относительным к текущему каталогу. Если путь не
указан, `init`, `upgrade` и `update` работают с текущим каталогом.

## 2. Соберите production-каталог

```bash
php vendor/bin/docara build production
```

Результат появится в `build_production`.

## 3. Проверьте статический результат

```bash
php vendor/bin/docara verify-static build_production
```

Продолжайте только после успешного завершения команды. Verifier проверяет
страницы, локальные ссылки и fragments, ассеты, поиск, resolved plans,
component catalog и Framework projection.

## 4. Откройте сайт по HTTP

```bash
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

После строки:

```text
Server started on http://127.0.0.1:8000
```

откройте `http://127.0.0.1:8000` в браузере. Успех виден, если загрузилась
стартовая страница Docara, работают переходы и стили. Сервер занимает текущий
терминал; после проверки нажмите `Ctrl+C`.

Не открывайте `build_production/index.html` через `file://`: такой просмотр не
проверяет pretty routes, `base_url`, поиск и загрузку ассетов.

## Что появилось

```text
docara.json
composer.json
composer.lock
vendor/
redirects.json
simai-framework.lock.json
assets/
.docara/
  engine/
content/
  ru/
    section.json
    index.md
    index.page.json
    landing.md
    landing.page.json
    guides/
```

## Измените страницу

1. Откройте `content/ru/index.md`.
2. Измените заголовок или текст.
3. После уже выполненной полной сборки запустите
   `php vendor/bin/docara build production --page=/ru/`.
4. Повторите `verify-static`.
5. Перезапустите HTTP preview или запустите `serve production` без
   `--no-build`, чтобы сначала пересобрать сайт.

Обычной Markdown-странице JSON-файл не нужен. Настройки всего сайта находятся
в `docara.json`; `section.json` добавляют только для наследуемых настроек
каталога, а соседний `<page>.page.json` — только для настройки одной страницы.
Starter содержит несколько таких файлов как рабочие примеры, а не как
обязательную пару для каждого Markdown-файла.

`--page` предназначен только для изменения существующего Markdown-owner. После
добавления, переименования или удаления `.md`, изменения маршрутизации, меню,
глобальной конфигурации или Framework lock выполните полную
`php vendor/bin/docara build production`.

Starter задаёт `default_locale`, явный реестр `locales`, симметричный
`locale_routing`, одну `documentation_version` и декларативный
`redirects_file`. Для следующего языка добавьте запись в реестр и соседнее
дерево `content/<locale>`. Для
другой версии создайте отдельный site variant и output, а не смешивайте
страницы в одной сборке.

## Что читать дальше

- [Как устроены компоненты](/ru/start/component-model/)
- [Файлы проекта](/authoring/project-files/)
- [Конфигурация](/authoring/configuration/)
- [Брендирование](/authoring/branding/)
- [Мультиязычный сайт](/authoring/multilingual-site/)
- [Наследование и `$reset`](/authoring/inheritance/)
- [Документация и лендинг](/authoring/layout-and-navigation/)
- [Справочник компонентов](/components/)
- [Проверка сборки](/build/verify/)
- [Публикация с rollback](/build/publish/)
- [Обновление Docara](/build/update/)
