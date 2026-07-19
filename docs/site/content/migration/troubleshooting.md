# Диагностика частых проблем

Эта страница относится к переходу со старой Docara. Общие ошибки новой сборки
разобраны в [решении проблем](/troubleshooting/).

## Старые настройки не влияют на страницу

Portable режим не читает `config.php`, `.settings.php` и `source/_core`.
Перенесите только поддерживаемые значения в `docara.json`, `_section.json` или
`<page>.page.json`, затем проверьте
[разрешённый план страницы](/reference/resolved-plan/).

## После переноса пропал custom tag или Blade

Portable Markdown не исполняет Blade, PHP callbacks, raw HTML и произвольные
legacy tags. Выберите поддерживаемый native Markdown, typed-компонент Docara
или Smart-компонент из [каталога](/components/catalog/). Если эквивалента нет,
зафиксируйте потребность как requirement, а не как скрытый no-op.

## Старый URL компонента возвращает 404

Девять ручных страниц компонентов выведены в пользу generated catalog. Таблица
старых и новых маршрутов находится на [странице миграции](/migration/).
Обязательную публичную совместимость обеспечьте отдельным hosting redirect и
проверьте его до переключения.

## `init --portable` отказывается работать в каталоге

Это ожидаемо, если найдены legacy `config.php`, `.settings.php`, `source/` или
существующие starter-файлы. Создайте отдельный пустой каталог. Не удаляйте
исходники только ради прохождения preflight.

## Portable build просит Node.js

Обычный portable build не должен запускать Node. Проверьте, что вы используете
`php vendor/bin/docara build ...`, а не legacy Mix/Vite script. Node и Vite
относятся только к разработке исходных ассетов темы.

## После миграции меню или порядок отличаются

Создайте каталоги и страницы-разделы, затем задайте `title` и
`navigation.order` в `_section.json`. Не переносите готовый HTML меню или
callback `getMenu`. Проверьте активный путь и вложенность на desktop и mobile.

## Нельзя архивировать docara-mix

Zero-reference scan ещё нашёл package, config, lockfile, CI или documentation
consumer. Завершите миграцию каждого найденного проекта и повторите его clean
install/build/watch acceptance. Само архивирование repository не исправляет
активные зависимости.

## Новый сайт готов, но переключение рискованно

Не переключайте hosting без timestamped backup текущего каталога, записанного
document root, проверенного rollback и сравнения обязательных URL. Сначала
проверьте staging через HTTP, затем сохраните digests staging и опубликованного
дерева.
