# Production-path previews

Preview не имеет собственного renderer. `PreviewKernel` вызывает те же parser/compiler, DesignRegistry, SmartComponentGateway, renderer, asset collector, LayoutComposer и PageBuilder; output помечен preview-purpose и production verifier его не принимает.

## Полная композиция

:::internal_preview {size=tall title="Композиция regions"}
[Открыть результат](/ru/demonstrator-results/regions-composed/)
:::

## Docs preset

:::internal_preview {size=tall title="Docs preset"}
[Открыть результат](/ru/demonstrator-results/preset-docs/)
:::

## Landing preset

:::internal_preview {size=tall title="Landing preset"}
[Открыть результат](/ru/demonstrator-results/preset-landing/)
:::

Команда `docara preview smart|region|layout|page` публикует isolated tree в `.docara-preview/output/<target>/`. `verify-static` обязан вернуть `PREVIEW_BUILD_PURPOSE_FORBIDDEN`; для production нужен обычный full build.
