# Изменения

## Unreleased

- Пользовательская документация приведена к accepted portable candidate
  `0f10afde92b93dd39703823ab22a2920b450a15b`; legacy `1.x` больше не
  сочетается с `init --portable` ни в одном quick-start маршруте.
- Зафиксировано, что одна сборка публикует все объявленные локали, а page
  sidecar необязателен для обычной Markdown-страницы.
- Добавлены практические руководства по брендированию, мультиязычному сайту,
  project language pack и безопасному обновлению с rollback.
- Добавлен maintainer-путь регистрации Layout, Section, Block, View Tree и
  Smart-компонента без авторской executable surface.
- Исправлен исполняемый пример fallback-графа и разделено владение canonical
  component contract, language-pack presentation и exact fixtures.

- Основной portable publisher переведён на декларативную цепочку
  `Layout -> Region -> Section -> Block -> Smart`.
- Авторские страницы, лендинги и сгенерированный каталог компонентов
  публикуются одним зарегистрированным full-document template.
- `ui.button` добавлен к поддержанным Smart-вызовам вместе с `ui.alert`.
- Навигация до четырёх уровней рендерится рекурсивно через зарегистрированный
  item template и подготовленные backend view models.
- CSS и JavaScript оболочки вынесены в общие immutable-ассеты
  `_docara/declarative-shell.css` и `_docara/declarative-shell.js`.
- Сборка стала транзакционной: действующий `build_*` заменяется только после
  полной успешной публикации кандидатного каталога.
- `.docara/resolved-page-plans.json` фиксирует publisher ID и SHA-256 каждой
  страницы.
- Добавлен `/examples/`: семь живых примеров областей, наследования, preset и
  Smart-компонентов с точными Markdown/JSON-источниками и хешированным receipt.
- Отключённые `sidebar` и `outline` больше не оставляют пустые layout-колонки
  или мобильную кнопку меню.
- Декларативный Smart resolver применяет manifest preset так же, как portable
  Framework runtime.
- Побайтово сохранённый `PortableHtmlRenderer` доступен как ограниченный
  rollback через `DOCARA_PORTABLE_PUBLISHER=legacy`.

Эти изменения не являются заявлением о production readiness, готовности всех
Smart-компонентов или публичном release.
