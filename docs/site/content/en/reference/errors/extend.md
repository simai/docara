---
description: Error codes of the menu and layout regions, your own shell composition, project components, layouts in design/ and recipes — with the cause and the fix.
tags: [error, layout, regions, smart, design, recipe, binding]
---

# Presentation and extension errors

These errors concern how the page frame is assembled: the menu, layout
regions, your own shell sections, project components in `smart/`, layouts
and sections in `design/`, and composition recipes. How this works is
described in [Layout regions](/en/settings/layout-and-regions/) and
[Interface composition](/en/development/composition/).

## Menu and layout

| Code | Cause and what to do |
| --- | --- |
| `DECLARATIVE_NAVIGATION_DEPTH_EXCEEDED` | The menu came out deeper than four levels. **What to do:** Move pages deeper than the fourth level up, or merge sections. |
| `DECLARATIVE_CONTAINER_MAX_INVALID` | `layout.container.max` is not a whole number from 1 to 8. **What to do:** Give a number from 1 to 8; `7` suits most sites. |
| `DECLARATIVE_CONTENT_GAP_INVALID` | `layout.content.gap` is not a whole number from 0 to 8. **What to do:** Give `0` for an ordinary article, or a number up to 8 for a landing page. |
| `DECLARATIVE_REQUIRED_REGION_DISABLED` | The main `main` region is turned off. **What to do:** Remove `layout.regions.main.enabled: false`. |
| `DECLARATIVE_LAYOUT_CONFIGURATION_INVALID` | `layout.key` names a layout that is not registered. **What to do:** Give `docara.docs` or a project layout from `design/layouts/`. |
| `DECLARATIVE_SHELL_BRANDING_INVALID` | A dark theme logo is set without the main logo, or the `logo` mode is chosen without a logo file. **What to do:** Give the main logo in the `brand` section. |

## Your own region composition

The regions `layout.regions.<region>.sections` are assembled from sections
and blocks.

| Code | Cause and what to do |
| --- | --- |
| `DECLARATIVE_REGION_UNKNOWN` | `layout.regions` names a region that the layout does not have. **What to do:** Use `header`, `sidebar`, `main`, `outline` or `footer`. |
| `DECLARATIVE_REGION_CONFIGURATION_INVALID`, `DECLARATIVE_REGION_SECTION_INVALID`, `DECLARATIVE_REGION_BLOCK_INVALID`, `DECLARATIVE_REGION_ELEMENT_INVALID` | The description of a region, section or block is written incorrectly: not an object, no `id` or `section`, an unknown field. **What to do:** Compare the record with the example in [Your own region composition](/en/settings/layout-and-regions/#your-own-region-composition). |
| `DECLARATIVE_REGION_SECTION_LIMIT_EXCEEDED`, `DECLARATIVE_REGION_BLOCK_LIMIT_EXCEEDED` | A region has more than 8 sections, or a section more than 12 blocks. **What to do:** Merge or remove the extra ones. |
| `DECLARATIVE_SECTION_INSTANCE_ID_DUPLICATED`, `DECLARATIVE_BLOCK_INSTANCE_ID_DUPLICATED`, `DECLARATIVE_DOCUMENT_NODE_ID_DUPLICATED` | Two elements got the same `id`. **What to do:** Give each section and block its own `id`. |
| `DECLARATIVE_DOCUMENT_REGION_MANAGED` | The page itself fills the `main` region; it cannot be described with sections. **What to do:** Remove `sections` from `main`. |
| `DECLARATIVE_SECTION_CAPABILITY_MISMATCH`, `DECLARATIVE_SECTION_REGION_FORBIDDEN` | The section does not suit this region. **What to do:** Put it in the region it is meant for; `docara atlas --json` shows which. |
| `DECLARATIVE_BLOCK_SECTION_FORBIDDEN`, `DECLARATIVE_BLOCK_SLOT_FORBIDDEN`, `DECLARATIVE_REGION_BLOCK_KIND_FORBIDDEN`, `DECLARATIVE_BLOCK_PAYLOAD_MISMATCH` | The block cannot stand in this section or in this place of the section, or its data does not suit its kind. **What to do:** Use `shell.element` or `shell.smart` and compare the fields with the example. |
| `DECLARATIVE_ELEMENT_INVALID`, `DECLARATIVE_ELEMENT_HREF_FORBIDDEN`, `DECLARATIVE_ELEMENT_HREF_INVALID`, `DECLARATIVE_ELEMENT_ARIA_LABEL_INVALID` | A `shell.element` is written incorrectly: `href` is given on something that is not a link, the link does not lead to an address of this site, or the screen reader label is empty. **What to do:** Give `href` only to an `a` element, write the address from the site root and fill in the label. |
| `DECLARATIVE_REGION_SET_PASSPORT_INVALID`, `SET_PASSPORT_PACKAGE_UNKNOWN`, `SET_PASSPORT_INVALID` | A `shell.set-passport` block is written incorrectly, is outside the footer or names a package that is not a component set in `simai-framework.lock.json`; or the set's `set.json` cannot be read or has no title. **What to do:** Put the block in the `footer` region, give the set's name from the lock in `package` and an address from the site root in `history`; if `set.json` is at fault, take the set's package again. |
| `DECLARATIVE_VIEW_UTILITY_NOT_ALLOWED` | `utilities` names a class that is not in the pinned SIMAI Framework version. **What to do:** Use classes from the [Framework utility reference](https://ui.simai.io/). |
| `DECLARATIVE_AUTHOR_EXECUTABLE_SURFACE_FORBIDDEN` | The `layout` settings have a field that looks like a template or code (its name has `template`, `html`, `script`, `style`, `php`, `path` and the like), or a value with `<script`, `<style`, `<?php`, `javascript:`. Settings only choose registered parts. **What to do:** Remove this field; make your own markup a section in `design/`. |

## Shell data bindings

| Code | Cause and what to do |
| --- | --- |
| `DECLARATIVE_REGION_BINDING_FORBIDDEN`, `DECLARATIVE_BINDING_CAPABILITY_MISMATCH` | `bind` names an unknown binding or a binding that does not suit this region. **What to do:** Take a binding for the region you need from `docara atlas --json`. |
| `BINDING_SMART_MISMATCH`, `BINDING_PRESENTATION_FORBIDDEN` | The binding cannot fill this component or its view. **What to do:** Choose a component and view listed for the binding. |
| `BINDING_OWNED_PROP_COLLISION` | A parameter that the binding fills is also set by hand. **What to do:** Remove this parameter from `props`. |

## Project components

Project components live in `smart/<name>/`. The easy way to create them is
`docara scaffold smart <name> --dry-run`.

| Code | Cause and what to do |
| --- | --- |
| `SMART_PROJECT_NAMESPACE_RESERVED` | The `ui` or `docara` namespace is taken by built-in components. **What to do:** Choose another name in `smart.namespace`, for example `project`. |
| `SMART_PROVIDER_NAMESPACE_INVALID`, `SMART_PROVIDER_NAMESPACE_REQUIRED`, `SMART_PROVIDER_NAMESPACE_NOT_OWNED`, `SMART_PROVIDER_NAMESPACE_COLLISION` | The component name does not start with the project namespace, or it matches someone else's. **What to do:** Name the folder `smart/<namespace>.<name>/`, for example `smart/project.notice/`. |
| `SMART_PROVIDER_JSON_INVALID`, `SMART_MANIFEST_INVALID`, `DECLARATIVE_SMART_MANIFEST_INVALID` | `manifest.json` or a view file is not valid JSON or lacks fields, for example a parameter example for the atlas. **What to do:** Run `docara validate smart <name>` and fix the named field. |
| `SMART_PROVIDER_TEMPLATE_REQUIRED` | The component's `template/` folder has no `.php` or `.blade.php` template. **What to do:** Add a template, for example `template/default.php`. |
| `SMART_PROVIDER_ASSET_INVALID` | The path to a style or script in the `assets` section of `manifest.json` is not written as a string, or the file does not exist. **What to do:** Put the file into the component folder and give its path as a string. |
| `SMART_PROVIDER_ALIASES_INVALID` | The extra component names in `extensions.docara.aliases` are written incorrectly. **What to do:** Write them as an object `{"other.name": "reason"}` with a non-empty reason. |
| `SMART_PROVIDER_ROOT_UNSAFE`, `SMART_PROVIDER_PATH_UNSAFE`, `SMART_PROVIDER_SYMLINK_FORBIDDEN` | The component folder has a symbolic link, or a path leaves `smart/`. **What to do:** Replace the links with ordinary files. |
| `SMART_REGISTRY_COMPONENT_NOT_FOUND` | A page or command names a component that does not exist, usually a typo in the name after `:::`. **What to do:** Take the name from `docara list smart`. |
| `DECLARATIVE_COMPOSITE_PRESET_UNKNOWN`, `DECLARATIVE_COMPOSITE_PRESET_INVALID` | The composite component has no such preset, or it is not written as a string. **What to do:** Take the preset name from `docara inspect smart <name> --json`. |
| `DECLARATIVE_COMPOSITE_PROPS_INVALID`, `DECLARATIVE_COMPOSITE_MANIFEST_INVALID` | The parameters of the composite component do not suit its description, or the description is damaged. **What to do:** Compare the parameters with `docara inspect smart <name> --json`. |

## Project layouts and sections

Your own layouts, views, sections and blocks live in `design/layouts/`,
`design/views/`, `design/sections/` and `design/blocks/`.

| Code | Cause and what to do |
| --- | --- |
| `DESIGN_PROJECT_NAMESPACE_RESERVED` | The `docara`, `content`, `shell` or `ui` namespace is taken. **What to do:** Use the project namespace. |
| `DESIGN_ARTIFACT_JSON_INVALID` | A file in `design/` is not valid JSON. **What to do:** Fix the syntax. |
| `DESIGN_ARTIFACT_ID_INVALID`, `DESIGN_VIEW_ID_INVALID` | The file name or `id` is written incorrectly. **What to do:** Name views `layout.<namespace>.<name>` or `section.<namespace>.<name>`. |
| `DESIGN_ARTIFACT_NAMESPACE_FORBIDDEN`, `DESIGN_NAMESPACE_COLLISION` | The description uses someone else's namespace. **What to do:** Use the project namespace. |
| `DESIGN_ARTIFACT_DUPLICATE` | Two descriptions with the same `id`. **What to do:** Rename one. |
| `DESIGN_ARTIFACT_PATH_INVALID`, `DESIGN_ARTIFACT_OUTSIDE_ROOT`, `DESIGN_PROVIDER_ROOT_INVALID`, `DESIGN_PROVIDER_ROOT_MISSING`, `DESIGN_PROVIDER_DIRECTORY_INVALID` | The `design/` folder or a subfolder is a link or does not exist, or a file is not in its own subfolder. **What to do:** Keep the files in ordinary folders `design/layouts`, `views`, `sections`, `blocks`. |
| `DESIGN_DEFAULT_LAYOUT_INVALID` | No layout, or several layouts, are declared the default layout. **What to do:** Keep exactly one. |
| `DESIGN_LAYOUT_VIEW_INVALID`, `DESIGN_LAYOUT_REGION_CONTRACT_INVALID`, `DECLARATIVE_LAYOUT_VIEW_REGIONS_MISMATCH` | The layout refers to a view that does not exist, or the view does not place all layout regions or places one twice. **What to do:** Place each layout region in the view exactly once. |
| `DESIGN_SECTION_VIEW_INVALID`, `DESIGN_SECTION_SLOT_CONTRACT_INVALID`, `DESIGN_SECTION_REGION_CONTRACT_INVALID`, `DESIGN_SECTION_BLOCK_CONTRACT_INVALID`, `DECLARATIVE_SECTION_VIEW_SLOTS_MISMATCH` | The section refers to a view or block that does not exist, its slots do not match the view, or it does not suit the given region. **What to do:** Check the section with `docara validate section <id>`. |
| `DESIGN_VIEW_TREE_INVALID`, `DECLARATIVE_VIEW_TAG_NOT_ALLOWED`, `DECLARATIVE_VIEW_ATTRIBUTE_NOT_ALLOWED`, `DECLARATIVE_VIEW_ATTRIBUTE_INVALID`, `DECLARATIVE_VIEW_UTILITY_INVALID`, `DECLARATIVE_VIEW_NODE_INVALID`, `DECLARATIVE_VIEW_TARGET_DUPLICATED` | The view tree uses a forbidden tag or attribute, is written incorrectly or places one slot twice. **What to do:** Use the allowed tags and attributes; `docara schema view --json` shows the list. |
| `DECLARATIVE_DEFINITION_NOT_ALLOWED`, `DESIGN_ARTIFACT_NOT_FOUND` | A description is named that is not registered. **What to do:** Take the `id` from `docara list layout`, `view`, `section` or `block`. |

## Composition recipes

A composition recipe (`composition_recipe` in the page metadata) assembles a
page from ready parts.

| Code | Cause and what to do |
| --- | --- |
| `COMPOSITION_RECIPE_PAGE_CONTENT_CONFLICT` | A page with a recipe has its own text. **What to do:** Move the visible text into the recipe sources, and keep only the settings block between `---` in the page file. |
| `COMPOSITION_RECIPE_PLAN_PARITY_FAILED`, `COMPOSITION_RECIPE_DOCUMENT_INVALID` | The recipe changed the page plan that it was only meant to repeat, or the page description is incomplete. **What to do:** Rebuild the whole site; if the error repeats, report it. |
| `COMPOSITION_RECIPE_LOCK_FAILED`, `COMPOSITION_RECIPE_ACTIVATION_CONFLICT` | Another build is assembling the recipe at the same time. **What to do:** Wait for it to finish and try again. |
| `COMPOSITION_RECIPE_SNAPSHOT_MISSING`, `COMPOSITION_RECIPE_SNAPSHOT_UNKNOWN`, `COMPOSITION_RECIPE_SNAPSHOT_INTEGRITY_FAILED`, `COMPOSITION_RECIPE_SNAPSHOT_COLLISION`, `COMPOSITION_RECIPE_HEAD_INVALID`, `COMPOSITION_RECIPE_HEAD_COLLISION`, `COMPOSITION_RECIPE_DIGEST_INVALID`, `COMPOSITION_RECIPE_PAGE_KEY_INVALID`, `COMPOSITION_RECIPE_RESULT_INVALID`, `COMPOSITION_RECIPE_ACTIVATION_FAILED`, `COMPOSITION_RECIPE_ACTIVATION_REVISION_INVALID` | The saved recipe result is damaged, was changed by hand or could not be written. **What to do:** Delete the `.docara/composition-recipe/` folder in the project root and run a full build. |

## See also

- [Layout regions](/en/settings/layout-and-regions/)
- [Interface composition](/en/development/composition/)
- [Smart components](/en/development/smart-components/)
- [Error codes](/en/reference/errors/)
