# Архитектура

Основной пакет `simai/docara` владеет CLI, JSON schemas, разрешением
наследования, Markdown renderer, Framework adapter, starter и статической
сборкой.

Проект определяется наличием `docara.json` со schema `docara.site.v1`.

Поток portable-сборки:

:::steps
1. Загрузить и проверить site, section, page и Framework lock.
2. Разрешить наследование и provenance каждой страницы.
3. Преобразовать Markdown и Smart-вызовы в типизированный `DocumentAst`.
4. Разрешить `Layout -> Region -> Section -> Block -> Smart`.
5. Построить маршруты, каноническую топологию и asset plan.
6. Получить из одной топологии видимое меню, breadcrumbs и previous/next.
7. Отрендерить regions через trusted templates и immutable view models.
8. Построить эффективный каталог и сгенерировать его index/detail страницы.
9. Собрать полный документ через зарегистрированный publisher template.
10. Записать кандидатный HTML, общие shell assets, search index, receipts и plans.
11. Только после полного успеха транзакционно заменить действующий `build_*`.

:::

Каноническая топология содержит все страницы, включая скрытые. Проекция меню
отбрасывает скрытые листья, но сохраняет видимые дочерние ветви. Breadcrumbs и
соседние страницы используют каноническую модель, поэтому UI-поверхности не
расходятся по пути или порядку.

TOC builder работает с безопасным HTML Markdown до подстановки
Smart-компонентов. Он назначает H1–H6 уникальные Unicode `id`, а компонент
`docara.toc` выводит H2–H6 только до `reading.toc_depth`. В конфигурации правая
область по-прежнему называется `outline`: это стабильный layout key, а не имя
компонента.

## Источники и производные поверхности

- `resources/component-catalog/native`, `typed`, `smart` и `requirements`
  содержат owner-записи возможностей;
- exact Framework lock и manifests ограничивают Smart admission;
- `EffectiveComponentCatalogBuilder` создаёт один проверяемый каталог;
- `PortableComponentCatalogProjector` использует один общий index/detail shape;
- `_docara/component-catalog.json`, generated HTML и page receipt являются
  производными результатами, а не вторым источником истины.

Техническая canonical-запись компонента хранит стабильный ID, контракт и
lifecycle. Переводимые названия, описания и подписи находятся в language packs,
а исполняемые exact fixtures — в каталоге примеров. Отдельные ручные страницы
для каждого компонента не нужны: generated catalog объединяет эти источники.

Файлы являются источником истины; база данных, runtime CRUD, роли и workflow не
входят в standalone Docara.

`resources/publisher/templates/page.php` является только host-шаблоном
документа. Элементы publisher chrome находятся в зарегистрированных шаблонах
`resources/publisher/components`; product Smart-шаблоны, manifest и их CSS/JS
— в `resources/smart`. В `resources/portable` остаётся общая геометрия shell и
поведение уровня документа. Builder не содержит HTML, CSS или client runtime.

Пользовательская сборка остаётся PHP-only. Browser JavaScript в готовом
статическом сайте не означает, что автору нужен Node.js.

## Где заканчиваются данные и начинается представление

- Markdown хранит содержание и вызовы разрешённых компонентов.
- `docara.json`, `section.json` и `<page>.page.json` хранят настройки и
  наследование, но не HTML-шаблоны.
- Resolved page plan содержит итоговые данные страницы и provenance каждого
  значения.
- Layout contract определяет области, section contract — их безопасную
  композицию, а Smart manifests — параметры отдельных интерфейсных блоков.
- Trusted templates хранятся отдельно в `resources/publisher` и
  `resources/smart`; пользовательские JSON не могут указывать PHP, Blade,
  callback, script или произвольный template path.
- Publisher получает immutable view model и собирает готовый статический HTML.

Поэтому внешний вид можно менять в одном зарегистрированном шаблоне, настройки
— в данных проекта, а содержание — в Markdown. Builder координирует эти слои,
но не смешивает их в одном файле.
