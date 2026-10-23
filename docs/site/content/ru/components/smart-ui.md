---
title: "Компоненты SIMAI Framework ui.*"
description: Smart-компоненты ui.* из SIMAI Framework — кнопка ui.button, уведомление ui.alert и другие: что это, как сайт разрешает их через simai-framework.lock.json и как вызвать в Markdown.
tags: [smart, ui, framework, sf-button, sf-alert]
---

# Компоненты SIMAI Framework ui.*

Smart-компоненты `ui.*` — готовые элементы интерфейса из SIMAI Framework:
кнопка, уведомление, поле ввода и другие. В Markdown они вызываются блоком
`:::ui.имя` с параметрами в JSON. Docara сверяет параметры с описанием
компонента ещё при сборке, а в браузере компонент работает так же, как в
других продуктах на SIMAI Framework.

Чем они отличаются от обычных компонентов Docara:

- обычный компонент, например [уведомление](/ru/components/alert/) `:::alert`,
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
      "provider": "larena/ui",
      "provider_revision": "4b055d09926fec4c32f2ae43b2e7e0a6f64d7663",
      "sha256": "235e049d…"
    },
    "ui.alert": {
      "provider": "larena/ui",
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
`ui.button` и `ui.alert` — те же, что получает новый проект. Подробно поля
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
на другую страницу, используйте [кнопку-ссылку](/ru/components/button/)
`:button[...]` или блок [cta](/ru/components/landing-blocks/#cta-кнопка-действия).

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

## Другие компоненты

- `ui.input`, `ui.checkbox`, `ui.dropdown` и `ui.list-item` (только текстовый
  вариант внутри `ui.dropdown`) тоже поставляются с Docara. Их используют
  собственные компоненты сайта, например конструктор команды установки — см.
  [примеры расширений](/ru/examples/extensions/). Прямо в Markdown они
  вызываются только на сайте, чей lock их разрешает.
- `ui.badge`, `ui.tabs` и `ui.dataview` пока не приняты. Вместо них используйте
  [бейдж](/ru/components/badge/), [вкладки](/ru/components/tabs/) и
  [таблицу](/ru/authoring/markdown/#таблицы) Docara.

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
