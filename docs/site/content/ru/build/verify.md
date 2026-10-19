---
description: verify-static проверяет собранный сайт: читает статические файлы, не выполняет PHP проекта и не исправляет результат, а сообщает о нарушенном контракте.
---

# Проверка статического результата

`verify-static` проверяет уже собранный portable output. Команда не выполняет
project PHP configuration и не исправляет результат; она читает статические
файлы и fail closed сообщает о нарушенном контракте.

## Явный каталог

```bash
php vendor/bin/docara verify-static build_production
```

## Каталог по умолчанию

```bash
php vendor/bin/docara verify-static
```

Без аргумента проверяется `build_production`.

## Квитанции сборки

Verifier читает квитанции сборки из соседнего каталога
`build_production.docara/`. Для каталога, собранного Docara 3.0.0, где они ещё
лежали внутри результата в `.docara/`, используется прежнее место. Если
результат уже скопирован на хостинг без квитанций, укажите их каталог явно:

```bash
php vendor/bin/docara verify-static /var/www/docs --receipts=build_production.docara
```

Если квитанции лежат снаружи, а в проверяемом каталоге всё же есть `.docara/`,
проверка не проходит с маркером `@build-receipts-in-output`: служебные файлы
сборки не должны попадать в публикуемый каталог.

## Что проверяется

- manifest `resolved-page-plans.json` из квитанций сборки;
- соответствие заявленных страниц физическому output;
- local routes, links и fragments;
- duplicate HTML IDs и fragment encoding;
- опубликованные content assets;
- search index и pinned search runtime;
- redirect receipt, статические redirect pages и их targets;
- effective component catalog и физические Markdown-owner страниц компонентов;
- точная asset projection SIMAI Framework и общие файлы
  `docara-shell.<sha256>.css`: каждый файл — точные байты его исходных CSS,
  страница подключает файлы из квитанции в её порядке, их исходные CSS — это
  ровно набор страницы, и порядок каскада между ними сохранён;
- отсутствие лишней или пропавшей поверхности, охваченной manifest.

Успешный exit code означает, что проверенный каталог самосогласован. Он не
является разрешением production deployment или публичного release.

## Рекомендуемый цикл

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Откройте `http://127.0.0.1:8000`, проверьте root и вложенную страницу, поиск и
ссылку с fragment. Нажмите `Ctrl+C`.

`--no-build` важен: preview показывает именно каталог, который только что
прошёл verifier. Не используйте `file://`.

## Если проверка не прошла

1. Не публикуйте каталог.
2. Найдите первый диагностический marker или файл в выводе.
3. Исправьте source Markdown/JSON или владеющий runtime input.
4. Не правьте `build_production` вручную.
5. Повторите build и verify той же последовательностью.

Частые markers для links и fragments:

- `@missing-fragment`;
- `@duplicate-html-id`;
- `@unsafe-fragment-encoding`.

Builder error codes и security boundary находятся в
[справочнике ошибок](/reference/security-and-errors/). Если причина неясна,
сохраните команду, exit code, полный вывод, exact source revision и путь
проверяемого каталога для диагностики.

## Вес страниц

`verify-static` не ограничивает размер страниц. Чтобы тяжёлое изображение или
лишний шрифт не попали в публикацию незамеченными, запустите отдельную
проверку на собранном каталоге:

```bash
php vendor/simai/docara/scripts/verify-page-weight.php build_production --config=page-weight.json
```

Для каждой страницы скрипт складывает HTML и файлы, которые она заставляет
загрузить: таблицы стилей с `@import`, скрипты, preload, favicon, изображения
(из `srcset` берётся самый большой вариант), шрифты и фоны из CSS. Шрифт из
`@font-face` и `url()` в правиле считаются, только если на странице есть все
классы селектора; шрифт с `unicode-range` — если в тексте есть символ из
диапазона. Из начертаний одного семейства считается ближайшее к обычному
(400): остальные браузер загружает, только когда текст или иконка их
использует. Один файл на странице считается один раз. Файлы, которые скрипты
догружают во время работы, статический анализ не видит: на сайте документации
это около 0,8 МБ на страницу.

Проверка не проходит, если:

- страница тяжелее `max_page_bytes` (по умолчанию 3 МБ);
- изображение тяжелее `max_image_bytes` (по умолчанию 1 МБ);
- страница без вариантных иконок и иконок вне shell-подмножества загружает
  полный шрифт Material Symbols;
- у `<img>` с атрибутом `width` или `height` растр больше заданного размера
  более чем в `image_oversize_ratio` раз (по умолчанию 2,5).

Бюджеты задаются в JSON-файле, а для отдельных страниц — в `pages`:

```json
{
  "max_page_bytes": 4500000,
  "max_image_bytes": 1300000,
  "pages": {
    "/ru/components/diagram/": {"max_page_bytes": 6900000, "reason": "Mermaid"}
  }
}
```

Параметры командной строки `--max-page-bytes`, `--max-image-bytes`,
`--image-oversize-ratio` и `--base-url` переопределяют файл, `--json` выводит
отчёт для машин. Redirect-страницы не проверяются.

## Проверка воспроизводимости

Для release candidate соберите сайт дважды из одного immutable source tree в
два пустых destination и сравните canonical tree digest. Mutable worktree или
старый ignored `build_*` не является exact evidence.

[Почему output должен быть детерминированным](/build/determinism/).
