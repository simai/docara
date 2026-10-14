# Размещение на хостинге

Результат `build production` — обычный набор статических файлов. Любой
статический хостинг отдаёт каталог `build_production/` как есть: nginx,
Apache, GitHub Pages, Netlify, S3-совместимое хранилище с веб-доступом и
другие. PHP, Composer и Node.js на сервере не нужны; они нужны только там, где
сайт собирается.

## Перед загрузкой

Соберите и проверьте именно тот каталог, который будете загружать:

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

`verify-static` не выполняет проектный PHP-код и проверяет статический
каталог: страницы из manifest и физические HTML-файлы, локальные ссылки и
`#fragment`, уникальность HTML `id`, ассеты, поисковый индекс, resolved page
plans, страницы redirects, каталог компонентов, проекцию SIMAI Framework и
receipts сборки. При ненулевом exit code каталог не загружайте. Подробности —
в разделе [Проверка статического результата](/build/verify/).

Загружайте содержимое `build_production/` целиком и без изменений. Не
открывайте страницы через `file://`: так не работают pretty routes, `base_url`,
поиск и загрузка ассетов. Для локального просмотра используйте
`serve production --no-build`.

## Маршруты

Каждая страница — каталог с `index.html`: маршрут `/guides/install/`
соответствует файлу `guides/install/index.html`. Хостинг должен отдавать
`index.html` для запроса каталога. Большинство статических хостингов делает
это по умолчанию.

nginx:

```nginx
server {
    root /var/www/docs/current;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Apache (`.htaccess` или конфигурация виртуального хоста):

```apache
DirectoryIndex index.html
```

## Размещение в подкаталоге

Если сайт открывается не с корня домена, а, например, по адресу
`https://example.com/docs/`, задайте `base_url` в `docara.json` до сборки:

```json
{
  "base_url": "/docs/"
}
```

Docara строит все ссылки, ассеты и поиск с этим префиксом, а `verify-static`
проверяет каталог относительно него. Загрузите содержимое `build_production/`
в каталог, который сервер отдаёт по `/docs/`. Не исправляйте ссылки вручную
после сборки. Для сайта проекта на GitHub Pages адрес обычно имеет вид
`https://<owner>.github.io/<repository>/`, поэтому `base_url` равен
`"/<repository>/"`.

## Redirects

Записи из `redirects.json` превращаются в обычные HTML-страницы по старому
адресу: `old/route/index.html` содержит canonical, `noindex`, meta refresh и
видимую ссылку на новую страницу. Настраивать redirects на сервере не нужно.
Если хостинг умеет отдавать HTTP 301, такие правила можно добавить отдельно,
но статические страницы redirects остаются в каталоге. Формат файла описан в
разделе [Redirects](/authoring/redirects/).

## Кеширование

Общие ресурсы сборки публикуются один раз в `<base_url>_docara/`. Файлы, у
которых SHA-256 или ревизия входит в путь, при изменении получают новый адрес,
поэтому их можно кешировать надолго:

- `_docara/docara-shell.<sha256>.css`;
- `_docara/brand/<sha256>.*`;
- `_docara/vendor/simai-framework/runtime/<revision>/**`.

Для них подходит `Cache-Control: public, max-age=31536000, immutable`.
HTML-страницы и остальные файлы `_docara/` без хеша в пути, например
`_docara/search-index.json` и `_docara/search.js`, отдавайте без `immutable`,
с проверкой актуальности по `ETag` или `Last-Modified`. Страницы подключают
часть ресурсов с параметром версии (`?docara_v=…`, `?sf_v=…`), поэтому CDN
должен учитывать query string в ключе кеша.

```nginx
location ~ ^/_docara/(docara-shell\.[0-9a-f]{64}\.css$|brand/[0-9a-f]{64}\.|vendor/simai-framework/runtime/) {
    add_header Cache-Control "public, max-age=31536000, immutable";
}
```

Для сайта в подкаталоге добавьте префикс `base_url` в начало выражения.

## Особенности площадок

- **GitHub Pages.** При публикации через GitHub Actions
  (`actions/upload-pages-artifact`) загрузите `build_production/` как
  артефакт. При публикации из ветки положите в её корень пустой файл
  `.nojekyll`: иначе Jekyll пропустит каталог `_docara/`, имя которого
  начинается с подчёркивания.
- **Netlify и похожие сервисы.** Укажите `build_production` как publish
  directory. Если сборка выполняется на стороне сервиса, в его окружении нужны
  PHP 8.2+, Composer и Node.js; проще собрать и проверить сайт в CI и загрузить
  готовый каталог.
- **S3-совместимое хранилище.** Включите режим статического сайта с index
  document `index.html`, чтобы адреса каталогов открывали страницы. Если
  хранилище закрыто CDN, проверьте, что CDN тоже отдаёт `index.html` для
  вложенных маршрутов.

## После загрузки

Откройте корень сайта и вложенную страницу, проверьте стили, поиск, ссылку с
`#fragment` и одну страницу redirect. Порядок с промежуточным каталогом,
digest и возвратом к предыдущей версии описан в разделе
[Публикация и rollback](/build/publish/).
