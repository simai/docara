# Button

Button запускает действие. Используйте понятный глагол и обязательно задавайте
доступное имя. Ниже показан отключённый образец: он демонстрирует внешний вид,
но намеренно не обещает действие на странице справочника.

:::ui.button
{"text":"Демонстрационная кнопка","size":"1","type":"default","scheme":"primary","loading":false,"disabled":true,"native-type":"button","aria-label":"Демонстрационная кнопка без действия"}

:::

## Код примера

```markdown
:::ui.button
{"text":"Демонстрационная кнопка","size":"1","type":"default","scheme":"primary","loading":false,"disabled":true,"native-type":"button","aria-label":"Демонстрационная кнопка без действия"}

:::
```

## Основные параметры

| Prop | Допустимые значения |
| --- | --- |
| `size` | `1/3`, `1/2`, `1`, `2`, `3` |
| `type` | `default`, `tonal`, `outline`, `link` |
| `scheme` | `primary`, `secondary`, `on-surface` |
| `native-type` | `button`, `submit`, `reset` |
| `loading`, `disabled` | Boolean |
| `aria-label` | Доступное имя |

Сочетания `type` и `scheme` проверяются manifest. При `loading: true` значение
`disabled` тоже должно быть `true`. Docara отрисовывает кнопку, но не
привязывает к ней прикладное действие автоматически.
