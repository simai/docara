---
description: "Как добавить на сайт свой интерактивный компонент без изменения Docara: папка smart/ с описанием, шаблоном, скриптом и стилями."
---

# Свой компонент проекта

Когда готовых блоков мало, сайт добавляет свой компонент. Здесь их три:
конструктор команды установки, калькулятор тарифа и ссылки в подвале. Docara
при этом не меняется: компоненты лежат в папке `smart/` самого сайта.

::::example {src="/ru/project-demos/" label="Результат"}
### Markdown страницы

:::code {src="../project-demos.md" lang=markdown highlight="13-15,21-23"}
:::

### manifest.json

:::code {src="../../../smart/project.install-builder/manifest.json" lang=json highlight="18-32"}
:::

### view/default.json

:::code {src="../../../smart/project.install-builder/view/default.json" lang=json}
:::

### template/default.php

:::code {src="../../../smart/project.install-builder/template/default.php" lang=php}
:::
::::

Выберите в окне примера другую систему или версию: команда установки
меняется сразу. Копировать её можно кнопкой «Копировать».

## Как это устроено

1. Каждый компонент — папка в `smart/` с именем вида `project.имя`.
2. `manifest.json` описывает компонент: его параметры (`props`), места для
   вложенных элементов (`slots`) и файлы стилей и скриптов.
3. `view/default.json` собирает компонент из готовых элементов SIMAI
   Framework: выпадающих списков `ui.dropdown`, полей `ui.input` и флажков
   `ui.checkbox`. Русские подписи лежат рядом, в `view/ru.json`: русская
   страница выбирает его параметром `"view":"ru"`.
4. `template/default.php` — разметка, которую Docara выводит при сборке. Скрипт
   из `assets/` оживляет её в браузере.

На странице компонент вызывается так же, как компоненты SIMAI Framework:

:::code {src="../project-demos.md" lang=markdown lines="13-15"}
:::

## Что можно поменять

| Файл | Что в нём меняется |
|---|---|
| `manifest.json` | Параметры компонента и разрешённые вложенные элементы |
| `view/default.json` | Какие элементы стоят внутри и с какими подписями |
| `template/default.php` | Разметка вокруг элементов |
| `assets/*.css`, `assets/*.js` | Внешний вид и поведение в браузере |

Компоненты примера ничего не отправляют по сети: конструктор только
составляет команду, а калькулятор считает итог в браузере.

## См. также

- [Компоненты SIMAI Framework](/ru/components/smart-ui/)
- [Подвал страницы](../region-footer/)
