---
description: Как simai-framework.lock.json закрепляет поставку SIMAI Framework, manifests и хеши файлов и какие проверки проходит каждый Smart до публикации.
---

# Framework lock и providers

`simai-framework.lock.json` фиксирует immutable runtime tuple, admitted
manifests, Smart projection, typography projection и локальный Core/runtime
packet. Moving branch/latest запрещены; manifest пакета проверяет каждый
distribution-relative path и SHA-256 до публикации. Каждый Smart дополнительно
проходит schema, provider ownership, namespace, template/assets/hydration и
hash checks.

Файл создаёт `docara init` вместе с проектом. Вручную его обычно не правят:
сборка сверяет закреплённые файлы по хешам. Таблицы ниже показывают, что
именно закреплено.

## Версия и сборка SIMAI Framework

:::schema_reference {schema=framework-lock scope=lock fields=schema,version,runtime,nonclaims}
:::

## Файлы и ресурсы

:::schema_reference {schema=framework-lock scope=lock fields=runtime_projection,asset_projection,dynamic_asset_projection,package_projections}
:::

## Значки и шрифты

:::schema_reference {schema=framework-lock scope=lock fields=icon_projection,typography_projection}
:::

## Описания компонентов

:::schema_reference {schema=framework-lock scope=lock fields=manifests}
:::

## Проверка Smart-компонентов

Effective support смотрите через `docara atlas --json` и `docara inspect smart <id> --json`. Neutral identity — `sf.smart_artifact_abi / 1.0.0 / sf-smart-artifact-abi-v1`; storage compatibility alias/provider adapter/template ABI показываются отдельно.

Принятый form wave: `ui.input`, `ui.dropdown`, `ui.checkbox`; populated dropdown зависит от exact-pinned text-only `ui.list-item`. Related icons/avatars/tags остаются nonclaims.
