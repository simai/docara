# Решение проблем

Начните с симптома, исправьте исходный Markdown или JSON и повторите сборку.
Не правьте файлы внутри `build_local` или `build_production`: следующая сборка
заменит их.

## Composer не находит версию 3.x

Сообщение `Root composer.json requires simai/docara ^3.0, found
simai/docara[... v2.18.2] but it does not match the constraint` означает, что
Composer видит только ветку 2.x. Docara 3.x опубликована на Packagist, поэтому
обычно дело в устаревшем кэше метаданных Composer или в записи `repositories`
в `composer.json`, которая указывает на прежний репозиторий. Удалите такую
запись, очистите кэш и повторите установку:

```bash
composer clear-cache
composer require simai/docara:^3.0
```

## Composer требует расширение PHP

Сообщение вида `simai/docara requires ext-intl * -> it is missing from your
system` означает, что в PHP нет нужного расширения. Docara использует `ctype`,
`dom`, `intl`, `json`, `libxml` и `mbstring`. Установите расширение для той
версии PHP, которую запускает `php -v`, и проверьте его командой `php -m`.

## `init` отказывается создавать проект

`Initialization requires an empty target directory` означает, что в каталоге
уже есть файлы. `init` принимает пустой каталог, каталог только с
`composer.json`, `composer.lock` и `vendor/` после `composer require`, а также
служебные `.git/`, `.gitignore`, `.gitattributes` и `.DS_Store`. Создайте
проект в новом каталоге или перенесите посторонние файлы.

`Detected an existing Docara project` означает, что проект уже создан. Для
обновления служебных файлов используйте [`docara update`](/build/update/).

## Сборка не находит Node.js

Код `COMPOSITION_RECIPE_RUNTIME_REQUIRED` означает, что команда `node` не
найдена. Сборка запускает код композиции страниц SIMAI Framework в Node.js 20
или новее. Установите Node.js или укажите путь к нему:

```bash
export DOCARA_NODE_BINARY=/path/to/node
php vendor/bin/docara build production
```

Готовому сайту Node.js не нужен: он требуется только на машине сборки.

## Сборка остановилась на проверке JSON

В сообщении об ошибке найдите имя файла, JSON Pointer и код
`SCHEMA_VALIDATION_FAILED`.

1. Проверьте запятые, кавычки и тип значения.
2. Сверьте поле со [справочником схем](/reference/schemas/).
3. Удалите неизвестные поля вместо сохранения их «на будущее».
4. Повторите `php vendor/bin/docara build local`.

Если вы меняли настройки раздела, проверьте ближайший `section.json`; если
настройки одной страницы — соседний `<page>.page.json`.

Код `SECTION_DESCRIPTOR_LEGACY_NAME` означает, что найдено старое имя
`_section.json`. Переименуйте указанный в ошибке файл в `section.json`. Не
оставляйте оба варианта: каноническим является только `section.json`.

## Страница открывается без оформления

Не открывайте HTML через `file://`. Запустите HTTP preview:

```bash
php vendor/bin/docara serve local --host=localhost --port=8000
```

В Network должны успешно загружаться exact-commit Core CSS/JS и локальные
файлы `_docara/framework`. Если вы указываете document root в ServBay или
другом сервере, он должен смотреть на `build_local` либо `build_production`, а
не на исходный каталог проекта.

## После изменения показывается старая версия

`--no-build` намеренно показывает уже существующий результат. Остановите
preview через `Ctrl+C` и запустите `serve` без этого флага:

```bash
php vendor/bin/docara serve local --host=localhost --port=8000
```

Если Docara просит подтвердить очистку destination и вы отказываетесь, preview
не запускается: это защита от незаметного показа старой сборки.

## Вложенный маршрут или компонент возвращает 404

Для локального просмотра используйте `docara serve`: встроенный read-only
router понимает pretty URL и идентификаторы с точкой, например
`/components/alert/`.

На внешнем сервере включите обычное разрешение каталога в `index.html`. Не
переписывайте URL на PHP и не направляйте запросы к Markdown-исходникам.

## Компонент отклонён

Откройте [справочник компонентов](/components/) и найдите точный
идентификатор:

- `supported` имеет рабочий вызов и живой пример;
- недоступная запись показывает причину, безопасную замену и условие допуска;
- неизвестный prop нужно удалить;
- `FRAMEWORK_PROP_MANAGED` означает, что поле вычисляет Docara;
- `FRAMEWORK_COMPONENT_UNSUPPORTED` нельзя обходить похожим custom element.

Точный синтаксис поддерживаемых компонентов находится на их сгенерированных
страницах, а не в старых ручных маршрутах.

## Поиск не находит страницу

Проверьте итоговое наследование:

```json
{
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

После изменения обязательно пересоберите сайт. `search.enabled` создаёт общую
поисковую поверхность, а `search.indexed: false` исключает конкретную страницу
из индекса.

## `verify-static` сообщает о сломанной ссылке

Исправьте ссылку в исходном Markdown и повторите production build:

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

В содержании используйте итоговый маршрут `/section/page/`, а не путь
`page.md`. Для ссылки с `#fragment` проверьте фактический `id` заголовка после
сборки.

## Тема выглядит смешанной

Не добавляйте собственные классы `theme-light` или `theme-dark` в Markdown.
Выберите тему в настройках чтения, сбросьте пользовательский выбор и проверьте
режим `system`. Интерфейс должен использовать токены SIMAI Framework, а не
жёстко заданные цвета.

## Порт preview занят

Остановите прежний процесс через `Ctrl+C` либо выберите другой порт:

```bash
php vendor/bin/docara serve local --host=localhost --port=8010
```

Для переноса существующего сайта используйте отдельный
[порядок миграции](/migration/).
