---
description: "Безопасность сборки: какие пути, файлы и данные Docara отклоняет, почему сборка останавливается и где искать коды ошибок."
---

# Безопасность

Portable builder работает fail closed. Он не пытается угадать намерение при
невалидном JSON, неизвестном prop или опасном пути.

Если вы начинаете с видимого симптома, используйте
[общий troubleshooting](/troubleshooting/). Эта страница нужна для точной
границы и error codes.

## Основные границы

- Все configuration paths относительны и остаются внутри корня сайта.
- Symlink в корне, content path, lock или destination отклоняется.
- Raw HTML удаляется; небезопасные ссылки запрещаются.
- `_docara` и `.docara` зарезервированы.
- Output collision проверяется до очистки существующей сборки.
- Redirects принимают только внутренние source/target без query и fragment;
  collision, self redirect, chain, cycle и отсутствующий target отклоняются
  до очистки существующей сборки.
- Каждая страница обязана находиться в `content_root` ровно одной объявленной
  локали; BCP 47 tag страницы должен присутствовать в реестре `locales`.
- Scalar props экранируются host renderer.
- Поисковый индекс проходит schema до очистки output; browser runtime принимает
  только same-origin URL внутри настроенного `base_url` и создаёт результаты
  через DOM API с `textContent`.
- `navigation.hidden` и `search.indexed: false` не закрывают доступ к HTML.
  Поисковый JSON также публичен. Секретные данные нельзя помещать в portable
  source или собранный сайт.
- Ссылки на локальные фрагменты проверяются вместе с файлами. Повторяющийся
  HTML `id`, отсутствующий якорь и небезопасная percent-кодировка делают
  статическую проверку неуспешной.

## Что запрещено в настройках и проекте

Docara принимает IDs и данные, но не исполняемые project hooks.

Fail-closed запрещены:

- traversal, absolute/output-root escape и include вне roots;
- symlink/junction-like escape, hardlink target и case collision;
- class, callback, PHP/template/filesystem path из Markdown/config;
- raw HTML/CSS и external embed вне explicit allowlist/sandbox;
- duplicate namespace/ownership и implicit package shadowing;
- запись scaffold/QA/preview в engine, lock, generated или external roots;
- stale/hash-mismatched apply plan.

Ошибка не должна менять ни одного байта вне verified project root. Diagnostics используют safe relative path/pointer/location и не публикуют secrets/private absolute paths.

## Чего документация не обещает

Документация описывает проверенный локальный контур. Она не заявляет поддержку
каждого компонента SIMAI Framework, полностью автономную работу без сети или
интеллектуальный поиск.

## Коды ошибок

Коды ошибок с причиной и способом исправления собраны в
[справочнике кодов ошибок](/reference/errors/):

- [страницы и Markdown](/reference/errors/content/) — front matter, блоки,
  компоненты, ссылки и картинки;
- [настройки сайта](/reference/errors/site/) — перенаправления, языки,
  `lang.json`, поиск, эффекты, страница 404;
- [обновление Docara](/reference/errors/upgrade/);
- [проверка сборки](/reference/errors/verify-static/) — отметки
  `verify-static`, например `@missing-fragment` и `@duplicate-html-id`.

Точный код ошибки полезнее общего «сборка не работает»: сначала исправьте
исходный файл, затем повторите сборку и [проверку результата](/build/verify/).
Не исправляйте каталог `build_*` вручную.
