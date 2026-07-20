# Изменения

## Unreleased

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
- Побайтово сохранённый `PortableHtmlRenderer` доступен как ограниченный
  rollback через `DOCARA_PORTABLE_PUBLISHER=legacy`.

Эти изменения не являются заявлением о production readiness, готовности всех
Smart-компонентов или публичном release.
