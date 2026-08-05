# Компоненты SIMAI Framework

Docara поддерживает только immutable Framework artifacts, которые прошли owner admission и unchanged-artifact cross-host proof. Статус берётся из effective Atlas, а не из списка в этой странице.

## Поддерживаемые Framework Smart

:::atlas_index {kind=smart owner=ui support=supported}
:::

## Как использовать

```markdown
::::ui.dropdown {label="Тариф"}
:::ui.list-item {slot=options type=text value=team label="Командный"}
:::
::::
```

Manifest, view, preset, slot, template, assets и hydration проверяются до render. `ui.dropdown` зависит от принятого text-only `ui.list-item`; icons, avatars, tags и raw related SF surfaces не объявлены supported.

## Статусы

- `supported` — exact-pinned artifact принят и доступен проекту;
- `compatibility` — доказательство совместимости, но не обещание нового product scenario;
- `proposal` — не принят и не может использоваться в public example;
- `rejected` — fail-closed, даже если похожий upstream component существует.

## Доступность и ошибки

Label, keyboard, focus-return и объявленные slots принадлежат owner artifact. Unknown component, version/hash mismatch, неразрешённый child или unsafe path останавливают сборку до template execution.

