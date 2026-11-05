---
description: "Как сделать посадочную страницу продукта без меню документации: вид landing, первый экран, карточки и кнопка действия."
---

# Посадочная страница продукта

Главная страница продукта не похожа на документацию: на ней нет меню и
оглавления, есть крупный первый экран, карточки преимуществ и кнопка
действия. Её собирает тот же Docara из того же Markdown.

::::example {src="/ru/demonstrator-results/preset-landing/" label="Результат"}
### page.json

:::code {src="../demonstrator-results/preset-landing.page.json" lang=json highlight="5"}
:::

### Markdown страницы

:::code {src="../demonstrator-results/preset-landing.md" lang=markdown}
:::
::::

## Как это устроено

1. `"preset": "landing"` убирает меню и оглавление и отдаёт странице всю
   ширину.
2. `:::hero` — первый экран: заголовок, короткий текст и главная кнопка.
3. `:::features` превращает список в карточки, а `:::cta` — ссылку в кнопку.
4. Поиск на этой странице выключен: посадочной странице он не нужен.

:::code {src="../demonstrator-results/preset-landing.md" lang=markdown lines="5-12"}
:::

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `preset` | Вид страницы: `landing` или `docs` |
| `layout.container.max` | Ширина содержимого, от 1 до 8 |
| `search.enabled` | Поиск в шапке |
| `search.indexed` | Попадает ли страница в результаты поиска |

## См. также

- [Первый экран](/ru/components/hero/)
- [Блоки лендинга](/ru/components/landing-blocks/)
- [Страница документации](../preset-docs/)
