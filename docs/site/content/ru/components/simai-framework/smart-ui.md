---
title: "Компоненты SIMAI Framework ui.*"
description: "Smart-компоненты ui.* из SIMAI Framework — кнопка, уведомление, бейдж, тег, подсказка, аватар, полоса прогресса и ссылка-карточка: что это, как сайт разрешает их в lock-файле и как вызвать в Markdown."
tags: [smart, ui, framework, sf-button, sf-alert, sf-badge, sf-tag, sf-tooltip, sf-avatar, sf-progress-bar, sf-reference-link]
---

# Компоненты SIMAI Framework ui.*

Smart-компоненты `ui.*` — готовые элементы интерфейса из SIMAI Framework:
кнопка, уведомление, поле ввода и другие. В Markdown они вызываются блоком
`:::ui.имя` с параметрами в JSON. Docara сверяет параметры с описанием
компонента ещё при сборке, а в браузере компонент работает так же, как в
других продуктах на SIMAI Framework.

Чем они отличаются от обычных компонентов Docara:

- обычный компонент, например [уведомление](/ru/components/blocks/alert/) `:::alert`,
  пишется Markdown-текстом и подходит для большинства страниц;
- Smart-компонент принимает параметры именно так, как их описывает Framework,
  и нужен, когда на странице должен быть настоящий элемент Framework: кнопка с
  состоянием загрузки, уведомление с заголовком и поясняющим текстом и т. п.

## Как сайт разрешает компоненты

Сайт может вызвать только те Smart-компоненты, которые перечислены в файле
`simai-framework.lock.json` рядом с `docara.json`, в разделе `manifests`. Для
каждого компонента там закреплены поставщик, точная версия и контрольная
сумма его описания, а также ограничения для статического сайта:

```json
{
  "manifests": {
    "ui.button": {
      "provider_revision": "4b055d09926fec4c32f2ae43b2e7e0a6f64d7663",
      "sha256": "235e049d…"
    },
    "ui.alert": {
      "provider_revision": "4b055d09926fec4c32f2ae43b2e7e0a6f64d7663",
      "sha256": "056329bb…",
      "consumer_policy": {
        "blocked": [
          {"prop": "closable", "value": true, "code": "FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME"},
          {"prop": "type", "value": "success", "code": "FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME"}
        ]
      }
    }
  }
}
```

Файл создаёт `docara init`; обновление Docara его не меняет. Сборка сверяет
контрольные суммы с описаниями компонентов, которые поставляются с Docara:
если компонента нет в lock или описание не совпадает, сборка
останавливается. Lock этого сайта разрешает
`ui.button`, `ui.alert`, `ui.badge`, `ui.tag`, `ui.tooltip`, `ui.avatar`,
`ui.progress-bar` и `ui.reference-link` —
те же, что получает новый проект. Подробно поля
lock описаны в справочнике [Framework lock](/ru/reference/framework-lock/).

## Кнопка ui.button

```markdown
:::ui.button
{"preset":"primary","text":"Продолжить","aria-label":"Продолжить настройку"}
:::
```

Результат:

:::ui.button
{"preset":"primary","text":"Продолжить","aria-label":"Продолжить настройку"}
:::

`preset` выбирает готовое оформление: `primary`, `secondary`, `tonal_on_surface`,
`outline`, `outline_on_surface`, `link`, `link_on_surface` или `on_surface`.
Остальные параметры:

| Параметр | Значение |
| --- | --- |
| `text` | Надпись на кнопке, до 120 символов |
| `aria-label` | Подпись для программ экранного доступа, до 120 символов |
| `size` | `1/3`, `1/2`, `1`, `2` или `3` |
| `loading` | `true` — кнопка показывает загрузку |
| `disabled` | `true` — кнопка недоступна |
| `native-type` | `button`, `submit` или `reset` |

:::ui.button
{"preset":"outline","text":"Сохраняем","loading":true,"disabled":true,"native-type":"button","aria-label":"Сохранение выполняется"}
:::

`ui.button` — кнопка действия в интерфейсе, а не ссылка. Чтобы кнопка вела
на другую страницу, используйте [кнопку-ссылку](/ru/components/inline/button/)
`:button[...]` или блок [cta](/ru/components/layout/landing-blocks/#cta-кнопка-действия).

## Уведомление ui.alert

```markdown
:::ui.alert
{"type":"info","variant":"default","title":"Сборка проверяет параметры","supporting-text":"Неизвестный параметр или значение остановит сборку до публикации.","closable":false,"aria-label":"Сборка проверяет параметры"}
:::
```

Результат:

:::ui.alert
{"type":"info","variant":"default","title":"Сборка проверяет параметры","supporting-text":"Неизвестный параметр или значение остановит сборку до публикации.","closable":false,"aria-label":"Сборка проверяет параметры"}
:::

| Параметр | Значение |
| --- | --- |
| `type` | `clear`, `info`, `warning` или `danger` |
| `variant` | `default`, `flat` или `outlined` |
| `title` | Заголовок, до 160 символов |
| `supporting-text` | Поясняющий текст, до 500 символов |
| `closable` | Только `false`: на статическом сайте закрыть уведомление нельзя |
| `aria-label` | Подпись для программ экранного доступа |

:::ui.alert
{"type":"warning","variant":"outlined","title":"Проверьте адрес каталога","supporting-text":"Закрепляйте внешние адреса на коммите, а не на ветке.","closable":false,"aria-label":"Проверьте адрес каталога"}
:::

`type=success` и `closable=true` lock этого сайта запрещает: на статическом
сайте их нечем поддержать. Такой вызов останавливает сборку с
`FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME`. Идентификатор `id` Docara
задаёт сама.

## Бейдж ui.badge

Короткая метка: статус, тип или число.

```markdown
:::ui.badge
{"text":"Оплачен","status":"success"}
:::
```

Результат:

:::ui.badge
{"text":"Оплачен","status":"success"}
:::

| Параметр | Значение |
| --- | --- |
| `text` | Надпись, до 80 символов |
| `status` | `info`, `success`, `warning` или `error` — что сообщает бейдж |
| `appearance` | `tonal` (по умолчанию), `outline` или `main`. `main` — только для счётчика со статусом `error` |
| `scheme` | `neutral`, `primary`, `secondary`, `tertiary` или `on-surface` |
| `size` | `1/3` (по умолчанию), `1/2` или `1` |
| `line` | `1/2`, `1`, `2` или `text` — высота строки, чтобы бейдж не менял высоту строки таблицы или текста |
| `activity` | Ход работы: `running`, `waiting`, `done`, `stopped`, `failed`, `paused` или `queued`. Бейдж сам подставит значок и слово на языке страницы |
| `icon`, `icon-position` | Имя значка и его место: `start` или `end` |
| `icon-only` | `true` — виден только значок, текст остаётся для программ экранного доступа |
| `tight` | `true` — узкие поля для плотных мест |
| `aria-label` | Подпись для программ экранного доступа, до 120 символов |

:::ui.badge
{"text":"PHP 8.4","appearance":"outline","scheme":"on-surface","size":"1/2"}
:::

:::ui.badge
{"activity":"running"}
:::

Для бейджа внутри строки текста удобнее строчный
[бейдж Docara](/ru/components/inline/badge/) `:badge[...]`.

## Тег ui.tag

Тег помечает содержимое коротким словом. Вид `checkbox` — переключатель
фильтра: при нажатии он отмечается или снимает отметку.

```markdown
:::ui.tag
{"text":"Компоненты","kind":"checkbox","count":"12"}
:::
```

Результат:

:::ui.tag
{"text":"Компоненты","kind":"checkbox","count":"12"}
:::

| Параметр | Значение |
| --- | --- |
| `text` | Надпись, до 80 символов |
| `kind` | `default` (по умолчанию) — просто метка, `checkbox` — переключатель |
| `status` | `info`, `success`, `warning` или `error` |
| `appearance` | `default`, `outline` или `ghost` |
| `size` | `1/3`, `1/2` (по умолчанию), `1`, `2` или `3` |
| `height` | `low`, `default`, `high` или `highest` |
| `inline` | `true` — тег стоит в строке другого элемента |
| `count` | Число после надписи, например `12` или `99+` |
| `disabled` | `true` — тег недоступен |
| `aria-label` | Подпись для программ экранного доступа |

:::ui.tag
{"text":"PHP 8.4","appearance":"outline"}
:::

Теги, которые удаляются крестиком или открывают меню, этой странице не нужны:
на статическом сайте их действие некому обработать, поэтому Docara их не
принимает.

## Подсказка ui.tooltip

Сам «пузырёк» подсказки Framework: короткий текст, вторая строка и сочетание
клавиш. Здесь он показан как есть, без элемента, над которым обычно
появляется.

```markdown
:::ui.tooltip
{"text":"Скопировать адрес","shortcut":"mod+c","arrow":"top-center"}
:::
```

Результат:

:::ui.tooltip
{"text":"Скопировать адрес","shortcut":"mod+c","arrow":"top-center"}
:::

| Параметр | Значение |
| --- | --- |
| `text` | Основной текст, до 160 символов |
| `supporting-text` | Вторая строка, до 300 символов |
| `shortcut` | Сочетание клавиш, например `mod+c` или `shift+/` |
| `appearance` | `dark` (по умолчанию) или `light` |
| `arrow` | Где хвостик: `none` (по умолчанию), `top-center`, `bottom-center`, `inline-start`, `inline-end`, `bottom-inline-start` или `bottom-inline-end` |

:::ui.tooltip
{"text":"Черновик","supporting-text":"Эту страницу видите только вы.","appearance":"light","arrow":"inline-start"}
:::

## Аватар ui.avatar

Аватар показывает человека или организацию: инициалы из имени, значок и
отметку в углу.

```markdown
:::ui.avatar
{"heading":"Анна Смирнова","hue":"auto","status":"online"}
:::
```

Результат:

:::ui.avatar
{"heading":"Анна Смирнова","hue":"auto","status":"online"}
:::

| Параметр | Значение |
| --- | --- |
| `heading` | Имя: из него берутся инициалы и подпись для программ экранного доступа |
| `text` | Свои инициалы вместо взятых из имени, до 8 символов |
| `icon` | Значок вместо инициалов, например для бота или сервиса |
| `size` | `1/4`, `1/3`, `1/2`, `1` (по умолчанию), `2` или `3` |
| `shape` | `circle` (по умолчанию) — человек, `square` — организация, проект, группа |
| `hue` | Цвет инициалов: `auto` — по имени, или название цвета: `red`, `teal`, `indigo` и другие |
| `status` | Отметка в углу: `online`, `away`, `offline`, `company`, `verified` или `none` |
| `badge-count`, `badge-dot` | Счётчик или точка на аватаре |
| `line` | `1/2`, `1` или `2` — высота строки, когда аватар стоит рядом с текстом |
| `aria-label` | Подпись для программ экранного доступа |

:::ui.avatar
{"heading":"Команда документации","shape":"square","hue":"indigo","status":"verified"}
:::

## Полоса прогресса ui.progress-bar

Полоса прогресса показывает, сколько работы уже сделано, или насколько
заполнен известный объём, например место на диске.

```markdown
:::ui.progress-bar
{"value":40,"label":"Загрузка","text-position":"top"}
:::
```

Результат:

:::ui.progress-bar
{"value":40,"label":"Загрузка","text-position":"top"}
:::

| Параметр | Значение |
| --- | --- |
| `value` | Сколько сделано, в процентах: от 0 до 100 |
| `label` | Подпись: что именно выполняется |
| `value-text` | Свой текст значения для программ экранного доступа, например «3 из 8 файлов» |
| `text-position` | Где показать проценты: `none` (по умолчанию), `top` — над полосой, `inline-end` — в конце строки |
| `status` | Цвет: пусто — основной цвет темы, `neutral`, `info`, `success`, `warning` или `error` |
| `warning-at`, `error-at` | Пороги в процентах: с этого значения полоса сама становится предупреждением или ошибкой |
| `indeterminate` | Работа идёт, но её объём пока неизвестен: по полосе бежит отрезок |
| `paused` | Работа на паузе |
| `meter` | Это не ход работы, а заполненность объёма: квота, диск |
| `inline` | Короткая полоса в конце строки, например в ячейке таблицы; используйте вместе с `meter` |
| `size` | `1/3`, `1/2`, `1` (по умолчанию), `2` или `3` |
| `aria-label` | Подпись для программ экранного доступа |

:::ui.progress-bar
{"value":83,"label":"Диск","meter":true,"warning-at":80,"error-at":95,"text-position":"top"}
:::

:::ui.progress-bar
{"label":"Подготовка","indeterminate":true}
:::

## Ссылка-карточка ui.reference-link

Ссылка-карточка ведёт на другую страницу: название, короткая пометка рядом
и значок типа. Её ставят, когда ссылка должна быть заметнее строки текста.

```markdown
:::ui.reference-link
{"label":"Модель компонентов","href":"/ru/start/component-model/","text":"Начало"}
:::
```

Результат:

:::ui.reference-link
{"label":"Модель компонентов","href":"/ru/start/component-model/","text":"Начало"}
:::

| Параметр | Значение |
| --- | --- |
| `label` | Название ссылки |
| `href` | Куда ведёт ссылка: адрес на сайте (`/…`, `./…`, `#…`) или внешний адрес `https://…` |
| `text` | Короткая пометка рядом с названием: раздел, источник или номер |
| `appearance` | `card` (по умолчанию) — отдельная карточка, `inline` — ссылка внутри текста, `plain` — ссылка отдельной строкой |
| `icon` | Значок перед названием; без параметра в карточке стоит стрелка |
| `target` | `_self` (по умолчанию) или `_blank` — открыть в новой вкладке |
| `state` | Страницы больше нет: `missing` — удалена, `forbidden` — нет доступа; ссылка тогда не работает |
| `disabled` | Ссылка недоступна |
| `size`, `line` | `1/2`, `1` или `2` — размер поля формы или высота строки, например для ячейки таблицы |
| `aria-label` | Подпись для программ экранного доступа |

:::ui.reference-link
{"label":"Старая страница","state":"missing"}
:::

## Другие компоненты

- `ui.input`, `ui.checkbox`, `ui.dropdown` и `ui.list-item` (только текстовый
  вариант внутри `ui.dropdown`) тоже поставляются с Docara. Их используют
  собственные компоненты сайта, например конструктор команды установки — см.
  [примеры расширений](/ru/examples/extensions/). Прямо в Markdown они
  вызываются только на сайте, чей lock их разрешает.
- `ui.tabs` и `ui.dataview` пока не приняты. Вместо них используйте
  [вкладки](/ru/components/blocks/tabs/) и
  [таблицу](/ru/authoring/markdown/#таблицы) Docara.
- `ui.copy-button` тоже пока не принят: для него ещё не готово описание,
  которое Docara может проверить.

Какие компоненты доступны вашему сайту, покажет команда
`php vendor/bin/docara atlas --json`, а подробности одного компонента —
`php vendor/bin/docara inspect smart ui.button --json`. Как устроены
Smart-компоненты внутри и как написать собственный, описано в разделе
[Smart-компоненты Docara](/ru/development/smart-components/).

## Ошибки

Неверный вызов останавливает сборку. Чаще всего встречаются:

| Код | Причина |
| --- | --- |
| `FRAMEWORK_DIRECTIVE_JSON_INVALID` | Параметры — не корректный JSON |
| `FRAMEWORK_PROP_UNKNOWN` | У компонента нет такого параметра |
| `FRAMEWORK_PROP_ENUM_INVALID` | Значения нет в списке допустимых |
| `FRAMEWORK_PROP_MANAGED` | Параметр задаёт сама Docara, например `id` |
| `FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME` | Значение запрещено lock для статического сайта |

Причины и способы исправления всех кодов — в справочнике
[ошибок страниц](/ru/reference/errors/content/#компоненты-и-их-параметры).
