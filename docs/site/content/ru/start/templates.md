---
description: Заготовки minimal и showcase команды init, какие файлы они создают, что нужно в окружении сборки и как проверить установку.
tags: [init, заготовка, шаблон]
---

# Заготовки и окружение

Команда `init` из [быстрого старта](/ru/start/quick-start/) создаёт проект из
заготовки. Здесь описано, какую заготовку выбрать, что появится в каталоге и
что должно быть в окружении сборки.

## Выбор заготовки

`init` принимает два параметра: `--template` (`-t`) выбирает заготовку, а
`--lang` (`-l`) — язык её содержимого. По умолчанию используется
`--template=minimal --lang=en`: небольшой рабочий сайт на английском. Заготовку
выбирают при первом `init`
([шаг 1 быстрого старта](/ru/start/quick-start/#1-создайте-проект)): в уже созданном проекте `init`
откажется работать.

```bash
# Небольшой сайт на русском
php vendor/bin/docara init . --lang=ru

# Каталог компонентов, лендинг, вложенные руководства и Smart-примеры
php vendor/bin/docara init . --template=showcase --lang=ru
```

| Заготовка | Содержимое | Языки |
| --- | --- | --- |
| `minimal` | главная страница, страница о написании страниц (Markdown, код, таблица, `:::example`) и страница компонентов (alert, button, JSON-компонент `:::ui.button`) | `en`, `ru` |
| `showcase` | каталог компонентов, лендинг, вложенные руководства и проектные Smart-примеры | `ru` |

Заготовка `showcase` написана только на русском. Сочетание
`--template=showcase --lang=en` завершается понятной ошибкой и ничего не
записывает.

## Окружение сборки

Docara содержит закреплённую поставку SIMAI Framework вместе со средой
выполнения Composition Recipe. Отдельная копия Framework и переменные
окружения для неё не нужны.

Node.js должен быть доступен командой `node`. Если он установлен в другом
месте, задайте `export DOCARA_NODE_BINARY=/path/to/node` в окружении сборки,
включая автоматические проверки и обновление Docara.

Проверить установку без сборки можно двумя командами: `capabilities` сообщает
версию и возможности установленной Docara, `doctor` — состояние контрактов
проекта.

```bash
php vendor/bin/docara capabilities --json
php vendor/bin/docara doctor --json
```

## Обновление

Docara обновляется одной командой `php vendor/bin/docara upgrade`, а
возвращается к прежней проверенной версии командой
`php vendor/bin/docara upgrade --rollback=latest`. Как это работает и что
проверяется, описано в разделе [«Обновление Docara»](/ru/build/update/).

## Какие файлы создаёт заготовка

Заготовка `minimal` с `--lang=ru` создаёт такие файлы:

```text
docara.json
composer.json
composer.lock
vendor/
redirects.json
simai-framework.lock.json
assets/
contracts/
.docara/
  engine/
content/
  ru/
    section.json
    lang.json
    index.md
    index.page.json
    writing.md
    writing.page.json
    components.md
    components.page.json
```

С `--lang=en` содержимое лежит в `content/en/`. Сайт одноязычный, маршруты
без языкового префикса: `/`, `/writing/`, `/components/`.

## См. также

- [Файлы проекта](/ru/start/project-files/) — назначение каждого файла и каталога
- [Команды CLI](/ru/reference/cli/) — все параметры `init`, `capabilities` и `doctor`
