# G1C.2 — provider, context and admission cleanup

Status: `PASS_WITH_CROSS_HOST_NONCLAIM`
Implementation: `2d779107add39155edc26537929323aebe066984`

- portable provider records `sf5.smart.template.v1`; legacy package templates
  use an explicit `docara.legacy.object-view.v1` adapter;
- `SmartTemplateContext` carries manifest, resolved view, preset, merged props,
  children/slot strings and host metadata internally, while portable templates
  receive only the SF5 array surface;
- Framework consumer narrowing is loaded from each exact lock manifest record;
  `FrameworkConsumerPolicy` contains no component map;
- locked `ui.notice` proves an additional Framework provider artifact can be
  resolved and rendered without a component-ID engine change;
- namespace duplication, moving revision, symlink component/template, unsafe
  asset and unknown adapter/strategy remain fail-closed.

Focused matrix:

```text
Sf5CrossHostSmartCompatibilityTest, Sf5SmartArtifactV1ContractTest,
SmartProviderRegistryTest, SmartRegistryTest, ProjectLocalSmartRuntimeTest,
SmartGenericRuntimeTest, SmartComponentGatewayTest,
FrameworkComponentRuntimeTest, DeclarativeRenderingTest,
PortableSearchTextExtractorTest, PortableConfigurationTest

80 tests, 699 assertions, PASS
```

Nonclaim: exact SF5 currently loses selected view data and render-shortcut slot;
therefore the full cross-host context gate is not passed.
