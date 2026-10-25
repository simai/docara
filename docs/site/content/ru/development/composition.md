---
description: "Как устроен интерфейс Docara: макет задаёт области, секции наполняют их блоками, Smart-компоненты рисуют элементы."
tags: [композиция, дизайн, макет, секция, блок, atlas, предпросмотр]
---

# Композиция интерфейса

Docara собирает интерфейс как конструктор на трёх уровнях. Макет задаёт
области страницы, секция наполняет область блоками, а Smart-компонент или
представление рисует конечный элемент. Всё это — настройки из проверенных
описаний в реестрах, а не второй шаблонный движок.

## Три уровня

1. **Макет (layout)** отвечает за геометрию страницы и имена её областей.
2. **Секция (section)** ставится в подходящую область и раскладывает блоки
   по слотам.
3. **Блок (block)**, Smart-компонент или представление (view) получает
   проверенные данные. Конечный HTML он создаёт через общие реестры и
   `SmartComponentGateway`.

Markdown страницы разбирается отдельно, в типизированный документ в памяти
(Document IR). Длина ограды `:::` не выбирает уровень композиции.

Проверенные настройки и разобранный Markdown превращаются в общий рецепт
(Composition Recipe). Стандартный сборщик SIMAI Framework создаёт из него
Composition Document — готовое дерево страницы. Docara восстанавливает из
дерева вход своих рендереров и создаёт HTML. Авторские файлы при этом не
переписываются.

## Путь от файла до страницы

Каждый шаг описан в своём зарегистрированном файле:

| Шаг | Файл или место | За что отвечает |
| --- | --- | --- |
| Макет | `resources/layouts/docara.docs.json` | области, обязательность, ширина контейнера |
| Дерево макета | `resources/views/layout.docara.docs.json` | разрешённые элементы и места областей |
| Область | `header`, `sidebar`, `main`, `outline`, `footer` из описания макета | место вставки, а не ветка кода по ID компонента |
| Секция | `resources/sections/docara.article.json`, `docara.header.json`, `docara.navigation.json`, `docara.outline.json`, `docara.shell.json` | подходящие области и разрешённые блоки |
| Дерево секции | `resources/views/section.docara.article.json`, `section.docara.header.json`, `section.docara.shell.json` | допустимые слоты |
| Блок | `resources/blocks/content.document.json`, `content.markdown.json`, `content.smart.json`, `shell.element.json`, `shell.smart.json` | схема данных блока и его возможность |
| Smart-компонент | из пакета Docara, SIMAI Framework или проекта | параметры, представления, пресеты, слоты, ассеты, оживление в браузере |
| Представление | зарегистрированное представление, пресет или шаблон | конечный HTML, ассеты, происхождение |
| Общее оформление | `src/PortableSite/SurfacePresentation.php` | единая внешняя рамка Surface, Hero, Showcase и Promo |

Так проходит обычная страница документации:

```text
Markdown owner
  -> typed Document IR
  -> Layout docara.docs
  -> region main
  -> Section docara.article
  -> slot content
  -> Block content.document
  -> generated Composition Recipe
  -> Framework resolveRecipe
  -> Composition Document
  -> Docara renderer projection
  -> SmartComponentGateway when a Smart leaf is present
  -> registered View
  -> DeclarativePageRenderer: regions into the layout View Tree
  -> PageBuilder result
```

## Кто проверяет совместимость

Для каждого контейнера Atlas показывает правила вложения: `allowed_children`,
`slots`, `min_children`, `max_children`, `order`, `max_depth` и
`depth_semantics`. Значение `relative_subtree_root_level_1` значит, что каждый
контейнер считает себя уровнем 1.

Реестр отклоняет неизвестный вид узла, тег, атрибут, утилиту, область или слот
в дереве разметки (View Tree). Это происходит ещё до регистрации. Настройки
проекта только выбирают допущенные ID. В них нельзя указать класс, функцию
обратного вызова, PHP-код или путь к шаблону.

### Общее оформление не меняет владельца

Surface — контейнер для авторского содержимого. Hero, Showcase и Promo — свои
типизированные блоки со своим смыслом. Их владелец, состояния, параметры и
происхождение берутся из их собственных описаний. Внешнюю рамку — ширину,
контейнер и фон — у всех собирает одно неизменяемое оформление Surface.
Оборачивать эти блоки в Surface нельзя. Страница целиком и `<head>`
по-прежнему принадлежат приложению, а области в дерево макета расставляет
`DeclarativePageRenderer`.

## Атлас проекта

Atlas — список всего, что можно поставить на страницу: макеты, секции, блоки,
представления, привязки и пресеты. Его выводит `docara atlas --json`, и
строится он только из допущенных реестров `DesignRegistry`, `SmartRegistry` и
`BindingRegistry`. Отпечаток Atlas сборка записывает в свою квитанцию. Atlas —
только вычисленное отображение реестров, редактировать его как источник
нельзя. Ниже — его текущее содержимое, без ручного списка:

:::atlas_index {kind=layout,section,block,view,binding,preset}
:::

## Части оболочки

Одна оболочка сайта обслуживает виды страниц `docs`, `site` и `landing`.
Варианты меняют зарегистрированное оформление, а путь отрисовки остаётся тем
же.

| Часть интерфейса | Кто отвечает | Возможность или оформление | Можно заменить проектом |
| --- | --- | --- | --- |
| Логотип и название | `docara.brand` через привязку `docara.branding` | `full`, `compact`, `logo`, `text` | через допущенную возможность бренда |
| Меню в шапке | `docara.navigation` | `header` | да |
| Дерево разделов в боковой колонке | `docara.navigation` | `tree` | да |
| Компактное меню для телефона | `docara.navigation` | `compact` | да |
| Содержание страницы | `docara.toc` | привязка `docara.outline` | да |
| Поиск | зарегистрированный Smart-компонент поиска | возможность поиска в оболочке | да |
| Хлебные крошки | зарегистрированный Smart-компонент | контекст чтения | да |
| Ссылки «назад» и «вперёд» | зарегистрированный Smart-компонент | контекст чтения | да |
| Настройки чтения | `docara.preferences` | боковая панель | да |
| Подвал | Smart-компонент пакета или проекта | возможность подвала | да |
| Документ целиком: `<head>`, канонический адрес, метаданные, список ассетов | `PageBuilder` и приложение | принадлежит приложению | нет |

`header`, `tree` и `compact` — три представления одного `docara.navigation`.
Нужное выбирает `BindingRegistry` по возможности и описанию поставщика.
`SmartComponentGateway`, рендерер, `DeclarativePageRenderer` и `PageBuilder`
остаются теми же. Если `view` не указан, сохраняется прежнее поведение по
умолчанию.

Вклад проекта не может заменить канонический адрес, метаданные языка,
квитанцию сборки, происхождение поиска и меню или произвольный фрагмент
`<head>`. Эти части принадлежат приложению. Так `verify-static` может
проверить сайт, а полная сборка и сборка одной страницы дают одинаковый
результат.

## Привязки данных оболочки

Блок `shell.smart` может выбрать только зарегистрированную привязку в поле
`bind`. Привязка соединяет данные страницы с уже зарегистрированным
Smart-компонентом. Это не PHP-функция и не путь к шаблону. Встроенный реестр
содержит три привязки:

| ID | Возможности | Smart-компонент |
| --- | --- | --- |
| `docara.branding` | `shell.brand` | `docara.brand` |
| `docara.navigation` | `shell.primary-navigation`, `shell.secondary-navigation` | `docara.navigation` |
| `docara.outline` | `shell.outline` | `docara.toc` |

Описание привязки и его происхождение показывают команды:

```bash
docara list binding
docara inspect binding docara.navigation
docara schema binding
```

Область макета и секция объявляют подходящие возможности. Ещё до отрисовки
сборка отклонит неизвестную привязку и несовместимую возможность. Так же она
отклонит попытку передать параметры, которыми владеет привязка, или подменить
ID пакета. JSON проекта не может задавать класс, функцию, путь к PHP-файлу,
шаблону или любому другому файлу.

## Свои макеты и блоки проекта

Проект может добавить свои макет, секцию, блок и безопасное дерево разметки в
каталог `design/`. Менять движок для этого не нужно. Цепочка та же:

```text
Layout → Region → Section → Slot → Block → Smart или безопасный element
```

Все определения читает один `DesignRegistry`. Встроенные ID принадлежат
пакету, а ID проекта — пространству имён из `docara.json`:

```json
{"smart":{"namespace":"project"}}
```

ID проекта должен начинаться с `project.`. Он не может заменить `docara.*`,
`content.*`, `shell.*` или `ui.*`. Сборку останавливают повторы,
символические ссылки, выход из корня и неизвестные определения.

### Файлы

```text
design/
├── layouts/project.docs.json
├── sections/project.article.json
├── blocks/project.document.json
└── views/
    ├── layout.project.docs.json
    └── section.project.article.json
```

Макет задаёт безопасное дерево разметки, области, их значения по умолчанию и
место документа:

```json
{
  "schema": "docara.layout.v1",
  "key": "project.docs",
  "default": false,
  "view": "layout.project.docs",
  "configuration": {
    "container": {"max": 7},
    "scrollbar": {"preset": "overlay"},
    "content": {"gap": 0}
  },
  "document": {
    "region": "stage",
    "section": "project.article",
    "slot": "content",
    "block": "project.document"
  },
  "regions": {
    "stage": {
      "required": true,
      "default_enabled": true,
      "default_sections": [],
      "section_types": ["content"]
    }
  },
  "assets": []
}
```

Секция объявляет подходящие области, слоты и блоки. Блок выбирает один из уже
зарегистрированных безопасных видов рендерера. JSON не принимает PHP, путь к
шаблону, функцию, произвольный HTML или CSS.

Чтобы выбрать макет проекта, сбросьте унаследованную ветку:

```json
{
  "layout": {
    "$reset": true,
    "key": "project.docs"
  }
}
```

## Вклад проекта в оболочку

Часть оболочки от проекта — это файлы с данными в разрешённом каталоге
`design/` или `smart/` и ссылка на допущенный ID привязки. Менять `src/`
движка не нужно. Порядок подключения:

1. Выберите своё пространство имён в `docara.json`.
2. Создайте файлы через `docara scaffold`: сначала `--dry-run`, затем
   `--apply` с хешем плана.
3. Проверьте описание, схему, пространство имён и возможности.
4. Убедитесь через `docara atlas --json`, что у элемента `owner=project` и
   `support=project`.
5. Посмотрите результат в отдельном предпросмотре на реальной странице.
6. Только после этого соберите сайт целиком.

Сборка останавливается, если:

- ID проекта совпадает с ID пакета или SIMAI Framework;
- настройки пытаются переопределить параметры, которыми владеет привязка;
- возможность, область или слот конфликтует с уже занятыми;
- путь выходит за корень, ведёт через символическую или жёсткую ссылку,
  совпадает с другим без учёта регистра или указывает на произвольный файл;
- вклад в оболочку пытается изменить страницу целиком или `<head>`.

Стартовый шаблон содержит небольшой пример подвала. Секция и блок проекта
допускают в область `footer` только зарегистрированный `project.footer-links`.
Ни настройки, ни Markdown не выбирают путь к PHP-файлу или шаблону. Компонент,
его представление и ассеты находит обычный поставщик Smart-компонентов
проекта.

Подвал работает как часть оболочки, а `project.install-builder` и
`project.product-configurator` — как содержимое страницы. Все три проходят
те же реестры и `SmartComponentGateway`. Элементы проекта на этом сайте:

:::atlas_index {support=project}
:::

## Предпросмотр

У предпросмотра (preview) нет своего рендерера. `PreviewKernel` запускает
обычный `PortableSiteBuilder` с теми же разбором Markdown, `DesignRegistry`,
`SmartComponentGateway`, рендерером, сборщиком ассетов,
`DeclarativePageRenderer` и `PageBuilder`. Из готовой страницы он берёт
нужную часть.

### Полная композиция

:::internal_preview {size=tall title="Композиция областей"}
[Открыть результат](/ru/demonstrator-results/regions-composed/)
:::

### Вид docs

:::internal_preview {size=tall title="Вид docs"}
[Открыть результат](/ru/demonstrator-results/preset-docs/)
:::

### Вид landing

:::internal_preview {size=tall title="Вид landing"}
[Открыть результат](/ru/demonstrator-results/preset-landing/)
:::

### Команды

Команда `docara preview smart|region|layout|page` собирает выбранную часть в
`.docara-preview/output/<target>/`:

```bash
docara preview page --page=/ru/components/alert/
docara preview layout --page=/ru/components/alert/
docara preview region --page=/ru/components/alert/ --selector=main
docara preview smart --page=/ru/components/alert/ --selector=ui.alert
```

Для автоматизации добавьте `--json`. С `--watch` команда следит за всем, от
чего зависит выбранная страница. Это подписи интерфейса для её языка и
реально использованные макет, секция, блок, Smart-компонент, шаблон и ассеты
из пакета и проекта:

```bash
docara preview region --page=/ru/components/alert/ --selector=main --watch
```

В каталоге результата лежат три файла:

- `artifact.html` — ровно выбранный фрагмент;
- `index.html` — та же страница, что в рабочей сборке, для проверки в браузере;
- `preview.json` — ассеты, происхождение, зависимости и хеши.

Рядом публикуются нужные локальные CSS, JS, файлы SIMAI Framework и ассеты
страницы. Поэтому открывайте `index.html` через HTTP из каталога
`.docara-preview/output/<target>/`. Квитанция рабочей сборки и другие
страницы туда не копируются. Предпросмотр не перезаписывает обычные каталоги
`build_*`.

Изменение постороннего Smart-компонента проекта выбранную часть не
пересобирает. Изменение, появление или удаление файла внутри реально
использованного элемента проекта или пакета вызывает одну пересборку этой
страницы. Признак `target_only=true` выводится из этого и из результата
пересборки, а не задан заранее.

### Почему предпросмотр не попадёт на сайт

Результат предпросмотра изолирован в `.docara-preview/` и не считается
рабочей сборкой. В его квитанции записано `build.purpose=preview`, поэтому
`verify-static` завершится ошибкой `PREVIEW_BUILD_PURPOSE_FORBIDDEN`. Защиту
даёт сама квитанция, а не отдельная служебная отметка рядом. Для публикации
нужна обычная полная сборка.

## Проверка

```bash
php vendor/bin/phpunit --filter 'DesignRegistryTest|ProjectDesignCompositionTest|PreviewKernelTest'
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

Не добавляйте ID проекта в PHP-код движка или в перечисления схем. Если нужен
новый исполняемый рендерер или API, это отдельное расширение платформы, а не
JSON проекта.

## См. также

- [Области макета](/ru/settings/layout-and-regions/) — настройка областей автором сайта
- [Smart-компоненты](/ru/development/smart-components/) — описание компонента, поставщики и шлюз
- [Архитектура](/ru/development/architecture/) — путь сборки от Markdown до HTML
- [SDK для разработчика и AI-агента](/ru/development/developer-sdk/) — команды `inspect`, `schema`, `scaffold`, `preview`
- [Словарь терминов](/ru/reference/glossary/)
- [Примеры для разработчиков](/ru/development/examples/)
