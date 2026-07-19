# Перенаправления

Перенаправление (`redirect`) нужно, когда опубликованный маршрут меняется, а
старые ссылки должны продолжить вести к содержательно эквивалентной странице.
В portable Docara это декларативный input сборки, а не Markdown-заглушка и не
project PHP.

## Подключение

Укажите файл на уровне сайта:

```json
{
  "schema": "docara.site.v1",
  "redirects_file": "redirects.json"
}
```

Создайте `redirects.json`:

```json
{
  "schema": "docara.redirects.v1",
  "version": 1,
  "redirects": [
    {
      "from": "guide/old-install",
      "to": "start"
    }
  ]
}
```

`from` и `to` записываются как route slugs без начального и конечного `/`.
Пустой `to` означает корневую страницу текущего `base_url`; пустой `from`
запрещён, потому что корень уже является обычной сгенерированной страницей.
Builder применяет `base_url` самостоятельно.

## Что создаёт сборка

Для source route появляется статический `index.html` с:

- `canonical` на target;
- `robots: noindex`;
- meta refresh;
- обычной видимой ссылкой на тот же target.

Дополнительно `.docara/redirects.json` фиксирует SHA-256 канонически
нормализованного source, locale, `documentation_version`, `base_url`, output и
итоговые URL. Это receipt для `verify-static` и публикации. Форматирование JSON
и порядок записей не меняют этот хэш.

Статическая fallback-страница не обещает HTTP status `301` или `308`: статус
зависит от hosting. Если он обязателен для публичного SEO-переноса, серверное
правило должно быть сгенерировано из того же проверенного списка и принято
отдельно.

## Fail-closed граница

Сборка останавливается до очистки предыдущего output, если:

- target не является существующей сгенерированной страницей;
- source совпадает со страницей, каталогом, ассетом или служебным маршрутом;
- source и target одинаковы;
- target является source другого redirect, образуя chain или cycle;
- встречается внешний URL, query, fragment, traversal или небезопасная
  кодировка;
- файл проходит через symlink/hardlink boundary или не соответствует schema.

Порядок записей во входном массиве не влияет на результат: receipt и output
канонически сортируются.

## Языки

Redirect не доказывает эквивалентность содержания. Не направляйте английскую
страницу на русскую только ради отсутствия 404. Сохраните legacy reference
либо подготовьте страницу на том же языке в отдельном site variant.

После изменения выполните:

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Проверьте source URL и обычный target по HTTP из того же output.
