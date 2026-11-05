---
noindex: true
---

# Useful project components

These examples live in the site's own `smart/` folder and load without changes to the Docara engine. The page opens from the [“Your own project component”](/en/examples/extensions/) example and is not shown in the menu.

## Install command

Choose the operating system, installation method, package, version and options. The component only builds and copies the command. It downloads and runs nothing.

:::project.install-builder
{"title":"Build the install command","package":"simai/docara","version":"^3.0"}
:::

## Product configurator

The list changes the base plan, the checkboxes add options and the total is recalculated in the browser. The demo sends no data and creates no order.

:::project.product-configurator
{"title":"Configure the sample product","base_price":2500,"team_price":4500,"business_price":8000,"currency":"₽"}
:::
