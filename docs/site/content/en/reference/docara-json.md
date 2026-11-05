---
description: Every docara.json field with its description, default value, type and validation rule. The tables are built at build time from the JSON Schema.
tags: [docara-json, settings, fields, schema]
---

# docara.json

`docara.json` is the settings file of the whole site. Only here do you set
the content root, the base and public address, the language registry and the
address rules, the documentation version, the redirects file, the reader
panel, the Framework lock and the namespace of the project's Smart
components. The presentation, menu and reading fields set here serve as
defaults for all pages.

## How to read the field tables

The tables below are built at build time from the JSON Schema and the Docara
code, so they include every `docara.json` field. The "Default" line under a
description shows the value Docara uses when the field is not set. If there
is no such line, there is no default: the field is required, or the feature
is off without it. "Technical details" give the type, the validation rule and
the place in the schema.

You can also set the presentation, menu and reading fields in `section.json`
and `<page>.page.json`: see [section.json and page.json](/en/reference/section-and-page/).
How levels and inheritance work is described in
[Site, section and page](/en/settings/levels-and-inheritance/).

## Basics

:::schema_reference {schema=site scope=site fields=schema,version,title,preset,framework_lock,content_root,documentation_version}
:::

## Address and publication

More in [Site address and search engines](/en/settings/site-address/).

:::schema_reference {schema=site scope=site fields=base_url,site_url,discovery,redirects_file,not_found,page_transforms,portal}
:::

## Languages and translations

More in [Multilingual site](/en/authoring/multilingual-site/).

:::schema_reference {schema=site scope=site fields=locale,default_locale,locales,locale_routing,translation_tracking}
:::

## Appearance

More in [Brand and theme](/en/settings/branding-and-theme/) and
[Layout regions](/en/settings/layout-and-regions/).

:::schema_reference {schema=site scope=site fields=theme,branding,settings,layout,effects}
:::

## Menu, reading and search

More in [Menu and sections](/en/authoring/navigation/),
[Reading context](/en/settings/reading/), [Search](/en/settings/search/) and
[Reader panel](/en/settings/reader-preferences/).

:::schema_reference {schema=site scope=site fields=navigation,header_navigation,reading,search,reader_preferences}
:::

## Examples, code and extensions

:::schema_reference {schema=site scope=site fields=examples,code,documentation_tracking,smart}
:::
