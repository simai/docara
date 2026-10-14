# Компоненты

Docara выводит компоненты SIMAI Framework по коротким директивам Markdown.
Зафиксированный выпуск Framework поставляется вместе с Docara, поэтому
устанавливать ничего не нужно.

## Уведомление

:::alert {type=success}
#### Сборка прошла

Сайт собран и проверен.
:::

## Кнопка

:button[На главную]{href=../ type=outline scheme=on-surface size=1 icon=arrow_back}

## Компонент Framework с настройками в JSON

Компонент может получать настройки в формате JSON:

:::ui.button
{"text":"Продолжить","size":"1","type":"default","scheme":"primary"}
:::
