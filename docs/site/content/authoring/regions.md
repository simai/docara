# Области макета

Макет `docara.docs` состоит из пяти именованных областей:

| Область | Содержимое по умолчанию | Обязательна |
| --- | --- | :---: |
| `header` | Бренд и ссылка на главную | — |
| `sidebar` | Многоуровневое меню | — |
| `main` | Markdown текущей страницы | ✓ |
| `outline` | Ссылки на заголовки H2–H6 | — |
| `footer` | Пусто | — |

`sidebar` и `outline` выводятся семантическими элементами `aside`. Отдельной
области с именем `aside` нет.

## Простое включение и отключение

Эта страница отключает левое меню и правое оглавление своим
`regions.page.json`:

```json
{
  "schema": "docara.page.v1",
  "layout": {
    "regions": {
      "sidebar": { "enabled": false },
      "outline": { "enabled": false }
    }
  }
}
```

В [декларативной версии этой страницы](/_docara/declarative-preview/pages/authoring/regions/)
остаётся шапка и основное содержимое, а оба `aside` отсутствуют в HTML.
Legacy-страница продолжает использовать принятый renderer до отдельного
переключения публикации.

Настройку можно положить в:

- `docara.json` — для всего сайта;
- `section.json` — для раздела и всех потомков;
- `<page>.page.json` — только для соседней Markdown-страницы.

Поздний уровень меняет только явно указанные области.

## Из чего собирается область

Расширенная форма использует ту же последовательность, что и Larena:

```text
Page -> Region -> Section -> Block -> Smart
```

Например, стандартная левая навигация описывается так:

```json
{
  "layout": {
    "regions": {
      "sidebar": {
        "enabled": true,
        "sections": [
          {
            "section": "docara.shell",
            "blocks": [
              {
                "block": "shell.smart",
                "smart": "docara.navigation",
                "bind": "navigation",
                "props": {
                  "maximum_depth": 4
                }
              }
            ]
          }
        ]
      }
    }
  }
}
```

`bind` не является выражением. Это фиксированная безопасная привязка данных,
подготовленных builder:

| Smart-компонент | Область | Bind |
| --- | --- | --- |
| `docara.header` | `header` | `branding` |
| `docara.navigation` | `sidebar` | `navigation` |
| `docara.outline` | `outline` | `outline` |

Другую комбинацию сборка отклонит. JSON не может выбрать PHP-файл, class,
callback или произвольный template path.

## Что нельзя отключить

`main` обязателен для `docara.docs`. Значение
`layout.regions.main.enabled: false` завершает сборку ошибкой
`DECLARATIVE_REQUIRED_REGION_DISABLED`.

Если нужен принципиально другой каркас, следует зарегистрировать другой
layout, а не нарушать контракт существующего.

## Наследование и provenance

`sections` является массивом: поздний массив целиком заменяет унаследованный.
Частичное изменение `enabled` не затрагивает унаследованный список секций.

Итог и владеющий файл находятся в:

```text
build_<environment>/.docara/resolved-page-plans.json
```

Проверьте `configuration.layout.regions` и соответствующие pointers вида
`/layout/regions/sidebar/enabled` в `provenance`.

:::ui.alert
{"type":"info","title":"Области уже являются данными","supporting-text":"Компилятор получает разрешённый layout contract и больше не выбирает header, sidebar и outline жёстко по коду."}
:::
