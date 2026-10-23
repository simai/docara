:::hero {variant=centered padding=2xl effect=constellation-net}
# Сайт документации из Markdown — на PHP и Composer

Пишите страницы в Markdown, а настройки — в JSON. Docara соберёт из них
быстрый статический сайт на SIMAI Framework с меню, поиском и тёмной темой.
Такой сайт можно выложить на любой хостинг.

Docara 3 · открытый код на [GitHub](https://github.com/simai/docara) ·
лицензия Apache 2.0

[Быстрый старт](/ru/start/quick-start/)

[Посмотреть пример](/components/)
:::

## Первый сайт за три команды

Установите пакет через Composer, создайте заготовку и соберите сайт. Для сборки
нужны PHP 8.2+, Composer и Node.js 20+; готовому сайту — только статический
веб-сервер.

```bash
# 1. Установите Docara
composer require simai/docara:^3.0

# 2. Создайте заготовку сайта
php vendor/bin/docara init .

# 3. Соберите сайт и откройте его в браузере
php vendor/bin/docara serve production
```

Заготовки, проверка результата и публикация — в [полном быстром старте](/ru/start/quick-start/).

## Почему Docara

:::features
- :icon[package_2]{size=2 container=square variant=tonal scheme=primary} **Composer вместо npm.** Docara ставится и обновляется как обычный PHP-пакет: без npm-зависимостей и собственной темы, которую нужно поддерживать.
- :icon[bolt]{size=2 container=square variant=tonal scheme=primary} **Статический результат.** Сборка выдаёт готовые HTML, CSS и JavaScript. Их можно положить на GitHub Pages или любой веб-сервер: PHP и Node.js там не нужны.
- :icon[palette]{size=2 container=square variant=tonal scheme=primary} **Дизайн-система SIMAI.** Интерфейс, светлая и тёмная темы и компоненты берутся из SIMAI Framework. Ошибку в вызове компонента сборка найдёт до публикации.
:::

## Документация из коробки

Меню, поиск и удобное чтение Docara строит сама — без своей темы и плагинов.

:::features
- :icon[account_tree]{size=2 container=square variant=tonal scheme=primary} **Меню и навигация.** Разделы, хлебные крошки, содержание страницы и переходы к соседним материалам строятся из структуры файлов. Подробнее — в разделе [«Макеты и навигация»](/authoring/layout-and-navigation/).
- :icon[search]{size=2 container=square variant=tonal scheme=primary} **Поиск по сайту.** Индекс собирается при сборке и работает в браузере читателя: серверу не нужны ни база данных, ни внешний сервис.
- :icon[tune]{size=2 container=square variant=tonal scheme=primary} **Удобно читать.** Читатель сам выбирает светлую или тёмную тему, размер шрифта и ширину текста.
- :icon[translate]{size=2 container=square variant=tonal scheme=primary} **Несколько языков.** Каждый язык — своя папка с Markdown. Связанные страницы получают переключатель языка и `hreflang`, подробнее — в [«Многоязычном сайте»](/authoring/multilingual-site/).
- :icon[web]{size=2 container=square variant=tonal scheme=primary} **Лендинг из тех же файлов.** Эта страница — тоже Docara: preset `landing` убирает меню документации и оставляет первый экран, блоки и призыв к действию. Как она устроена — [на отдельной странице](/landing/).
- :icon[verified]{size=2 container=square variant=tonal scheme=primary} **Проверка перед публикацией.** Команда `verify-static` проверяет готовый сайт до выкладки: страницы, локальные ссылки, ресурсы и поисковый индекс. Подробнее — в [«Проверке статического результата»](/build/verify/).
:::

## Примеры страниц

:::features
- :icon[widgets]{size=2 container=square variant=tonal scheme=primary} **[Каталог компонентов](/components/).** Каждый компонент с живым примером, параметрами и кодом, который можно скопировать.
- :icon[dashboard]{size=2 container=square variant=tonal scheme=primary} **[Примеры макетов](/examples/).** Отдельные страницы с разными областями, шапками и наследованием настроек — вместе с исходными файлами.
- :icon[extension]{size=2 container=square variant=tonal scheme=primary} **[Компоненты проекта](/project-demos/).** Собственные компоненты сайта, подключённые без изменения Docara: конструктор команды установки и конфигуратор продукта.
:::

## Частые вопросы

:::details {view=lines}
### Чем Docara отличается от VitePress и Docusaurus?

VitePress и Docusaurus — проекты на npm: сайт собирается из JavaScript-пакетов,
а внешний вид задаёт тема, которую команда настраивает и обновляет. Docara
ставится через Composer, хранит содержание в Markdown и JSON, а интерфейс
берёт из SIMAI Framework. Она подходит командам, которые работают на PHP или
хотят документацию в общем стиле с продуктами SIMAI.
:::

:::details {view=lines}
### Какие требования?

Для сборки нужны PHP 8.2+ с расширениями `ctype`, `dom`, `intl`, `json`,
`libxml` и `mbstring`, Composer и Node.js 20+: команда `node` в `PATH` или путь
в переменной `DOCARA_NODE_BINARY`. Готовый сайт статический, ему нужен только
веб-сервер.
:::

:::details {view=lines}
### Можно ли сделать лендинг?

Да. Preset `landing` использует тот же Markdown, JSON и компоненты, но убирает
меню документации. Эта страница собрана именно так.
:::

:::details {view=lines}
### Можно ли добавить свой компонент?

Да. Сначала попробуйте обычный Markdown и готовые компоненты Docara. Если их не
хватает, подключите собственный компонент сайта по
[документированному контракту](/development/extensions/).
:::

:::details {view=lines}
### Как безопасно обновляться?

Команда `php vendor/bin/docara upgrade` готовит новую версию отдельно,
собирает и проверяет сайт и только затем применяет результат. Вернуться к
предыдущей версии можно командой `upgrade --rollback=latest`.
:::

:::promo
## Соберите первый сайт

Быстрый старт проведёт от пустого каталога до готовой страницы в браузере.

[Перейти к быстрому старту](/ru/start/quick-start/)
:::
