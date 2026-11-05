---
description: "Как добавить внизу страницы подвал с текстом и ссылкой: область footer в page.json, без HTML."
---

# Подвал страницы

Внизу страницы появляется подвал: строка о компании и ссылка. По умолчанию
подвала нет, страница включает его сама.

::::example {src="/ru/demonstrator-results/region-footer/" label="Результат"}
### page.json

:::code {src="../demonstrator-results/region-footer.page.json" lang=json highlight="7-30"}
:::

### Markdown страницы

:::code {src="../demonstrator-results/region-footer.md" lang=markdown}
:::
::::

Прокрутите страницу в окне примера до конца, чтобы увидеть подвал.

## Как это устроено

1. Область `footer` выключена у всех страниц. `"enabled": true` включает её.
2. В подвале одна часть `docara.shell` с двумя элементами: абзацем и
   ссылкой.
3. Линию над подвалом и отступы Docara ставит сама, текст подвала начинается
   там же, где пункты меню. Своего CSS не нужно.

:::code {src="../demonstrator-results/region-footer.page.json" lang=json lines="7-30" highlight="2,7" dedent=true}
:::

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `enabled` | Показывать подвал или нет |
| `tag` | Вид элемента: `p`, `a`, `span` или `div` |
| `text` | Текст элемента, до 500 знаков |
| `href` | Адрес ссылки: путь внутри сайта или `#якорь` |
| `utilities` | Классы SIMAI Framework для отступов, фона и расположения |

Чтобы подвал был на всех страницах, перенесите `layout` в `docara.json`.

## См. также

- [Подсказка под меню](../region-sidebar/)
- [Настройки на трёх уровнях](../composition-inheritance/)
- [Области макета](/ru/settings/layout-and-regions/)
