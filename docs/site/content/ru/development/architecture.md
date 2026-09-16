# Архитектура

Основной пакет `simai/docara` владеет CLI, JSON schemas, разрешением
наследования, Markdown renderer, Framework adapter, starter и статической
сборкой.

Проект определяется наличием `docara.json` со schema `docara.site.v1`.

Поток portable-сборки:

:::steps
1. Загрузить и проверить site, section, page и Framework lock.
2. Разрешить наследование и provenance каждой страницы.
3. Один раз скомпилировать Markdown и Smart-вызовы в типизированный `DocumentIr`
   в памяти.
4. Подготовить страницу, области, секции и блоки из проверенных настроек.
   Превратить эту структуру в Recipe, передать стандартному сборщику
   SIMAI Framework и получить готовый Composition Document.
   Вход PHP-рендерера восстановить из этого проверенного документа.
5. Построить маршруты и каноническую топологию.
6. Получить из одной топологии видимое меню, breadcrumbs и previous/next.
7. Отрендерить содержимое через единый registry/Smart gateway, а regions —
   через зарегистрированные templates и immutable view models.
8. Построить производные индекс, меню, breadcrumbs, outline, previous/next,
   search и эффективный machine-readable каталог из результатов PageBuilder.
9. Собрать кандидатный полный документ через зарегистрированный publisher
   template и передать его общему Framework Asset Planner.
10. По существующему Loader registry определить точные CSS/JS и зависимости
    первого кадра, повторно собрать документ с этим планом и проверить его
    устойчивость по финальному HTML.
11. Записать HTML, content-hashed CSS, отдельные Framework receipts страниц,
    search index и остальные plans.
12. Только после полного успеха транзакционно заменить действующий `build_*`.

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
- страницы компонентов принадлежат Markdown, а их индекс строится из метаданных
  результатов PageBuilder без generated index/detail projector;
- `_docara/component-catalog.json`, HTML и диагностический receipt являются
  производными результатами, а не вторым источником истины.

Техническая canonical-запись компонента хранит стабильный ID, контракт и
lifecycle. Текст каждой русской публичной страницы находится в одном Markdown,
а повторяющиеся интерфейсные подписи — в `content/ru/lang.json`. Package-owned
fixtures и данные других локалей не являются владельцами русской страницы.

Все страницы проходят через один PageBuilder и общий Composition Recipe
resolver Framework. Markdown сначала получает типизированное представление
`DocumentIr`, а готовая структура всей страницы — Composition Document.
Это разные документы: первый описывает прочитанный Markdown, второй —
результат сборки страницы вместе с областями, секциями и блоками.
Layout composer принимает типизированный
PageBuilder artifact; сырого `trustedMainHtml`, generated-page bypass и
публичных page projector больше нет.

Файлы являются источником истины; база данных, runtime CRUD, роли и workflow не
входят в standalone Docara.

`resources/publisher/templates/page.php` является только host-шаблоном
документа. Элементы publisher chrome находятся в зарегистрированных шаблонах
`resources/publisher/components`; product Smart-шаблоны, manifest и их CSS/JS
— в `resources/smart`. В `resources/portable` остаётся общая геометрия shell и
поведение уровня документа. Builder не содержит HTML, CSS или client runtime.

Для сборки нужны PHP, Node.js и точная generated-поставка Framework.
Node.js запускает общий Recipe resolver один раз на всю сборку; автору не
нужно заводить отдельный frontend-проект. Готовый сайт остаётся статическим:
для его публикации PHP и Node.js не нужны.

Перед сборкой задайте путь к Framework и, если `node` отсутствует в `PATH`,
путь к Node.js:

```bash
export DOCARA_SIMAI_UI_ROOT=/path/to/exact/ui
export DOCARA_NODE_BINARY=/path/to/node
php vendor/bin/docara build production
```

Каждая страница получает отпечатки Recipe и готового Document в отчёте сборки.
Ошибка проверки останавливает создание кандидата и сохраняет предыдущий
результат. [Два варианта страницы](/ru/examples/) показывают явную Recipe,
которая выбирает шапку по настройке; для обычных страниц Recipe создаётся
автоматически.
