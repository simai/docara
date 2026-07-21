# Области макета

Макет `docara.docs` состоит из пяти именованных областей:

| Область | Содержимое по умолчанию | Обязательна |
| --- | --- | :---: |
| `header` | Бренд и ссылка на главную | — |
| `sidebar` | Многоуровневое меню | — |
| `main` | Markdown текущей страницы | ✓ |
| `outline` | Содержание страницы: ссылки на заголовки H2–H6 | — |
| `footer` | Пусто | — |

`sidebar` и `outline` выводятся семантическими элементами `aside`. `outline` —
устойчивое техническое имя правой области; в интерфейсе она называется
«Содержание страницы». Отдельной области с именем `aside` нет.

## Простое включение и отключение

Чтобы отключить левое меню и правое оглавление на отдельной странице, добавьте
в её sidecar:

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

В результате остаётся шапка и основное содержимое, а оба `aside` и мобильная
кнопка меню отсутствуют в HTML. [Открыть живой пример, результат и точные
исходники](/examples/regions-disabled/).

Настройку можно положить в:

- `docara.json` — для всего сайта;
- `section.json` — для раздела и всех потомков;
- `<page>.page.json` — только для соседней Markdown-страницы.

Поздний уровень меняет только явно указанные области.

Сравнить включённые области, готовые shell-рецепты, наследование, оба preset и Smart-компоненты можно
в [демонстраторе декларативных макетов](/examples/). Его результаты собираются
тем же основным генератором, что и весь сайт.

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

Стандартные секции содержат только устойчивый ID экземпляра и ссылку на
зарегистрированную секцию. Слоты, блок `shell.smart`, Smart-компонент,
`maximum_depth` и привязка данных находятся в определении
`resources/sections/docara.navigation.json`, а не копируются на каждую
страницу:

| Область | Секция | Smart-компонент | Данные |
| --- | --- | --- | --- |
| `header` | `docara.header` | `docara.brand` | `branding` |
| `sidebar` | `docara.navigation` | `docara.navigation` | `navigation` |
| `outline` | `docara.outline` | `docara.toc` | `outline` |

Секция — это зарегистрированный рецепт наполнения области. Smart-компонент —
самостоятельный интерфейсный элемент с manifest, props, views, шаблонами,
assets и readiness. Поэтому `docara.header` сохранён как имя секции, но
компонент бренда называется `docara.brand`; `docara.outline` сохранён как имя
секции, а компонент содержания страницы называется `docara.toc`.

Другую комбинацию стандартной секции сборка отклонит.

## Собственная композиция области

Для практического наполнения используйте зарегистрированную секцию
`docara.shell`. Она принимает устойчивые ID блоков, проверенные утилиты Simai
Framework и два типа вызовов:

- `shell.element` — один безопасный семантический элемент с текстом и, для
  `a`, локальным `href`;
- `shell.smart` — зарегистрированный `ui.alert` или `ui.button` с props,
  проверенными по точному manifest зафиксированного Framework runtime.

Минимальный footer:

```json
{
  "layout": {
    "regions": {
      "footer": {
        "enabled": true,
        "sections": [
          {
            "id": "site-footer",
            "section": "docara.shell",
            "utilities": ["flex", "flex-col", "gap-1", "p-2", "surface-0"],
            "blocks": [
              {
                "id": "copyright",
                "block": "shell.element",
                "slot": "content",
                "element": {
                  "tag": "p",
                  "text": "Docara — документация как код.",
                  "utilities": ["m-0"]
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

Это не строка HTML. Автор выбирает только разрешённый тег, текст, локальную
ссылку и утилиты из точной Framework projection. PHP, Blade, raw HTML,
обработчики событий, JavaScript URL, style/class string, callback и пути к
шаблонам остаются недоступны.

Живые исходники:

- [брендированный header](/examples/region-header/);
- [sidebar с дополнительным блоком](/examples/region-sidebar/);
- [aside с оглавлением и Alert](/examples/region-aside/);
- [footer из безопасных элементов](/examples/region-footer/).

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
ID секций и блоков обязаны быть уникальными и сохраняют детерминированный
порядок массива. [Живой пример трёх уровней с reset](/examples/composition-inheritance/)
показывает итог `docara.json → section.json → page.page.json`.

Итог и владеющий файл находятся в:

```text
build_<environment>/.docara/resolved-page-plans.json
```

Проверьте `configuration.layout.regions` и соответствующие pointers вида
`/layout/regions/sidebar/enabled` в `provenance`.

:::ui.alert
{"type":"info","title":"Области уже являются данными","supporting-text":"Компилятор получает разрешённый layout contract и больше не выбирает header, sidebar и outline жёстко по коду."}
:::

Далее: [архитектура Smart-компонентов](/development/smart-components/) и
[живой пример product Smart-компонентов](/examples/product-smart-runtime/).
