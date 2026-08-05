# Полезные Framework и project-сценарии

Это не вторая gallery. Страница связывает принятые artifacts с задачей и реальным production output; support labels проецируются из Atlas.

## Принятые Framework controls

:::atlas_index {kind=smart owner=ui support=supported}
:::

`ui.input` редактирует package/version, `ui.checkbox` включает allowlisted options, а `ui.dropdown → ui.list-item(type=text)` выбирает OS/method/tariff. Raw `items`, icons, avatars, tags и прочие непринятые related surfaces не поддерживаются.

## Project-owned scenarios

:::atlas_index {support=project}
:::

:::internal_preview {size=tall title="Install builder, configurator и footer"}
[Открыть результат](/ru/project-demos/)
:::

- install builder формирует безопасную copy-only Composer command;
- product configurator локально пересчитывает tariff/options/summary;
- footer contribution входит через admitted `shell.footer` capability;
- network/order/payment/command side effects отсутствуют.

## Как читать статусы

`supported` и `project` — доступные public scenarios. `compatibility` означает лишь доказательство совместимости. Proposal/rejected artifacts не попадают в admitted Atlas и не могут быть показаны как рабочий public example.

