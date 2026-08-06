# Markdown

Начинайте страницу с одного H1. Дальше используйте обычные H2–H6, абзацы,
списки, ссылки, изображения, цитаты, fenced code и таблицы.

Перед H1 можно добавить ограниченный front matter с метаданными материала:

```markdown
---
title: Установка
description: Подготовьте первый сайт Docara.
tags: [start, install]
draft: false
translation_key: guide.install
---

# Установка
```

Поддерживаются только `title`, `description`, `tags`, `draft` и
`translation_key`. Layout, HTML и произвольные поля здесь запрещены. Страница
с `draft: true` не публикуется. Ошибка front matter указывает исходный файл,
строку и колонку.

```markdown
# Установка

Коротко объясните результат страницы.

## Шаги

1. Установите пакет.
2. Инициализируйте сайт.

| Команда | Результат |
| --- | --- |
| `docara build production` | Статический production-каталог |
```

Заголовки получают детерминированные Unicode IDs. При переименовании заголовка
меняется fragment URL, поэтому после правки deep links повторяйте
`verify-static`.

Fenced code остаётся читаемым статическим `<pre><code>`, а закреплённый
SIMAI Framework прогрессивно добавляет один заголовок с языком и доступной
кнопкой копирования, подсветку синтаксиса и локальную прокрутку длинных строк. Копируется только
точное содержимое `<code>`, без интерфейса. Длинная строка прокручивается
внутри одного code surface. Если runtime или словарь неизвестного языка
недоступен, исходный код остаётся безопасным читаемым fallback.

## Ссылки и изображения

Используйте относительные source assets и root routes сайта. `base_url`
добавляется генератором там, где это предусмотрено контрактом. Небезопасные
протоколы отклоняются.

## Rich content

Raw HTML удаляется. Если обычного Markdown недостаточно:

1. откройте [справочник компонентов](/components/);
2. выберите запись lifecycle `supported`;
3. скопируйте exact call со страницы записи;
4. пересоберите и проверьте сайт.

[Общие правила директив](/components/syntax/).

Для общей оформленной полосы используйте
[Surface](/components/surface/), а для первого экрана —
[Hero](/components/hero/). Hero/Showcase/Promo уже используют общую Surface
presentation: не вкладывайте их в Surface и не пытайтесь добавлять CSS,
template path или отдельный background callback.

Не копируйте параметры компонента из старой страницы или другого проекта:
фактический технический contract принадлежит machine catalog текущей сборки,
а объяснение — Markdown-owner страницы компонента.

## Примеры директив

Внутри обычного fenced code строки директив остаются текстом и безопасно
показываются читателю:

````markdown
```markdown
:::ui.<component>
{"<prop>":"<value>"}
:::
```
````

## Проверка

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Verifier обнаружит отсутствующий local route/fragment, duplicate HTML ID и
небезопасную fragment encoding.
