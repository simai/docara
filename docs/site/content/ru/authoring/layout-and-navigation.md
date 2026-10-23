# Макеты и навигация

Один переносимый формат поддерживает два presentation recipe:

| Preset | Когда использовать | Что видит читатель |
| --- | --- | --- |
| `docs` | Руководство, справочник, база знаний | Дерево, breadcrumbs, outline, previous/next |
| `landing` | Главная промостраница или сфокусированный вход | Контент без документационного дерева |

Оба preset используют тот же Markdown, JSON, Framework lock и правила
безопасности.

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
`none`. `settings.ui_radius` принимает `default`, `medium`, `large` и управляет
общим Framework-токеном `--sf-radius--ui`; стандартное значение —
`--sf-radius-1/3`. Размытие и закругление задаёт только автор: читатель
менять их не может.

`layout.scrollbar.preset` управляет полосами прокрутки длинного левого меню и
правого содержания. Режим `overlay` используется по умолчанию: полоса не
занимает место в сетке, появляется во время прокрутки, расширяется при
наведении и затем исчезает. Для постоянно видимой, системной или визуально
скрытой полосы выберите соответственно `persistent`, `standard` или `hidden`.
Поведение реализует `sf-scrollbar` SIMAI Framework, поэтому Docara не
дублирует его размеры, цвета и JavaScript.

Тема из JSON задаёт первое посещение; пользовательский выбор в браузере
описан в [настройках чтения](/authoring/reader-settings/).
