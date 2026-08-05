# Branding и тема

Branding отвечает за title/label/logo/favicons/mode/size. Reader appearance управляет `theme`, `modal_blur` и `ui_radius` только через allowlisted values.

```json
{
  "branding": {"title": "Docara", "mode": "compact", "size": "large", "logo": "assets/docara-mark.svg", "favicon": "assets/favicon.ico"},
  "settings": {"theme": "system", "modal_blur": "none", "ui_radius": "default"}
}
```

`ui_radius` выбирает Framework-owned CSS variable `--sf-radius--ui`: compact/default/comfortable меняют один токен, а components используют свои variables с fallback на него. `modal_blur=none` убирает backdrop blur поиска и настроек по умолчанию. Произвольный CSS из config не принимается.
