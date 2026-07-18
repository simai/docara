# Диагностика частых проблем

## `SCHEMA_VALIDATION_FAILED`

Проверьте имя поля, тип и допустимые enum в [справочнике схем](/reference/schemas/).
Не добавляйте неизвестное поле «на будущее».

## `FRAMEWORK_PROP_REQUIRED`

Это означает несовместимость принятого manifest и его безопасных defaults, а
не просьбу копировать внутренние props в каждый Markdown-вызов. Автор задаёт
только параметры из страницы компонента; Docara дополняет зафиксированные
defaults и валидирует итоговый вызов.

## `FRAMEWORK_PROP_MANAGED`

Prop управляется движком. Например, `ui.alert:id` Docara вычисляет
детерминированно; удалите его из авторского JSON.

## `FRAMEWORK_COMPONENT_UNSUPPORTED`

Компонент отсутствует в принятом component-call contract. Наличие похожего
custom element в upstream Framework не разрешает использовать его по аналогии.

## Tabs не собирается

Это ожидаемый blocker текущего контура: exact asset/slot contract ещё не принят.
Используйте последовательные Markdown-разделы.

## Closable Alert отклонён

В текущей projection отсутствует `sf-icon-button`; задайте `closable: false`.

## Сайт без оформления

Проверьте Network для exact Core CSS/JS и локальных файлов
`_docara/framework`. Текущий Core является сетевой exact-commit зависимостью.

## Неверная ссылка

Portable renderer не преобразует `.md` в route автоматически. В опубликованном
содержании используйте итоговые URL вида `/section/page/` и проверяйте все
внутренние ссылки после сборки.
