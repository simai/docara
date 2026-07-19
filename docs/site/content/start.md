# Быстрый старт

Этот путь начинается в пустом каталоге и заканчивается страницей, открытой по
HTTP. Portable init не создаёт `.env`, не копирует `source/_core` и не
устанавливает Node-зависимости.

## 1. Создайте проект

```bash
mkdir my-docara
cd my-docara
composer require simai/docara
php vendor/bin/docara init --portable
```

Init создаёт starter и не преобразует legacy-проект. Для добавления только
отсутствующих starter-файлов позднее используйте:

```bash
php vendor/bin/docara init --portable --update
```

`--update` сохраняет существующие Markdown и JSON.

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
composer.json
composer.lock
vendor/
docara.json
redirects.json
simai-framework.lock.json
assets/
content/
  _section.json
  index.md
  index.page.json
  landing.md
  landing.page.json
  guides/
```

## Измените страницу

1. Откройте `content/index.md`.
2. Измените заголовок или текст.
3. Повторите `build production`.
4. Повторите `verify-static`.
5. Перезапустите HTTP preview или запустите `serve production` без
   `--no-build`, чтобы сначала пересобрать сайт.

Настройки всего сайта находятся в `docara.json`, раздела — в `_section.json`,
страницы — в соседнем `<page>.page.json`.

Starter задаёт один `default_locale`, одну `documentation_version` и
декларативный `redirects_file`. Для другого языка или версии создайте отдельный
site variant и output, а не смешивайте страницы в одной сборке.

## Что читать дальше

- [Файлы проекта](/authoring/project-files/)
- [Конфигурация](/authoring/configuration/)
- [Наследование и `$reset`](/authoring/inheritance/)
- [Документация и лендинг](/authoring/layout-and-navigation/)
- [Generated catalog компонентов](/components/catalog/)
- [Проверка сборки](/build/verify/)
- [Публикация с rollback](/build/publish/)
