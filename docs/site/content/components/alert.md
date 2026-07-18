# Alert

Alert сообщает о результате, предупреждении или ошибке. Текст должен объяснять
состояние сам по себе, а не только цветом.

:::ui.alert
{"type":"success","variant":"default","title":"Сборка завершена","supporting-text":"JSON и Markdown прошли проверку.","closable":false,"aria-label":"Сборка завершена успешно"}

:::

## Код примера

```markdown
:::ui.alert
{"type":"success","variant":"default","title":"Сборка завершена","supporting-text":"JSON и Markdown прошли проверку.","closable":false,"aria-label":"Сборка завершена успешно"}

:::
```

## Параметры

| Prop | Допустимые значения |
| --- | --- |
| `type` | `clear`, `info`, `danger`, `warning`, `success` |
| `variant` | `default`, `flat`, `outlined` |
| `title` | Непустая строка до 160 символов |
| `supporting-text` | Непустая строка до 500 символов |
| `closable` | Boolean |
| `aria-label` | Доступное имя до 160 символов |

`id` автор не задаёт: Docara создаёт стабильный идентификатор из пути страницы
и порядкового номера компонента. Явный `id` отклоняется, а не игнорируется.

`closable: true` в текущей зафиксированной паре намеренно отклоняется: в
принятой asset projection нет требуемой зависимости `sf-icon-button`.
