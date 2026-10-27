---
description: "Тестирование изменений в Docara: какие наборы тестов запускать перед передачей изменения и что проверить в собранной документации."
---

# Тестирование

Перед передачей изменения запускайте проверки от узких к полным.

## Сборка сайтов

Тесты разбора Markdown, меню, оглавления, каталога компонентов и сборки
сайта:

```bash
vendor/bin/phpunit --do-not-cache-result \
  tests/Unit/PortableConfigurationTest.php \
  tests/Unit/PortableDocumentOutlineBuilderTest.php \
  tests/Unit/PortableMarkdownRendererTest.php \
  tests/Unit/PortableNavigationBuilderTest.php \
  tests/Unit/StaticBuildVerifierTest.php \
  tests/Unit/FrameworkComponentRuntimeTest.php \
  tests/Unit/EffectiveComponentCatalogTest.php \
  tests/Unit/DocumentationContractTest.php \
  tests/Unit/DocumentationExamplesTest.php \
  tests/PortableSiteBuilderTest.php \
  tests/PortableInitCommandTest.php \
  tests/PortableDocumentationSiteTest.php
```

## Весь репозиторий

```bash
vendor/bin/phpunit --do-not-cache-result
vendor/bin/pint --test
composer validate --strict --no-check-publish
git diff --check
```

Сгенерированные справочники проверяются отдельными командами с `--check`:

```bash
php scripts/capture-component-source-metadata.php --check
php scripts/capture-component-parameters.php --check
php scripts/capture-lang-reference.php --check
php scripts/capture-diagnostic-code-coverage.php --check
php scripts/capture-icon-names.php --check
```

Дополнительно проверьте: разбор JSON, создание и сборку проекта с нуля,
одинаковый результат двух сборок, ссылки в исходниках и в готовых страницах,
отсутствие подвижных версий (`main`, `latest`) и вид сайта в браузере — для
документации и лендинга, в светлой и тёмной теме, на широком и узком экране.

Длинные тесты запускайте по одному набору за раз: часть старых тестов делит
общую временную папку.

## Документация

```bash
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
php ../../scripts/verify-page-weight.php build_production --config=page-weight.json
php ../../docara serve production --host=localhost --port=8000 --no-build
```

В собранной документации должно быть:

- один заголовок H1 на каждой странице автора;
- ровно один Markdown-файл на каждый публичный адрес и ни одной страницы,
  созданной без такого файла;
- ни одной битой локальной ссылки;
- `main_source=pagebuilder_document_ir` во всех записях квитанции сборки и
  одинаковый HTML при полной сборке и сборке одной страницы.

Машинный каталог компонентов — производный результат сборки: он не создаёт
публичных страниц.

### Вес и загрузка страниц

Квитанция `build_production.docara/performance.json` показывает для каждой
страницы, какие стили, шрифты и скрипты загружаются сразу, число запросов,
объём и самые крупные общие файлы. `verify-static` пересчитывает её и
отвергает устаревшие размеры и хеши. Пороги веса задаёт
`verify-page-weight.php` — см. раздел [«Вес страниц»](/ru/build/verify/#вес-страниц);
подбирайте их для своего проекта по замерам: один общий предел для
справочника, лендинга и простого сайта не подходит.

### Контекст чтения

Обязательны отрицательные проверки: глубина заголовков 1 и 7, повторяющиеся
`id` в HTML, ссылка на несуществующий якорь и неверное кодирование адреса.
В браузере проверьте: оглавление справа на широком экране, раскрывающееся
оглавление на планшете и телефоне, обычные ссылки, видимую рамку фокуса и
кнопки «Предыдущая» и «Следующая» без горизонтальной прокрутки.

## См. также

- [Начало разработки](/ru/development/getting-started/)
- [Архитектура](/ru/development/architecture/)
- [Проверка сборки](/ru/build/verify/)
