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
  "layout": { "max_width": "normal" },
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
  "layout": { "max_width": "wide" },
  "navigation": { "hidden": true },
  "search": { "enabled": false, "indexed": false }
}
```

Hero остаётся обычным H1 и абзацами Markdown. Для перехода возьмите
[generated пример CTA](/components/catalog/docara.cta/), для короткого списка
преимуществ —
[generated пример features](/components/catalog/docara.features/), для команд
используйте fenced code.

[Открыть живой лендинг этой документации](/landing/).

## Проверка обоих рецептов

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте `/guides/install/` и `/landing/` по HTTP, затем нажмите `Ctrl+C`.

## Ширина и тема

`layout.max_width` принимает `compact`, `normal`, `wide`, `full`.
`settings.theme` принимает `system`, `light`, `dark`.

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
экране дерево закреплено слева. На узком оно доступно через нативный disclosure
«Разделы». Escape закрывает мобильную панель и возвращает focus.

Breadcrumbs, outline и previous/next выводятся из того же дерева, поэтому
порядок не расходится. Preset `landing` этот reading context не выводит.

[Настройки reading context](/authoring/reading-context/).
