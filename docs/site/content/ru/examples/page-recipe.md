---
description: "Как собрать страницу по шаблону SIMAI Framework, где одна настройка выбирает вариант шапки ещё до создания HTML."
---

# Страница по шаблону с выбором шапки

Шаблон страницы описывает её части, а одна настройка выбирает, какую шапку
поставить: краткую или расширенную. Обе страницы ниже собраны из одних и тех
же файлов шаблона, отличаются только значением настройки.

Это продвинутая возможность для тех, кто собирает страницы программой.
Обычным страницам документации она не нужна.

## Краткая шапка

::::example {src="/ru/demonstrator-recipes/compact/" label="Результат"}
### inputs-compact.json

:::code {src="../../../composition/inputs-compact.json" lang=json highlight="5-7"}
:::

### recipe.json

:::code {src="../../../composition/recipe.json" lang=json highlight="13-21"}
:::
::::

## Расширенная шапка

::::example {src="/ru/demonstrator-recipes/expanded/" label="Результат"}
### inputs-expanded.json

:::code {src="../../../composition/inputs-expanded.json" lang=json highlight="5-7"}
:::

### recipe.json

:::code {src="../../../composition/recipe.json" lang=json highlight="13-21"}
:::
::::

## Как это устроено

1. `recipe.json` — шаблон: в шапку ставится фрагмент по значению
   `headerMode`, в основную часть — текст из значения `body`.
2. Файл значений задаёт `headerMode`: `compact` или `expanded`. Остальное у
   двух страниц общее.
3. Страница Docara ссылается на свой набор файлов строкой
   `composition_recipe` в начале Markdown. SIMAI Framework собирает страницу
   при сборке сайта, в браузере ничего не вычисляется.

:::code {src="../demonstrator-recipes/compact.md" lang=markdown highlight=4}
:::

## Что можно поменять

| Параметр | Что меняет |
|---|---|
| `headerMode` | Вариант шапки: `compact` или `expanded` |
| `composition_recipe` | Какой набор файлов шаблона собирает страницу |

## См. также

- [Шапка, меню и оглавление](../regions-composed/)
- [Страница документации](../preset-docs/)
