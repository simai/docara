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

## Что проверяется

- manifest `.docara/resolved-page-plans.json`;
- соответствие заявленных страниц физическому output;
- local routes, links и fragments;
- duplicate HTML IDs и fragment encoding;
- опубликованные content assets;
- search index и pinned search runtime;
- redirect receipt, статические redirect pages и их targets;
- effective component catalog и generated catalog pages;
- точная asset projection Simai Framework;
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

## Проверка воспроизводимости

Для release candidate соберите сайт дважды из одного immutable source tree в
два пустых destination и сравните canonical tree digest. Mutable worktree или
старый ignored `build_*` не является exact evidence.

[Почему output должен быть детерминированным](/build/determinism/).
