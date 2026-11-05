---
description: Every section.json and page.json field with its description, default value, type and validation rule, the shared presentation fields and the page metadata keys.
tags: [section-json, page-json, front-matter, settings, fields]
---

# section.json and page.json

`section.json` configures a section: the overview page beside it and all
pages of the folder. `<page>.page.json` refines one page and also sets its
`description` and `slug`. How levels are inherited and reset is described in
[Site, section and page](/en/settings/levels-and-inheritance/).

## How to read the field tables

The tables are built at build time from the JSON Schema. If a field is not
set in the file, the value from the level above applies: from the nearest
`section.json` of a parent folder or from `docara.json`. The "Default" line
under a description shows the built-in Docara value when the field is set
nowhere. "Technical details" give the type, the validation rule and the place
in the schema.

## section.json

### Basics

:::schema_reference {schema=section scope=section fields=schema,version,title,locale,preset}
:::

### Appearance

:::schema_reference {schema=section scope=section fields=branding,settings,layout}
:::

### Menu, reading and search

:::schema_reference {schema=section scope=section fields=navigation,header_navigation,reading,search}
:::

### Examples and code

:::schema_reference {schema=section scope=section fields=examples,code}
:::

## page.json

### Basics

:::schema_reference {schema=page scope=page fields=schema,version,title,description,slug,locale,preset}
:::

### Appearance

:::schema_reference {schema=page scope=page fields=branding,settings,layout}
:::

### Menu, reading and search

:::schema_reference {schema=page scope=page fields=navigation,header_navigation,reading,search}
:::

### Examples and code

:::schema_reference {schema=page scope=page fields=examples,code}
:::

## Shared presentation fields

These branches are the same on all three levels. The table is built from
`presentation.schema.json`.

:::schema_reference {schema=presentation scope=shared}
:::

## Markdown metadata

The metadata block (front matter) at the start of a Markdown page accepts
only the keys `title`, `description`, `tags`, `draft`, `noindex`,
`translation_key`, `profile` and `composition_recipe`. What each key does is
described in [Markdown](/en/authoring/markdown/#page-metadata).
