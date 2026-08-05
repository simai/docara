# Настройки читателя

`reader_preferences` разрешает локальную панель с allowlisted field IDs. Значения сохраняются только в браузере и не меняют source/build receipts.

```json
{"reader_preferences":{"enabled":true,"view":"side-panel","groups":[{"id":"appearance","fields":["appearance.theme","appearance.modal_blur","appearance.ui_radius"]}]}}
```

Панель обязана поддерживать keyboard, focus trap/return, Esc и reduced motion. Неизвестная group/field, duplicate или превышение schema limits отклоняется. Local storage не является project configuration provenance.

