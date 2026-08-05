# Project shell contribution

Project-owned shell contribution — это data-only artifact в разрешённом `design/`/`smart/` root и ссылка на admitted binding ID. Он не требует изменения engine `src/`.

## Порядок подключения

1. Выберите собственный namespace в `docara.json`.
2. Создайте artifact через hash-bound scaffold dry-run/apply.
3. Проверьте manifest/schema/namespace/capabilities.
4. Убедитесь через `docara atlas --json`, что owner=`project` и support=`project`.
5. Выполните isolated preview на реальной странице.
6. Только затем соберите full output.

:::atlas_index {support=project}
:::

## Fail-closed границы

- project ID не затеняет package/Framework ID;
- binding-owned props нельзя переопределить config-ом;
- capability/region/slot collision отклоняется;
- traversal, symlink, hardlink, case collision и arbitrary path отклоняются;
- shell contribution не меняет outer page/head.

Пример footer из принятого Goal B показывает shell-role, а install builder и product configurator — content-role. Все три проходят существующие registries и Gateway.

