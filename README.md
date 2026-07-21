# Docara

Docara собирает статическую документацию, справочники и небольшие
содержательные сайты. В переносимом режиме Markdown хранит содержание, строгие
JSON-файлы задают настройки, а Simai Framework отвечает за интерфейс.

В репозитории остаются два изолированных режима:

- переносимый JSON + Markdown для новых сайтов;
- legacy Blade/Jigsaw для существующих проектов.

Обычному автору переносимого сайта нужны только PHP и Composer. Node.js и Vite
используют только разработчики исходных ассетов темы.

## Первая переносимая сборка

Portable Docara пока не выпущена как стабильный Composer package. Обычная
команда `composer require simai/docara` устанавливает legacy-линию `1.x`, в
которой параметра `--portable` нет. До отдельного публичного release
используйте принятый immutable GitHub candidate:

```bash
composer init --name=example/docara-site --no-interaction
composer config minimum-stability dev
composer config prefer-stable true
composer config repositories.docara '{"type":"vcs","url":"https://github.com/simai/docara.git","no-api":true}' --json
composer require 'simai/docara:dev-codex/docara-consolidation#0f10afde92b93dd39703823ab22a2920b450a15b' --prefer-source
php vendor/bin/docara init --portable
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Эти команды предназначены для проверки GitHub candidate, а не объявляют
стабильный или production-ready release. После выпуска Docara отдельная
release-инструкция заменит временную VCS-установку точной версией пакета.

После строки `Server started on http://127.0.0.1:8000` откройте этот адрес в
браузере. Вы должны увидеть стартовую страницу Docara. Сервер работает в
текущем терминале; остановите его сочетанием `Ctrl+C`.

Не открывайте HTML через `file://`: так нельзя корректно проверить маршруты,
поиск и ассеты с настроенным `base_url`.

Starter создаёт:

```text
docara.json
redirects.json
simai-framework.lock.json
assets/
content/
```

Одна сборка публикует все локали, объявленные в `docara.json`: у каждой своё
дерево Markdown, URL-префикс, направление письма и явная цепочка fallback.
Версию документации собирайте как отдельный site variant и output под
собственным `base_url`; разные версии не смешиваются скрыто.

Порядок разрешения настроек детерминирован:

```text
встроенные значения
→ docara.json
→ section.json от корня к странице
→ <page>.page.json
→ Markdown как содержание
```

Подробный путь:

- [быстрый старт](docs/site/content/start.md);
- [конфигурация и наследование](docs/site/content/authoring/configuration.md);
- [сборка и проверка](docs/site/content/build.md);
- [публикация](docs/site/content/build/publish.md);
- [контракт переносимого формата](docs/portable-format.md).

## Источник сведений о компонентах

Каждая сборка создаёт `_docara/component-catalog.json` и страницы
`/components/catalog/`. Каталог выводится из точного Framework lock,
проверенных описаний и исполняемых примеров. Он является источником фактической
доступности компонентов для конкретной сборки; README не дублирует их параметры
и ограничения.

Сгенерированные `build_*` и `.docara` нельзя редактировать вручную. Меняйте
Markdown или JSON и повторяйте сборку.

## Legacy Blade/Jigsaw

Обычный `docara init`, `.settings.php`, `config.php`, `source/_core` и
frontend-команды старого проекта относятся только к legacy-режиму. Команда
`init --portable` не преобразует legacy-проект автоматически.

Начать явную миграцию:

- [переход со старого Docara](docs/site/content/migration/legacy.md);
- [переход с docara-template](docs/site/content/migration/template.md);
- [переход с Laravel Mix на Vite](docs/site/content/migration/mix-to-vite.md).

## Проверки репозитория

```bash
php vendor/bin/phpunit
php vendor/bin/pint --test
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

## Границы

Документация описывает проверяемый переносимый контур. Она не заявляет
готовность к production, готовность всех компонентов Simai Framework,
полностью автономную offline-сборку или готовность публичного релиза. Release,
публикация пакета и распространение upstream-ассетов требуют отдельных
проверок и решений владельцев.

## License

MIT
