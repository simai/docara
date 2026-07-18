# Button

Button запускает действие. Используйте понятный глагол и обязательно задавайте
доступное имя.

:::ui.button
{"text":"Собрать сайт","size":"1","type":"default","scheme":"primary","loading":false,"disabled":false,"native-type":"button","aria-label":"Собрать сайт Docara"}

:::

## Код примера

```markdown
:::ui.button
{"text":"Собрать сайт","size":"1","type":"default","scheme":"primary","loading":false,"disabled":false,"native-type":"button","aria-label":"Собрать сайт Docara"}

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
