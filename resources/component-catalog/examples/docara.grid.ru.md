<!-- docara-variant:base -->
<!-- docara-variant:state.stacked -->
<!-- docara-variant:state.responsive -->
::::grid {columns=4 gap=2}
:::card
#### Markdown

Понятный исходный формат.
:::
:::card
#### JSON

Проверяемые настройки.
:::
:::card
#### PHP

Воспроизводимая сборка.
:::
:::card
#### HTML

Готовый результат работает без runtime.
:::
::::

## Ссылка и действие внутри карточки

::::grid {columns=2 gap=2}
:::card
#### Документация как код

Пишите материалы в Markdown и храните изменения рядом с проектом.

:button[Подробнее]{href="../button/" type=link icon=arrow_forward}
:::
:::card
#### Быстрый старт

Соберите первую страницу и проверьте результат в браузере.

:button[Начать]{href="../button/" type=outline}
:::
::::

## Карточки с изображениями

:::::grid {columns=3 gap=2}
::::card {variant=plain}
:::figure {ratio=16/9 fit=cover}
![Содержание](../../_docara/component-catalog/feature-markdown.png)
:::
##### Содержание остаётся содержанием

Markdown легко читать и редактировать.
::::
::::card {variant=plain}
:::figure {ratio=16/9 fit=contain}
![Композиция](../../_docara/component-catalog/feature-json.png)
:::
##### Макет задаётся декларативно

JSON связывает области и компоненты.
::::
::::card {variant=plain}
:::figure {ratio=16/9 fit=contain}
![Сборка](../../_docara/component-catalog/feature-build.png)
:::
##### Результат можно проверить

Сборка создаёт готовый статический сайт.
::::
:::::

## Рецепт преимуществ

Это композиция `Grid + Card + Icon`, а не отдельный компонент.

::::grid {columns=4 gap=2}
:::card
:icon[edit_note]{size=1 container=square variant=tonal scheme=primary}
#### Понятный исходник

Markdown остаётся главным содержанием.
:::
:::card
:icon[schema]{size=1 container=circle variant=tonal scheme=secondary}
#### Строгая схема

Ошибки настроек видны до публикации.
:::
:::card
:icon[devices]{size=1 container=square variant=outline scheme=info}
#### Адаптивность

Один интерфейс работает на любом экране.
:::
:::card
:icon[bolt]{size=1 container=circle variant=main scheme=success}
#### Быстрый результат

На сервере остаются статические файлы.
:::
::::

## Карточки без оформления

Вариант `plain` убирает поверхность, рамку и внутренний отступ.

::::grid {columns=3 gap=2}
:::card {variant=plain}
#### Содержание

Компонент отвечает за смысл.
:::
:::card {variant=plain}
#### Композиция

Grid отвечает за ширину и расстояние.
:::
:::card {variant=plain}
#### Адаптивность

Колонки складываются на узком экране.
:::
::::
