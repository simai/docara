# Framework lock и providers

`simai-framework.lock.json` фиксирует immutable runtime tuple, admitted manifests и asset projection. Moving branch/latest запрещены; каждый Smart дополнительно проходит schema, provider ownership, namespace, template/assets/hydration и hash checks.

:::schema_reference {schema=framework-lock scope=lock}
:::

Effective support смотрите через `docara atlas --json` и `docara inspect smart <id> --json`. Neutral identity — `sf.smart_artifact_abi / 1.0.0 / sf-smart-artifact-abi-v1`; storage compatibility alias/provider adapter/template ABI показываются отдельно.

Принятый form wave: `ui.input`, `ui.dropdown`, `ui.checkbox`; populated dropdown зависит от exact-pinned text-only `ui.list-item`. Related icons/avatars/tags остаются nonclaims.
