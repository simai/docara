---
description: Галерея диаграмм Mermaid — все 22 типа схем, которые рисует Docara, с живым результатом, исходником и примерами из жизни документации, от процесса публикации до плана релиза.
tags: [mermaid, схемы, диаграммы, flowchart, gantt]
---

# Галерея диаграмм

Здесь собраны все типы схем Mermaid, которые рисует встроенный в Docara
Mermaid 11.16. У каждого типа есть короткое пояснение, когда он нужен, живая
схема и её исходник на вкладке «Markdown» — его можно скопировать в свою
страницу и поменять подписи.

Как подключить схему, какие у неё параметры и с какими подписями бывают
сложности, описано на странице [«Диаграмма»](/ru/components/diagram/).

## Процессы и поведение

### Блок-схема

`flowchart` показывает шаги и развилки процесса: что за чем идёт и куда
ведёт каждое решение.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Публикация страницы документации"}
flowchart LR
  draft[Черновик в Markdown] --> build(Сборка сайта)
  build --> check{Проверки прошли?}
  check -->|да| publish[Публикация]
  check -->|нет| fix[Правка ошибок]
  fix --> build
  publish --> reader([Читатель видит страницу])
:::
```
:::

### Диаграмма последовательности

`sequenceDiagram` показывает, кто кому и в каком порядке отправляет запросы
и ответы, — удобно для описания API.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Запрос списка страниц через API"}
sequenceDiagram
  autonumber
  participant app as Приложение
  participant api as API
  participant db as База данных
  app->>api: GET /v1/pages?section=components
  api->>api: Проверить токен
  alt токен действителен
    api->>db: Выбрать страницы раздела
    db-->>api: 30 записей
    api-->>app: 200 OK и список страниц
  else токен истёк
    api-->>app: 401 Unauthorized
  end
:::
```
:::

### Диаграмма состояний

`stateDiagram-v2` показывает, в каких состояниях бывает объект и что
переводит его из одного в другое.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Жизненный цикл страницы"}
stateDiagram-v2
  state "На проверке" as review
  state "Устарела" as outdated
  [*] --> Черновик
  Черновик --> review: автор отправил
  review --> Черновик: есть замечания
  review --> Опубликована: одобрено
  Опубликована --> outdated: вышла новая версия
  outdated --> Черновик: страницу обновляют
  outdated --> [*]: страницу удалили
:::
```
:::

### Путь пользователя

`journey` показывает шаги человека и его впечатление на каждом шаге —
оценкой от 1 до 5.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Путь читателя к ответу"}
journey
  title Путь читателя к ответу
  section Поиск
    Ищет в поисковике: 3: Читатель
    Открывает страницу: 4: Читатель
  section Чтение
    Находит пример: 5: Читатель
    Копирует исходник: 5: Читатель
  section Применение
    Вставляет в свой сайт: 4: Читатель, Автор
    Пишет в поддержку: 2: Читатель, Поддержка
:::
```
:::

## Структура и данные

### Диаграмма классов

`classDiagram` показывает объекты кода, их поля, методы и связи между ними.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Модель страниц и компонентов"}
classDiagram
  class Section["Раздел"] {
    +string title
    +pages() list~Page~
  }
  class Page["Страница"] {
    +string slug
    +string title
    +render() string
  }
  class Component["Компонент"] {
    <<interface>>
    +render(attributes) string
  }
  class Diagram["Диаграмма"]
  class Example["Пример"]
  Section "1" o-- "*" Page : содержит
  Page ..> Component : использует
  Component <|.. Diagram
  Component <|.. Example
:::
```
:::

### Модель данных

`erDiagram` показывает сущности, их поля и сколько записей одной сущности
связано с другой.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Модель данных сайта документации"}
erDiagram
  SITE["Сайт"] {
    string base_url "адрес сайта"
    string locale "язык по умолчанию"
  }
  SECTION["Раздел"] {
    string title "название в меню"
  }
  PAGE["Страница"] {
    string slug PK "адрес страницы"
    string title "заголовок"
    int order "место в меню"
  }
  TAG["Метка"]
  EXAMPLE["Пример"]
  SITE ||--|{ SECTION : "делится на"
  SECTION ||--o{ PAGE : "содержит"
  PAGE }o--o{ TAG : "отмечена"
  PAGE ||--o{ EXAMPLE : "показывает"
:::
```
:::

### Требования

`requirementDiagram` связывает требования с тем, что их выполняет и
проверяет.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Требования к сборке"}
requirementDiagram
  requirement offline_build {
    id: R1
    text: "Сборка работает без сети"
    risk: medium
    verifymethod: test
  }
  performanceRequirement page_weight {
    id: R2
    text: "Страница не тяжелее бюджета"
    risk: low
    verifymethod: analysis
  }
  element build_command {
    type: "команда"
    docref: "docara build"
  }
  element weight_check {
    type: "скрипт"
    docref: "verify-page-weight.php"
  }
  build_command - satisfies -> offline_build
  weight_check - verifies -> page_weight
:::
```
:::

### Интеллект-карта

`mindmap` раскладывает тему на ветви от центра — подходит для структуры
раздела или плана документации.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Структура документации"}
mindmap
  root((Документация))
    Начало работы
      Установка
      Первый сайт
    Написание
      Markdown
      Компоненты
      Диаграммы
    Настройки
      docara.json
      Навигация
    Сборка
      Проверки
      Публикация
:::
```
:::

### Карта площадей

`treemap-beta` показывает доли вложенных частей прямоугольниками: чем больше
часть, тем больше её площадь.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Разделы документации по числу страниц"}
treemap-beta
"Документация"
    "Компоненты": 31
    "Справочник": 15
    "Руководства"
        "Начало работы": 6
        "Написание": 8
        "Сборка": 8
        "Настройки": 8
        "Разработка": 8
    "Примеры": 17
    "Результаты демонстраций": 14
:::
```
:::

### Блоки

`block-beta` раскладывает блоки по сетке, когда важно их взаимное
расположение, а не поток.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Макет страницы документации"}
block-beta
  columns 3
  header["Шапка и поиск"]:3
  nav["Меню раздела"] article["Статья"] toc["Оглавление"]
  footer["Подвал"]:3
:::
```
:::

### Пакет

`packet-beta` показывает, из каких полей и битов состоит пакет или запись
в двоичном формате.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Заголовок пакета TCP"}
packet-beta
  title Заголовок TCP
  0-15: "Порт отправителя"
  16-31: "Порт получателя"
  32-63: "Номер последовательности"
  64-95: "Номер подтверждения"
  96-99: "Длина"
  100-103: "Резерв"
  104-111: "Флаги"
  112-127: "Окно"
  128-143: "Контрольная сумма"
  144-159: "Указатель срочности"
:::
```
:::

## Архитектура

### Контекст системы

`C4Context` показывает систему, её пользователей и внешние системы — первый
уровень модели C4.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Контекст системы документации"}
C4Context
  title Контекст системы документации
  Person(author, "Автор", "Пишет страницы в Markdown")
  Person(reader, "Читатель", "Ищет ответ на сайте")
  System(docara, "Docara", "Собирает статический сайт")
  System_Ext(git, "GitHub", "Хранит исходники")
  System_Ext(hosting, "Хостинг", "Отдаёт готовые страницы")
  Rel(author, git, "Отправляет правки")
  Rel(git, docara, "Запускает сборку")
  Rel(docara, hosting, "Публикует HTML")
  Rel(reader, hosting, "Открывает страницы")
  UpdateLayoutConfig($c4ShapeInRow="3", $c4BoundaryInRow="1")
:::
```
:::

### Архитектура сервисов

`architecture-beta` рисует сервисы и группы со значками облака, сервера,
диска и базы данных. Русские подписи берите в двойные кавычки.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Архитектура сайта документации"}
architecture-beta
  group site(cloud)["Сайт документации"]
  service cdn(internet)["CDN"] in site
  service web(server)["Веб-сервер"] in site
  service search(disk)["Индекс поиска"] in site
  service builds(database)["Хранилище сборок"] in site
  cdn:R --> L:web
  web:B --> T:search
  web:R --> L:builds
:::
```
:::

## Планы и история

### Диаграмма Ганта

`gantt` показывает задачи на календаре: сроки, зависимости и контрольные
точки.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="План релиза"}
gantt
  title План релиза
  dateFormat YYYY-MM-DD
  axisFormat %d.%m
  section Подготовка
    Аудит страниц    :done, audit, 2026-10-19, 3d
    Галерея диаграмм :active, gallery, after audit, 4d
  section Проверка
    Тесты и сборка   :tests, after gallery, 2d
    Приёмка          :crit, accept, after tests, 1d
  section Выпуск
    Публикация       :milestone, release, after accept, 0d
:::
```
:::

### Хронология

`timeline` перечисляет события по порядку — удобно для истории выпусков.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Выпуски Docara 3"}
timeline
  title Выпуски Docara 3
  section 14–18 октября
    3.0 : Framework в комплекте
    3.2 : Сайты стали легче
  section 19–21 октября
    3.4 : Сборка для портала
    3.5 : Фоновые эффекты
    3.6 : Проверка веса страниц
  section 21–23 октября
    3.7 : Страница 404
    3.8 : Справочник кодов ошибок
:::
```
:::

### Ветки Git

`gitGraph` показывает ветки, коммиты и слияния — например, порядок работы
над изменением.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Работа над изменением в Git"}
gitGraph
  commit id: "3.8.0" tag: "v3.8.0"
  branch feature/gallery
  checkout feature/gallery
  commit id: "Галерея диаграмм"
  commit id: "Проверка Mermaid"
  checkout main
  commit id: "Исправление поиска"
  merge feature/gallery id: "Слияние ветки"
  commit id: "3.9.0" tag: "v3.9.0"
:::
```
:::

### Канбан

`kanban` раскладывает задачи по колонкам статусов.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Доска задач документации"}
kanban
  todo[Сделать]
    t1[Описания страниц настроек]
    t2[Английская главная]
  doing[В работе]
    t3[Галерея диаграмм]@{ assigned: 'Автор', priority: 'High' }
  review[На проверке]
    t4[Новый логотип]
  done[Готово]
    t5[Проверка веса страниц]
:::
```
:::

## Графики и сравнение

### Круговая диаграмма

`pie` показывает доли целого — когда частей немного и важна пропорция.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Страницы документации по типу"}
pie showData
  title Страницы по типу
  "Руководство" : 38
  "Компонент" : 31
  "Пример" : 31
  "Справочник" : 15
:::
```
:::

### График

`xychart-beta` рисует столбцы и линии по осям — например, вес страниц этого
сайта рядом с общим бюджетом 4,5 МБ.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Вес страниц и общий бюджет"}
xychart-beta
  title "Вес страниц, МБ"
  x-axis ["Медиана сайта", "Значки", "Диаграмма", "Галерея"]
  y-axis "Мегабайты" 0 --> 8
  bar [2.75, 4.51, 6.35, 6.46]
  line [4.5, 4.5, 4.5, 4.5]
:::
```
:::

### Квадрант

`quadrantChart` раскладывает пункты по двум осям на четыре группы — удобно
для приоритетов.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Приоритеты задач документации"}
quadrantChart
  title Приоритеты задач
  x-axis Мало усилий --> Много усилий
  y-axis Мало пользы --> Много пользы
  quadrant-1 Планировать
  quadrant-2 Делать сразу
  quadrant-3 Между делом
  quadrant-4 Не делать
  Описания страниц: [0.2, 0.8]
  Галерея диаграмм: [0.45, 0.7]
  Английская версия: [0.85, 0.9]
  Новые значки: [0.3, 0.25]
  Свой движок схем: [0.9, 0.15]
:::
```
:::

### Поток

`sankey-beta` показывает, как объём перетекает между этапами: ширина полосы
равна величине. Подписи пишите латиницей — с кириллицей схема не рисуется.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Поток файлов через сборку"}
sankey-beta
Markdown pages,Build,118
Page settings,Build,118
Images,Build,40
Build,HTML pages,118
Build,Search index,118
Build,Assets,40
:::
```
:::

### Радар

`radar-beta` сравнивает несколько вариантов по общему набору оценок.

:::example {label="Схема"}
```markdown
:::diagram {engine=mermaid title="Качество разделов документации"}
radar-beta
  title Качество разделов
  axis complete["Полнота"], accuracy["Точность"], examples["Примеры"], navigation["Навигация"], speed["Скорость"]
  curve components["Компоненты"]{5, 4, 5, 4, 3}
  curve settings["Настройки"]{3, 5, 2, 4, 5}
  max 5
  min 0
:::
```
:::

## См. также

- [Диаграмма](/ru/components/diagram/)
- [Интерактивный пример](/ru/components/example/)
- [Математическая формула](/ru/components/math/)
