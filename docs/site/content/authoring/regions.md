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

В основном результате и
[диагностическом preview](/_docara/declarative-preview/pages/authoring/regions/)
остаётся шапка и основное содержимое, а оба `aside` отсутствуют в HTML.

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
            "id": "docs-navigation",
            "section": "docara.navigation"
          }
        ]
      }
    }
  }
}
```

Пользовательский файл содержит только устойчивый ID экземпляра и ссылку на
зарегистрированную секцию. Слоты, блок `shell.smart`, Smart-компонент,
`maximum_depth` и привязка данных находятся в определении
`resources/sections/docara.navigation.json`, а не копируются на каждую
страницу:

| Smart-компонент | Область | Bind |
| --- | --- | --- |
| `docara.header` | `header` | `branding` |
| `docara.navigation` | `sidebar` | `navigation` |
| `docara.outline` | `outline` | `outline` |

Другую комбинацию сборка отклонит. Пользовательский JSON не может передать
блоки из внутреннего определения, PHP, Blade, HTML, class, callback или путь к
шаблону.

## Как формируется разметка

Layout и Section ссылаются на зарегистрированные безопасные View Tree:

```text
Layout View Tree -> Region
Section View Tree -> Slot -> Block -> Smart
```

View Tree хранится в `resources/views/*.json`. Он допускает только ограниченный
набор семантических тегов, безопасные атрибуты и точную проекцию утилит Simai
Framework. Неизвестный класс, тег, повторная область или повторный слот
завершают сборку ошибкой. Сложные presentation leaves могут использовать Blade
только через внутренний реестр Docara; путь к Blade никогда не поступает из
авторского файла.

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
