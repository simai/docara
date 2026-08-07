# Быстрый старт

Этот путь начинается в пустом каталоге и заканчивается страницей, открытой по
HTTP. Для пользовательской сборки достаточно PHP и Composer.

## 1. Создайте проект

До публичного выпуска Docara 2 используйте точный исходный candidate.

```bash
cd /path/to/docara
git rev-parse HEAD
composer install
php docara init /path/to/my-docara
```

Первая команда фиксирует точный commit локального candidate. Не используйте
старый SHA из документации и не считайте feature branch стабильной версией.
После init выполняйте остальные команды из каталога созданного сайта, указывая
путь к `docara` из того же checkout.

Init создаёт starter только в пустом каталоге. Для безопасного обновления
package-owned engine state позднее используйте отдельный workflow:

```bash
php vendor/bin/docara update --verify
php vendor/bin/docara update --dry-run
# прочитайте список операций
php vendor/bin/docara update --apply
```

Apply разрешён только для неизменившегося hash-bound плана и создаёт проверяемый
rollback package. Вернуться к последнему состоянию можно командой
`php vendor/bin/docara update --rollback=latest`. Project-owned Markdown,
assets, `docara.json`, section/page settings, locale files и consumer-owned
`composer.lock` не являются целями update. Dirty, unknown, conflicting или
symlinked engine state останавливает операцию до записи.

Путь может быть абсолютным или относительным к текущему каталогу. Если путь не
указан, `init` и `update` работают с текущим каталогом. Проверить тот же проект
извне можно командой
`php /path/to/docara/docara update /path/to/my-docara --verify`.

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
