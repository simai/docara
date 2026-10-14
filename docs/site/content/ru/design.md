# Дизайн и интерфейс

Docara собирает интерфейс как LEGO на трёх уровнях: page Layout задаёт регионы, Section наполняет region разрешёнными slots/blocks, а Smart/View рисует конечный элемент. Это конфигурация из принятых registry descriptors, а не второй шаблонный движок.

## Разделы

- [Композиционная модель и insertion chain](/ru/design/composition/)
- [Матрица интерфейса](/ru/design/interface/)
- [Project shell contribution](/ru/design/project-shell/)
- [Production-path previews](/ru/design/previews/)

## Effective Design Atlas

:::atlas_index {kind=layout,section,block,view,binding,preset}
:::

Atlas строится командой `docara atlas --json` только из admitted DesignRegistry, SmartRegistry и BindingRegistry. Его fingerprint входит в build-owned projection; ручного списка компонентов здесь нет.
