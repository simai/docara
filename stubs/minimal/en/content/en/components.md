# Components

Docara renders SIMAI Framework components from short Markdown directives. The
Framework release Docara pins is bundled with it, so nothing else needs to be
installed.

## Alert

:::alert {type=success}
#### Build passed

The site was built and checked.
:::

## Button

:button[Back to the home page]{href=../ type=outline scheme=on-surface size=1 icon=arrow_back}

## A Framework component from JSON

A component can also take its settings as JSON:

:::ui.button
{"text":"Continue","size":"1","type":"default","scheme":"primary"}
:::
