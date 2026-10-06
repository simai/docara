# Настройки читателя

`reader_preferences` разрешает локальную панель с allowlisted field IDs. Значения сохраняются только в браузере и не меняют source/build receipts.

```json
{"reader_preferences":{"enabled":true,"view":"side-panel","groups":[{"id":"appearance","fields":["appearance.theme","appearance.font_size","appearance.content_width"]}]}}
```

Панель обязана поддерживать keyboard, focus trap/return, Esc и reduced motion. Неизвестная group/field, duplicate или превышение schema limits отклоняется. Local storage не является project configuration provenance.

Тема, размер шрифта и ширина страницы показываются всегда, даже если они не
перечислены в `groups`; скрыть их можно списком `hidden_fields`.

Поля `appearance.modal_blur` и `appearance.ui_radius` больше не показываются.
Если они остались в старом `docara.json`, сборка не останавливается: поле
просто пропускается, а сохранённые значения удаляются из хранилища браузера.

Кнопки «Скрыть содержание страницы» и «Режим чтения» рядом с хлебными крошками
не входят в панель. Их состояние хранится отдельно под ключом
`docara.reading.<site-hash>.v1`. Подробнее — в разделе
[Настройки чтения](/authoring/reader-settings/).
