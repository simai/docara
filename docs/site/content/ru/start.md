# Быстрый старт

Этот путь начинается в пустом каталоге и заканчивается страницей, открытой по
HTTP. Portable init не создаёт `.env`, не копирует `source/_core` и не
устанавливает Node-зависимости.

## 1. Создайте проект

Portable Docara пока доступна как принятый GitHub candidate. Стабильная
Composer-линия `simai/docara` версии `1.x` относится к legacy Blade/Jigsaw и
не содержит `--portable`.

```bash
mkdir my-docara
cd my-docara
composer init --name=example/docara-site --no-interaction
composer config minimum-stability dev
composer config prefer-stable true
composer config repositories.docara '{"type":"vcs","url":"https://github.com/simai/docara.git","no-api":true}' --json
composer require 'simai/docara:dev-codex/docara-consolidation#0f10afde92b93dd39703823ab22a2920b450a15b' --prefer-source
php vendor/bin/docara init --portable
```

Exact commit нужен только до отдельного публичного release. Он позволяет
проверять один принятый candidate и не превращает feature branch в стабильную
версию пакета.

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
3. Повторите `build production`.
4. Повторите `verify-static`.
5. Перезапустите HTTP preview или запустите `serve production` без
   `--no-build`, чтобы сначала пересобрать сайт.

Обычной Markdown-странице JSON-файл не нужен. Настройки всего сайта находятся
в `docara.json`; `section.json` добавляют только для наследуемых настроек
каталога, а соседний `<page>.page.json` — только для настройки одной страницы.
Starter содержит несколько таких файлов как рабочие примеры, а не как
обязательную пару для каждого Markdown-файла.

Starter задаёт `default_locale`, явный реестр `locales`, симметричный
`locale_routing`, одну `documentation_version` и декларативный
`redirects_file`. Для следующего языка добавьте запись в реестр и соседнее
дерево `content/<locale>`. Для
другой версии создайте отдельный site variant и output, а не смешивайте
страницы в одной сборке.

## Что читать дальше

- [Файлы проекта](/authoring/project-files/)
- [Конфигурация](/authoring/configuration/)
- [Брендирование](/authoring/branding/)
- [Мультиязычный сайт](/authoring/multilingual-site/)
- [Наследование и `$reset`](/authoring/inheritance/)
- [Документация и лендинг](/authoring/layout-and-navigation/)
- [Generated catalog компонентов](/components/catalog/)
- [Проверка сборки](/build/verify/)
- [Публикация с rollback](/build/publish/)
- [Обновление Docara](/build/update/)
