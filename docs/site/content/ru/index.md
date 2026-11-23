:::hero {space=6 layout=centered padding=2xl effect=text-flow}
# Сайты документации из Markdown

Docara собирает из Markdown-файлов статический сайт: меню, поиск, несколько
языков, проверка ссылок перед публикацией. Работает на GitHub Pages и любом
хостинге.

[Начать](/ru/start/quick-start/)

[Документация](/ru/start/)
:::

::::grid {space=7 columns=3 gap=4}
:::card
### Документация как код

```markdown
# Быстрый старт
Установите пакет и создайте сайт.

1. composer require simai/docara
2. docara init .
```

Текст — обычные файлы Markdown рядом с проектом. Правите файл — получаете
страницу. История — в GitHub, изменения — через pull request.
:::

:::card
### Готовый статический сайт

:icon[check_circle]{scheme=success} Сборка сайта\
:icon[check_circle]{scheme=success} Проверка ссылок и поиска\
:icon[check_circle]{scheme=success} Публикация на GitHub Pages

Сайт собирается прямо в GitHub и публикуется на GitHub Pages или любой
хостинг. Ни сервера, ни базы данных — только файлы, поэтому быстро, а на
GitHub Pages ещё и бесплатно.
:::

:::card
### Много готовых блоков

:badge[Шаги]{size=1} :badge[Карточки]{size=1} :badge[Вкладки]{size=1} :badge[Схемы]{size=1} :badge[Таблицы]{size=1}
:badge[Уведомления]{size=1} :badge[Первый экран]{size=1} :badge[Колонки]{size=1} :badge[Примеры]{size=1}

Из них собираются справочники, инструкции и лендинги.
:::
::::

## Как это работает

::::grid {columns=3 gap=4}
:::card
### 1. Пишете Markdown

```markdown
## Установка
Выполните в терминале:
`composer require simai/docara`
```

Страница — файл в папке `docs/`. Блоки — тоже Markdown: `:::steps`,
`:::tabs`, `:::card`.
:::

:::card
### 2. Отправляете в GitHub

```bash
git commit -m "Страница установки"
git push
```

`git push` или pull request — как с кодом.
:::

:::card
### 3. Сайт обновляется сам

```text
✓ Сборка   ✓ Проверка   ✓ GitHub Pages
```

GitHub Actions собирает и проверяет сайт и выкладывает его на GitHub Pages.
Если проверка не прошла, старая версия остаётся на месте.
:::
::::

:::container {width=full space=7}
Готовый файл процесса для GitHub Actions — в разделе
[«Сборка в CI»](/ru/build/ci/).
:::

## Блоки, из которых собирается страница

Каждый пример ниже — живой: переключите вкладку и посмотрите Markdown, из
которого он собран.

:::::tabs
### Шаги

::::example
```markdown
:::steps {current=2}
1. Создайте проект.
2. Добавьте страницы.
3. Опубликуйте сайт.
:::
```
::::

### Карточки

::::example
```markdown
::::grid {columns=3}
:::card
#### Markdown

Страницы — обычный текст с заголовками и списками.
:::

:::card
#### JSON

Настройки сайта, разделов и страниц.
:::

:::card
#### PHP

Сборка и проверка — один пакет Composer.
:::
::::
```
::::

### Вкладки

::::example
````markdown
:::tabs
### Composer
```bash
composer require simai/docara:^3.0
```
### Архив
Скачайте архив и распакуйте его в пустой каталог.
### Результат
Готовый сайт появится в каталоге сборки.
:::
````
::::

### Код

::::example
`````markdown
````php {title="config/site.php" highlight=3 numbers}
return [
    'name' => 'Docara',
    'locale' => 'ru',
];
````
`````
::::

### Терминал

::::example
`````markdown
````bash {layout=terminal}
composer require simai/docara:^3.0
php vendor/bin/docara init .
php vendor/bin/docara build production
````
`````
::::

### Дерево файлов

::::example
```markdown
:::tree
- content
  - ru
    - index.md
    - section.json
  - assets
    - logo.svg
- docara.json
:::
```
::::

### Схема

::::example
```markdown
:::diagram {title="Публикация страницы документации" label="Черновик в Markdown идёт на сборку сайта; если проверки прошли, страница публикуется, иначе ошибки правят и собирают снова"}
flowchart LR
  draft[Черновик в Markdown] --> build(Сборка сайта)
  build --> check{Проверки прошли?}
  check -->|да| publish[Публикация]
  check -->|нет| fix[Правка ошибок]
  fix --> build
:::
```
::::

### Раскрывающийся блок

::::example
```markdown
:::details
#### Что делать, если сборка остановилась?

Docara пишет код ошибки, файл и что исправить. Поправьте файл и запустите
сборку снова.
:::
```
::::

### Уведомление

::::example
```markdown
:::alert {type=info}
#### Полезная информация

Продолжайте работу в обычном порядке.
:::
```
::::
:::::

## Для команд и больших проектов

::::grid {space=7 columns=3 gap=2}
:::card
:icon[translate]{size=2 container=square variant=tonal scheme=primary}

#### [Несколько языков](/ru/authoring/multilingual-site/)

Каждый язык — своя папка. Docara видит, какой перевод отстал от оригинала, и
может публиковать только актуальные страницы.
:::

:::card
:icon[search]{size=2 container=square variant=tonal scheme=primary}

#### [Поиск без сервера](/ru/settings/search/)

Индекс строится вместе с сайтом. Поиск работает в браузере читателя и никуда
не отправляет запросы.
:::

:::card
:icon[dark_mode]{size=2 container=square variant=tonal scheme=primary}

#### [Удобно читать](/ru/settings/reader-preferences/)

Светлая и тёмная тема, размер шрифта и ширина текста — читатель выбирает сам.
:::

:::card
:icon[fact_check]{size=2 container=square variant=tonal scheme=primary}

#### [Проверка перед выкладкой](/ru/build/verify/)

Перед публикацией Docara проверяет ссылки, файлы и поиск. Если что-то
сломано, сайт не выкладывается.
:::

:::card
:icon[package_2]{size=2 container=square variant=tonal scheme=primary}

#### [Установка через Composer](/ru/build/update/)

Обычный PHP-пакет `simai/docara`, версия закреплена в `composer.lock`.
Обновление — одной командой, при ошибке прежняя версия возвращается сама.
:::

:::card
:icon[menu_book]{size=2 container=square variant=tonal scheme=primary}

#### [Подробная документация](/ru/start/)

Больше 130 страниц на русском и английском: каждый блок с живым примером,
настройки, сборка и публикация.
:::
::::

## Работает с ИИ-агентами

Docara работает и без ИИ. Если вы пишете с Claude Code или Codex, подключите
навык Docara: агент будет знать блоки и правила сайта и проверит свою работу
сборкой.

::::grid {columns=3 gap=2}
:::card
:icon[language]{size=2 container=square variant=tonal scheme=primary}

#### Переводит страницы

Агент переводит, а Docara проверяет, что форма страницы сохранилась и перевод
не отстал от оригинала.
:::

:::card
:icon[drive_file_move]{size=2 container=square variant=tonal scheme=primary}

#### Переносит материалы

README, папка `docs/` и заметки становятся страницами Docara с меню и
поиском.
:::

:::card
:icon[edit_note]{size=2 container=square variant=tonal scheme=primary}

#### Собирает страницу по описанию

«Добавь страницу про установку с вкладками для каждой системы» — агент
соберёт её из готовых блоков.
:::
::::

:::container {width=full space=7}
[Как подключить навык](/ru/start/ai-agent/). Агент собирает и проверяет сайт,
а выкладку решаете вы.
:::

## Частые вопросы

:::details {layout=lines}
### Нужны ли сервер и база данных?

Нет. Готовый сайт — статические файлы: HTML, CSS и JavaScript. PHP 8.2+,
Composer и Node.js 20+ нужны только там, где сайт собирается, — на вашем
компьютере или в GitHub Actions.
:::

:::details {layout=lines}
### Можно ли сделать не только документацию?

Да. Те же блоки собирают лендинг, страницу продукта или раздел с примерами;
для главной есть готовая заготовка `landing`.
:::

:::details {layout=lines}
### Как обновлять Docara?

Командой `php vendor/bin/docara upgrade`. Docara сначала собирает и проверяет
сайт на новой версии в отдельной папке и только потом заменяет старую; при
ошибке всё остаётся как было.
:::

:::details {layout=lines}
### Обязательно ли пользоваться ИИ?

Нет. Всё делается обычными командами и правкой файлов. Навык для ИИ-агента —
дополнительная возможность.
:::

:::details {space=7 layout=lines}
### Чем Docara отличается от VitePress и Docusaurus?

Docara ставится через Composer, как обычный PHP-пакет, а не из npm; поиск,
проверка ссылок перед публикацией и контроль переводов уже входят в неё.
:::

:::surface {width=full tone=muted padding=xl space=0}
## Начните с одной фразы или трёх команд

Поручите сайт агенту или поставьте Docara сами: сайт собирается на вашем
компьютере и в GitHub Actions.

:button[Поручить агенту]{href=/ru/start/ai-agent/} :button[Быстрый старт]{href=/ru/start/quick-start/ variant=outline scheme=on-surface}
:::
