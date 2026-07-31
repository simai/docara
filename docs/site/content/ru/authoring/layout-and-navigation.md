# Макеты и навигация

Один переносимый формат поддерживает два presentation recipe:

| Preset | Когда использовать | Что видит читатель |
| --- | --- | --- |
| `docs` | Руководство, справочник, база знаний | Дерево, breadcrumbs, outline, previous/next |
| `landing` | Главная промостраница или сфокусированный вход | Контент без документационного дерева |

Оба preset используют тот же Markdown, JSON, Framework lock и правила
безопасности.

## Верхнее меню сайта

Ключевые ссылки в шапке задавайте в корневом
`content/<locale>/section.json`. Подписи принадлежат конкретному языку, поэтому
русская, английская и любая другая версия могут иметь разный состав и порядок:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "home", "label": "Главная", "href": "/ru/"},
      {"id": "start", "label": "Быстрый старт", "href": "/ru/start/"},
      {"id": "components", "label": "Компоненты", "href": "/ru/components/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

`id` — стабильный технический идентификатор, `label` — локализованная подпись,
`href` — внутренний путь, якорь или безопасный HTTPS-адрес. Допустимо не более
восьми одноуровневых пунктов. Чтобы отключить меню во вложенном разделе,
задайте `"header_navigation": {"enabled": false}`. Чтобы заменить унаследованный
список, передайте новый `items`: списки наследуются целиком, а не по индексам.

На широком экране меню располагается между брендом и действиями шапки. На
узком экране оно не сжимается и не обрезается: ссылки переходят в единую
панель «Навигация», где сначала идёт раздел «Главное», затем дерево
документации. На лендинге без дерева та же панель содержит только ключевые
ссылки.

Сам каркас описывается именованными областями `header`, `sidebar`, `main`,
`outline`, `footer`. Их включение, наследование и безопасное наполнение
секциями и Smart-компонентами разобраны отдельно:
[области макета](/authoring/regions/).

## Рецепт документационной страницы

Создайте `content/guides/install.md`, затем соседний
`content/guides/install.page.json`:

```json
{
  "schema": "docara.page.v1",
  "title": "Установка",
  "description": "Установка проекта с нуля.",
  "preset": "docs",
  "layout": { "container": { "max": 6 } },
  "navigation": { "order": 20 },
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 3,
    "previous_next": true
  }
}
```

Соберите сайт и проверьте, что страница есть в дереве, active path раскрыт,
breadcrumbs ведут к overview, а outline содержит H2/H3.

## Рецепт лендинга

Создайте `content/landing.md` и sidecar:

```json
{
  "schema": "docara.page.v1",
  "title": "Лендинг",
  "preset": "landing",
  "layout": { "container": { "max": 7 } },
  "navigation": { "hidden": true },
  "search": { "enabled": false, "indexed": false }
}
```

Содержание остаётся Markdown, а композицию задают bounded typed-блоки:

- [hero](../../components/hero/) создаёт полноширинный первый экран;
- [grid](../../components/grid/) и [card](../../components/card/) собирают
  ряд возможностей без отдельного специального компонента;
- [figure](../../components/figure/) или [media](../../components/media/)
  показывают продуктовый скриншот;
- [logos](../../components/logos/) выводит участников или клиентов;
- [banner](../../components/banner/) с [button](../../components/button/)
  завершает страницу понятным действием.

Обычные заголовки, текст и код остаются во внутреннем контейнере. Только
зарегистрированные полноширинные блоки занимают всю ширину landing-области;
автор не добавляет отрицательные отступы, CSS-классы и размеры media.

[Открыть главную страницу этой документации](/).

## Проверка обоих рецептов

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте `/guides/install/` и `/landing/` по HTTP, затем нажмите `Ctrl+C`.

## Ширина и тема

`layout.container.max` принимает целое число `1..8`. Docara преобразует его в
класс SIMAI Framework `max-container-N`; фактическая максимальная ширина
берётся из системного токена Framework, а не дублируется в CSS Docara.
Значение `7` подходит для большинства сайтов. Меньшее число сужает общую сетку,
большее расширяет её. Шапка, документационная сетка, футер и внутреннее
содержимое полноширинных лендинг-блоков остаются выровнены по одной сетке.
`layout.key` выбирает зарегистрированный макет; сейчас доступен
`docara.docs`. `layout.regions` управляет его областями.
`settings.theme` принимает `system`, `light`, `dark`. Параметр
`settings.modal_blur` управляет размытием страницы за модальными окнами и
принимает `none`, `small`, `medium`, `large`; по умолчанию используется
`large`.

`layout.scrollbar.preset` управляет полосами прокрутки длинного левого меню и
правого содержания. Режим `overlay` используется по умолчанию: полоса не
занимает место в сетке, появляется во время прокрутки, расширяется при
наведении и затем исчезает. Для постоянно видимой, системной или визуально
скрытой полосы выберите соответственно `persistent`, `standard` или `hidden`.
Поведение реализует `sf-scrollbar` SIMAI Framework, поэтому Docara не
дублирует его размеры, цвета и JavaScript.

Тема из JSON задаёт первое посещение; пользовательский выбор в браузере
описан в [настройках чтения](/authoring/reader-settings/).

## Порядок и видимость

- `navigation.hidden` исключает страницу из видимого меню;
- `navigation.order` задаёт неотрицательный sibling order;
- страница без order идёт после страниц с явным order;
- при равенстве используется стабильный source path.

Hidden page остаётся публичным HTML. Чтобы убрать её из поиска, дополнительно
задайте `search.indexed: false`.

## Как строится дерево

Иерархия берётся из каталогов и Markdown-файлов, а не из public `slug`.
Overview-файл и одноимённый каталог объединяются:

```text
content/
  guides.md
  guides/
    install.md
    configuration.md
    configuration/
      theme.md
      advanced/
        overrides.md
```

Вместо `guides.md` можно использовать `guides/index.md`. Каталог без overview
остаётся группой без ложной ссылки.

Модель не имеет depth cap. Интерфейс принят на четырёх видимых уровнях; более
глубокие элементы сохраняют семантику и оформление последнего визуального
уровня.

## Active path и адаптивность

Текущая ссылка получает `aria-current="page"`, предки раскрываются. На широком
экране дерево закреплено слева. На узком оно открывается поверх статьи в
нативном dialog «Навигация», поэтому содержание не сдвигается. Escape, кнопка
закрытия и клик по backdrop закрывают панель и возвращают focus инициатору.

Breadcrumbs, outline и previous/next выводятся из того же дерева, поэтому
порядок не расходится. Preset `landing` этот reading context не выводит.

[Настройки reading context](/authoring/reading-context/).
