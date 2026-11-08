---
description: Error codes in Markdown pages — front matter, blocks, components and their parameters, images, links and the outline — with the cause and the fix.
---

# Page and Markdown errors

These errors concern one page. The explanation after the code usually has
the file path and the line number. How the blocks themselves are written is
shown on the [Markdown](/authoring/markdown/) and [components](/components/)
pages.

## Page metadata (front matter)

Front matter is the block between two `---` lines at the start of a page.
Docara accepts only the keys `title`, `description`, `tags`, `draft`,
`noindex`, `translation_key`, `profile` and `composition_recipe` in it, one
per line.

| Code | Cause and what to do |
| --- | --- |
| `FRONT_MATTER_UNTERMINATED` | The block starts with a `---` line but is not closed. **What to do:** Add a closing `---` line. |
| `FRONT_MATTER_SYNTAX_INVALID` | A line does not look like `key: value`, for example a key with capital letters or a multi-line value. **What to do:** Write one `key: value` pair per line. |
| `FRONT_MATTER_KEY_UNKNOWN` | The key is not in the list of supported keys. **What to do:** Remove the key or move the setting into `<page>.page.json`. |
| `FRONT_MATTER_KEY_DUPLICATE` | The key is given twice. **What to do:** Keep one. |
| `FRONT_MATTER_VALUE_EMPTY` | The key has no value, or the quoted string is empty. **What to do:** Give a value or remove the key. |
| `FRONT_MATTER_STRING_INVALID` | Text in double quotes is not a valid JSON string, or unquoted text contains `[`, `]`, `{`, `}`. **What to do:** Put such text in double quotes: `title: "Field [beta]"`. |
| `FRONT_MATTER_DRAFT_INVALID` | `draft` has a value other than `true` or `false`. **What to do:** Write `draft: true` or `draft: false`. |
| `FRONT_MATTER_NOINDEX_INVALID` | `noindex` has a value other than `true` or `false`. **What to do:** Write `noindex: true` or `noindex: false`. |
| `FRONT_MATTER_TAGS_INVALID` | `tags` is not written as a list in square brackets. **What to do:** Write `tags: [ui, status]`. |
| `FRONT_MATTER_TAG_INVALID` | A tag contains capital letters, spaces or other characters. **What to do:** Use lowercase letters of any alphabet, digits, `.`, `_` and `-`, for example `tags: [деплой, i18n]`. |
| `FRONT_MATTER_TAG_DUPLICATE` | A tag repeats. **What to do:** Remove the repeat. |
| `FRONT_MATTER_TRANSLATION_KEY_INVALID` | `translation_key` is not a lowercase identifier. **What to do:** Use lowercase Latin letters, digits, `.`, `_` and `-`, for example `start.install`. |
| `FRONT_MATTER_PROFILE_INVALID` | An unknown page profile. **What to do:** Give one of the profiles: `landing`, `article`, `tutorial`, `how_to`, `reference`, `explanation`. |
| `FRONT_MATTER_TEMPLATE_INVALID` | `template` is not a template id. **What to do:** Give a template id from `docara.authoring.json`: lowercase Latin letters, digits, `_` and `-`. |
| `FRONT_MATTER_TEMPLATE_OMIT_INVALID` | `template_omit` is not written as a list in square brackets, or it holds an invalid id. **What to do:** Write `template_omit: [classes]`, listing the ids of template sections. |
| `FRONT_MATTER_COMPOSITION_RECIPE_INVALID` | The path in `composition_recipe` is not a safe relative path to a `.json` file. **What to do:** Give a path from the project root without `..` and `//`. |

## General block rules

Markdown blocks start with a `:::name` line and end with a `:::` line.

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_BLOCK_UNCLOSED` | The block has no closing `:::` line. **What to do:** Add `:::` after the block content. The message gives the line where the block starts. |
| `MARKDOWN_BLOCK_EMPTY` | The block is opened and closed at once. **What to do:** Add content or remove the block. |
| `MARKDOWN_BLOCK_NESTING_UNSUPPORTED` | Another Docara block or Smart block stands inside the block. **What to do:** Move the inner block out. Plain Markdown is fine inside. The exceptions are `grid` with `card`, and `columns`. |
| `MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED` | The block starts with an indent, for example inside a list or a quote. **What to do:** Start the `:::name` line without an indent, at the top level of the page. |
| `MARKDOWN_BLOCK_LIMIT_EXCEEDED`, `FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED` | The page has more than 64 opening lines of blocks and components. **What to do:** Split the page into several. |
| `MARKDOWN_BLOCK_INPUT_INVALID`, `MARKDOWN_INLINE_INPUT_INVALID` | The file is not UTF-8. **What to do:** Save the page as UTF-8. |
| `MARKDOWN_COMPONENT_ATTRIBUTES_INVALID` | The attributes in `{...}` after the block name have an error. **What to do:** Write `{name=value}`; put a value with spaces in double quotes. |
| `MARKDOWN_COMPONENT_ATTRIBUTE_DUPLICATE` | An attribute is given twice, including under an old and a new name, for example `type` and `variant` on a badge. **What to do:** Keep one, with the new name. |
| `MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN` | The block does not support this attribute. **What to do:** Check the list of attributes on the block page in the [component catalogue](/components/). |
| `MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID` | The attribute value is not in the list of allowed values. **What to do:** Take a value from the block description. |
| `MARKDOWN_PARAMETER_DEPRECATED` | A warning, not an error: the page uses an old parameter name or an old value, for example `view` instead of `layout` or `ratio=16x9` instead of `ratio=16/9`. The page looks the same; the build does not stop and writes one summary line. **What to do:** Run `docara validate page <address>`: it shows the line and the new name. Replace the name or value. The full list is in [Former parameter names](/en/start/component-model/#former-parameter-names). |
| `MARKDOWN_RAW_HTML_FORBIDDEN` | The Markdown has HTML tags. Docara does not let HTML into a page. **What to do:** Rewrite the fragment in Markdown, or put the code in an [html](/components/html/) or [example](/components/example/) block, which show it in an isolated frame. |
| `MARKDOWN_BLOCK_PLACEHOLDER_CARDINALITY_INVALID`, `MARKDOWN_INLINE_PLACEHOLDER_CARDINALITY_INVALID`, `FRAMEWORK_PLACEHOLDER_CARDINALITY_INVALID` | The page text happened to match the service mark that Docara uses for the place of a block. **What to do:** Change the text near the block. If the error repeats, report it in the Docara repository. |

## Links and images

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_LINK_URL_UNSAFE`, `MARKDOWN_IMAGE_URL_UNSAFE` | A link or image uses an unsafe scheme, for example `javascript:` or `data:`. **What to do:** Use `https://`, `mailto:` or a path inside the site. |
| `MARKDOWN_LOCAL_IMAGE_UNSAFE` | An image with a `/assets/...` path was not found, lies outside the site `assets/` folder, goes through a link or has an unsupported format. **What to do:** Put the file into `assets/` beside `docara.json`. AVIF, GIF, JPEG, PNG, SVG and WebP work. |
| `MARKDOWN_LOCAL_IMAGE_CASE_MISMATCH` | The letter case in the path does not match the file name, for example `Logo.png` and `logo.png`. **What to do:** Fix the path. On a Linux server such an image will not open. |
| `MARKDOWN_LOCAL_IMAGE_CONTEXT_INVALID`, `MARKDOWN_LOCAL_IMAGE_RECEIPT_INVALID` | An image from `/assets/` is used by a file outside the content folder of a language. **What to do:** Use such images only on pages in `content/<language>/`. |

## Page blocks

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_ALERT_HEADING_REQUIRED` | An [alert](/components/alert/) does not start with a level 2–5 heading. **What to do:** Make a heading the first line of the block, for example `## Warning`. |
| `MARKDOWN_ALERT_CONTENT_REQUIRED` | The alert has no text after the heading. **What to do:** Add explanatory text. |
| `MARKDOWN_BANNER_CONTENT_REQUIRED` | The [banner](/components/banner/) is empty. **What to do:** Add text. |
| `MARKDOWN_DETAILS_HEADING_REQUIRED` | A [details](/components/details/) block does not start with a level 2–4 heading. **What to do:** Make a heading the first line; it becomes the label of the expandable block. |
| `MARKDOWN_DETAILS_CONTENT_REQUIRED` | The details block has no text after the heading. **What to do:** Add content. |
| `MARKDOWN_STEPS_ORDERED_LIST_REQUIRED` | [Steps](/components/steps/) does not hold exactly one numbered list. **What to do:** Keep one `1.`, `2.`, `3.` list in the block. |
| `MARKDOWN_TABS_ITEMS_REQUIRED` | [Tabs](/components/tabs/) has fewer than two tabs. A tab is a level 3 heading with text under it. **What to do:** Add at least two `###` headings with content. |
| `MARKDOWN_TABS_ITEM_INVALID` | A tab has no label or no content. **What to do:** Give each `###` heading text under it. |
| `MARKDOWN_TABS_DEFAULT_NOT_FOUND` | In [tabs](/components/tabs/) the `default` parameter names a tab that the block does not have. The message lists the tab names of the block. **What to do:** Give the label of one of the tabs or its name: the label in lowercase with punctuation replaced by a hyphen. |
| `MARKDOWN_TREE_LIST_REQUIRED` | [Tree](/components/tree/) does not hold one nested bulleted list. **What to do:** Keep one `-` list with nested items in the block. |
| `MARKDOWN_FIGURE_IMAGE_REQUIRED` | [Figure](/components/figure/) holds more than one image or extra text. **What to do:** Keep one image and, if you like, one caption paragraph. |
| `MARKDOWN_MEDIA_CONTENT_REQUIRED` | [Media](/components/media/) has no image or no text. **What to do:** Add one image and text beside it. |
| `MARKDOWN_GRID_CARD_REQUIRED` | [Grid](/components/grid/) holds something other than `card` blocks. **What to do:** Put the content into [card](/components/card/) blocks. |
| `MARKDOWN_DOWNLOAD_LINK_REQUIRED` | [Download](/components/download/) holds more than one link. **What to do:** Keep one link to the file. |
| `MARKDOWN_QUOTE_ATTRIBUTION_REQUIRED` | A quote with attribution has no author or source. **What to do:** Give the author or the source. |
| `MARKDOWN_MATH_SOURCE_REQUIRED` | [Math](/components/math/) is empty. **What to do:** Add a formula in TeX notation. |
| `MARKDOWN_DIAGRAM_SOURCE_REQUIRED` | [Diagram](/components/diagram/) is empty. **What to do:** Add the diagram description. |
| `MARKDOWN_DIAGRAM_LABEL_EMPTY` | The `label` parameter of a [diagram](/components/diagram/) is empty, and it is the text alternative for screen readers. **What to do:** Write in `label` what the diagram shows, or remove the parameter: then the name from `title` is read. |
| `MARKDOWN_HTML_FENCE_REQUIRED` | [Html](/components/html/) holds more than one HTML code block. **What to do:** Keep one code block with the language `html`. |
| `MARKDOWN_BACKLINKS_LIMIT_INVALID` | `limit` of [backlinks](/components/backlinks/) is outside the range 1–50. **What to do:** Give a number from 1 to 50. |
| `MARKDOWN_SCHEMA_REFERENCE_FIELDS_INVALID` | `fields` of `schema_reference` names a field that is not in the schema, or it is written with spaces. **What to do:** List top-level fields separated by commas without spaces: `fields=reading,search`. |
| `MARKDOWN_INLINE_COMPONENT_LABEL_REQUIRED` | A component inside text, for example `:badge[...]` or `:kbd[...]`, is empty. **What to do:** Write text in the square brackets: `:kbd[Esc]`. |
| `MARKDOWN_ATLAS_FILTER_INVALID` | The atlas filter contains forbidden characters. **What to do:** List identifiers separated by commas: lowercase Latin letters, digits, `.`, `_`, `-`. |

## Code from a file

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_CODE_SOURCE_REQUIRED` | A [code from a file](/components/code-from-file/) block has no `src`. **What to do:** Give the file path in `src`. |
| `MARKDOWN_CODE_SOURCE_UNSAFE` | The path in `src` is absolute, leads outside the project or to a symbolic link, or the file does not exist. **What to do:** Give the path to an existing file relative to the page. |
| `MARKDOWN_CODE_SOURCE_INVALID` | The file is larger than 1 MiB or not UTF-8. **What to do:** Take a smaller file, or the fragment you need through `lines`. |
| `MARKDOWN_CODE_LINES_INVALID` | `lines` is not written as `N` or `N-M`. **What to do:** Write `lines=5` or `lines=5-12`. |
| `MARKDOWN_CODE_LANGUAGE_INVALID` | The highlighting language is written with forbidden characters. **What to do:** Give the language in lowercase, for example `php` or `json`. |
| `MARKDOWN_CODE_RANGE_INVALID` | In `highlight` or `focus` of a [code block](/components/code/) a line number is greater than the number of lines in the block, a range is reversed or not numeric, or `start_after` and `end_before` left no lines. **What to do:** Write the numbers separated by commas, counting from the first line of the block: `highlight="1,4-6"`. |
| `MARKDOWN_CODE_REGION_UNKNOWN` | The file has no region from `region`, the region is not closed with `#endregion`, or no line contains the `start_after` or `end_before` text. **What to do:** Mark the region in the file with the comments `// #region name` and `// #endregion name`, or fix the name and the text of the marks. |
| `MARKDOWN_CODE_OPTIONS_CONFLICT` | A file fragment is selected in two ways at once: `lines` together with `region`, or with `start_after` and `end_before`. **What to do:** Keep one way. |

## Component reference

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_COMPONENT_REFERENCE_COMPONENT_INVALID` | `component` of a [component reference](/components/component-reference/) is empty or does not look like a tag: it needs lowercase Latin letters, digits and at least one hyphen. **What to do:** Give the tag, for example `component=sf-view-table`. |
| `MARKDOWN_COMPONENT_REFERENCE_SECTIONS_INVALID` | `sections` names an unknown part, repeats a part or is written with spaces. **What to do:** List parts from `overview`, `attributes`, `events`, `methods`, `css`, `accessibility`, separated by commas without spaces. |
| `COMPONENT_REFERENCE_MANIFEST_MISSING` | There is no `components/<folder>/smart.manifest.json` for the tag in `component`. The folder is the tag without the `sf-` prefix. **What to do:** Copy the component's manifest into that folder or fix the tag. |
| `COMPONENT_REFERENCE_MANIFEST_INVALID` | `smart.manifest.json` or `component.json` is not a JSON object, is larger than 4 MB, is a symbolic link, describes another tag (`custom_element`), or its `inputs`, `events`, `methods`, `accessibility` or `css-variables` field is not an object. **What to do:** Take the manifest from the component's release unchanged and check that `custom_element` matches `component`. |
| `COMPONENT_REFERENCE_DICTIONARY_INVALID` | The `component-reference.json` dictionary in a locale folder is not JSON, has no `"schema": "docara.component_reference.v1"` and `strings`, has other keys, or a translation is empty. **What to do:** Keep `schema`, `strings` and, if needed, `$comment`; every string needs a non-empty translation. |
| `COMPONENT_REFERENCE_TRANSLATION_MISSING` | The locale's dictionary has no translation for a manifest string a block shows, usually after the manifest was updated. The message names the dictionary and the first such strings. **What to do:** Add those strings to `strings` exactly as in the manifest, with their translation; for a CSS variable the key is `css:<name>`. |
| `COMPONENT_REFERENCE_TRANSLATION_STALE` | The locale's dictionary holds a string no `:::component_reference` block on the site shows: the manifest text changed or the component left the pages. Checked on full builds only. **What to do:** Remove the string or replace the key with the new manifest text. |

## Embedding and preview

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_EMBED_LINK_REQUIRED` | [Embed](/components/embed/) holds more than one link. **What to do:** Keep one link to the embedded material. |
| `MARKDOWN_EMBED_LABEL_REQUIRED` | The link in embed has no text. **What to do:** Write the link text: readers see it before loading. |
| `MARKDOWN_EMBED_URL_UNSAFE` | The link in embed uses an unsafe scheme. **What to do:** Use an `https://` address. |
| `MARKDOWN_EMBED_ORIGIN_FORBIDDEN` | The address does not suit the chosen provider or consent mode. **What to do:** Use an address of the service named in `provider`. The list of addresses is on the [embed](/components/embed/) page. |
| `MARKDOWN_EMBED_ID_INVALID` | The `id` of embed contains forbidden characters. **What to do:** Use lowercase Latin letters, digits and a hyphen. |
| `MARKDOWN_INTERNAL_PREVIEW_LINK_REQUIRED` | A preview of a site page holds more than one link. **What to do:** Keep one link. |
| `MARKDOWN_INTERNAL_PREVIEW_TITLE_REQUIRED` | The preview has no frame title. **What to do:** Add a title: a screen reader reads it. |
| `MARKDOWN_INTERNAL_PREVIEW_URL_UNSAFE` | The link leads to another site or is not written from the root. **What to do:** Give a path of this same site that starts with `/`. |

## Code examples

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_EXAMPLE_FENCED_SOURCE_REQUIRED` | [Example](/components/example/) has no code block. **What to do:** Add a Markdown code block, or HTML, CSS and JavaScript. |
| `MARKDOWN_EXAMPLE_SOURCE_ONLY` | The example has text outside the code blocks. **What to do:** Move the explanation outside the block. |
| `MARKDOWN_EXAMPLE_HTML_REQUIRED` | There is CSS or JavaScript but no HTML. **What to do:** Add an `html` code block. |
| `MARKDOWN_EXAMPLE_SOURCE_COMBINATION_INVALID` | A Markdown example is mixed with HTML, CSS or JavaScript. **What to do:** Make two separate examples. |
| `MARKDOWN_EXAMPLE_SOURCE_DUPLICATE` | Two code blocks in the same language. **What to do:** Merge them into one. |
| `MARKDOWN_EXAMPLE_LABEL_INVALID` | The example label is empty. **What to do:** Write a label or remove the attribute. |
| `MARKDOWN_EXAMPLE_PREVIEW_INVALID` | `preview` is not `auto`, `inline` or `sandbox`. **What to do:** Give one of the three values. |
| `MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED` | `preview=inline` is given, but the example runs a script or styles or changes its own theme (`theme_switch=true`), which needs an isolated frame. **What to do:** Remove `preview=inline` or give `preview=sandbox`. |
| `MARKDOWN_EXAMPLE_ID_INVALID` | The `id` of an example from the `examples/` folder is written incorrectly. **What to do:** Give a lowercase path with `/` inside `examples/`. |
| `MARKDOWN_EXAMPLE_ID_BODY_CONFLICT` | The example has both an `id` and code inside the block. **What to do:** Keep one of them. |
| `MARKDOWN_EXAMPLE_SRC_UNSAFE` | The page address in the `src` of the example leads to another site or is written incorrectly. **What to do:** Give a path from the root of this site, for example `/en/demo/`. |
| `MARKDOWN_EXAMPLE_SRC_CONFLICT` | A whole-page example has `src` together with `id`, `preview`, the log (`log`, `log_console`, `log_open`) or `theme_switch`. **What to do:** Keep only `src`. |
| `MARKDOWN_EXAMPLE_LOG_REQUIRED` | The example has `log_open` or `log_console=false`, but its log is not turned on. **What to do:** Name the events in `log` or set `log_console=true`; otherwise remove the parameter. |
| `MARKDOWN_EXAMPLE_PAGE_SOURCE_INVALID` | A whole-page example has no tabs, a tab is empty or repeats, or there is text before the first `###`. **What to do:** Start the block with `### File name` and put a `:::code` block under each heading. |
| `MARKDOWN_EXAMPLE_PROJECT_CONTEXT_REQUIRED` | An example by `id` is used outside a project build. **What to do:** Build the page with `docara build` in the site folder. |
| `MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE` | Two examples on a page got the same identifier. **What to do:** Give the examples different `id`s or labels. |
| `MARKDOWN_EXAMPLE_INLINE_ID_CHECK_FAILED` | The page could not be parsed to check the examples. **What to do:** Fix the markup error near the example that the build reports. |

## Landing blocks

### Hero

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_HERO_H1_REQUIRED` | [Hero](/components/hero/) does not start with a level 1 heading. **What to do:** Make `# Title` the first line. |
| `MARKDOWN_HERO_DESCRIPTION_REQUIRED` | There are not one or two description paragraphs. **What to do:** Add one or two paragraphs after the heading. |
| `MARKDOWN_HERO_STRUCTURE_INVALID` | The hero holds something other than the heading, paragraphs, button links and an image. **What to do:** Remove lists, tables and other blocks. |
| `MARKDOWN_HERO_LINK_REQUIRED` | A button has no text. **What to do:** Write the link text. |
| `MARKDOWN_HERO_LINK_UNSAFE`, `MARKDOWN_HERO_IMAGE_UNSAFE` | A link or image uses an unsafe scheme. **What to do:** Use `https://` or a site path. |
| `MARKDOWN_HERO_MEDIA_IMAGE_COUNT_INVALID` | More than one image. **What to do:** Keep one. |
| `MARKDOWN_HERO_MEDIA_IMAGE_REQUIRED` | `media=side` or `media=background` without an image. **What to do:** Add one image. |
| `MARKDOWN_HERO_MEDIA_IMAGE_FORBIDDEN` | `media=none`, but the hero has an image. **What to do:** Remove the image or give `media=side`. |
| `MARKDOWN_HERO_MEDIA_VARIANT_INCOMPATIBLE` | `media=side` without `layout=split`. **What to do:** Add `layout=split`. |
| `MARKDOWN_HERO_SIDE_ALT_REQUIRED` | The side image has an empty alt. **What to do:** Describe the image in `![description](...)`. |
| `MARKDOWN_HERO_BACKGROUND_ALT_FORBIDDEN` | The background image has an alt. A background carries no meaning, and a screen reader must not read it. **What to do:** Leave the alt empty: `![](...)`. |
| `MARKDOWN_HERO_BACKGROUND_MODE_REQUIRED` | Background settings are given without `media=background`. **What to do:** Add `media=background` or remove the background settings. |
| `MARKDOWN_HERO_SIDE_IMAGE_UNSAFE`, `MARKDOWN_HERO_BACKGROUND_UNSAFE` | The image was not found in the site `assets/` or has an unsupported format. **What to do:** Put the file into `assets/` and give the path `/assets/...`. |
| `MARKDOWN_HERO_SIDE_IMAGE_CASE_MISMATCH`, `MARKDOWN_HERO_BACKGROUND_CASE_MISMATCH` | The letter case in the path does not match the file name. **What to do:** Fix the path. |
| `MARKDOWN_HERO_SIDE_IMAGE_CONTEXT_INVALID`, `MARKDOWN_HERO_BACKGROUND_CONTEXT_INVALID` | An image from `/assets/` is used by a file outside `content/<language>/`. **What to do:** Use the hero on pages in a language folder. |
| `MARKDOWN_HERO_EFFECT_INVALID` | `effect` is neither an effect name from the catalogue nor an `https://.../effect.json` address. **What to do:** Give an effect name, for example `aurora-curtain`. |
| `MARKDOWN_HERO_EFFECT_REQUIRED` | `effect_theme` or `effect_settings` is given without `effect`. **What to do:** Add `effect` or remove these attributes. |
| `MARKDOWN_HERO_EFFECT_SETTINGS_INVALID` | `effect_settings` is not a JSON object. **What to do:** Write the settings as `{"speed": 0.5}`. |
| `MARKDOWN_HERO_EFFECT_MEDIA_CONFLICT` | A background effect and a background image at once. **What to do:** Keep one of them. |

Background effect codes that depend on `docara.json` are described in
[Background effects](/reference/errors/site/#background-effects).

### Features, logos, CTA, showcase, promo, columns, surface

| Code | Cause and what to do |
| --- | --- |
| `MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED` | Features does not hold one flat bulleted list. **What to do:** Keep one `-` list without nested items. |
| `MARKDOWN_FEATURES_ITEM_COUNT_INVALID` | There are fewer than two or more than six items. **What to do:** Keep from two to six. |
| `MARKDOWN_FEATURES_ITEM_CONTENT_INVALID` | An item has more than one paragraph or an unsupported element. **What to do:** Keep one paragraph of text in the item. |
| `MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED` | An item without visible text. **What to do:** Add text. |
| `MARKDOWN_FEATURES_IMAGE_UNSAFE` | An image in an item uses an unsafe scheme. **What to do:** Use `https://` or a site path. |
| `MARKDOWN_LOGOS_UNORDERED_LIST_REQUIRED` | [Logos](/components/logos/) does not hold one flat list. **What to do:** Keep one `-` list. |
| `MARKDOWN_LOGOS_ITEM_COUNT_INVALID` | There are fewer than two or more than twelve logos. **What to do:** Keep from two to twelve. |
| `MARKDOWN_LOGOS_ITEM_CONTENT_INVALID` | An item has more than one paragraph. **What to do:** Keep one image, or one link with an image, in the item. |
| `MARKDOWN_CTA_LINK_REQUIRED` | The cta does not hold exactly one link with text. **What to do:** Keep one button link. |
| `MARKDOWN_CTA_LINK_UNSAFE` | The link uses an unsafe scheme. **What to do:** Use `https://` or a site path. |
| `MARKDOWN_SHOWCASE_HEADING_REQUIRED`, `MARKDOWN_PROMO_HEADING_REQUIRED` | The block does not start with a heading of the required level. **What to do:** Make a heading the first line. |
| `MARKDOWN_SHOWCASE_STRUCTURE_INVALID`, `MARKDOWN_PROMO_STRUCTURE_INVALID` | The block holds something other than paragraphs, links and one image at the end. **What to do:** Remove the extra parts; put the image last. |
| `MARKDOWN_SHOWCASE_DESCRIPTION_REQUIRED`, `MARKDOWN_PROMO_DESCRIPTION_REQUIRED` | There is no description paragraph. **What to do:** Add a paragraph after the heading. |
| `MARKDOWN_SHOWCASE_IMAGE_REQUIRED`, `MARKDOWN_PROMO_IMAGE_REQUIRED` | There is no image. **What to do:** Add an image as the last line of the block. |
| `MARKDOWN_SHOWCASE_IMAGE_ALT_REQUIRED` | The image has an empty alt. **What to do:** Describe the image. |
| `MARKDOWN_SHOWCASE_IMAGE_UNSAFE`, `MARKDOWN_PROMO_IMAGE_UNSAFE`, `MARKDOWN_SHOWCASE_LINK_UNSAFE`, `MARKDOWN_PROMO_LINK_UNSAFE` | An image or link uses an unsafe scheme. **What to do:** Use `https://` or a site path. |
| `MARKDOWN_SHOWCASE_LINK_REQUIRED`, `MARKDOWN_PROMO_LINK_REQUIRED` | A link has no text. **What to do:** Write the link text. |
| `MARKDOWN_COLUMNS_REGION_COUNT_INVALID` | Columns has fewer than two or more than four columns. **What to do:** Split the content into 2–4 parts. |
| `MARKDOWN_COLUMNS_SEPARATOR_INVALID` | The columns are not separated by a `---` line with blank lines before and after it. **What to do:** Put a separate `---` line between the columns, surrounded by blank lines. |
| `MARKDOWN_COLUMNS_REGION_EMPTY` | One of the columns is empty. **What to do:** Add text to it or reduce the number of columns. |
| `MARKDOWN_SURFACE_CONTENT_REQUIRED` | [Surface](/components/surface/) is empty. **What to do:** Add content. |
| `MARKDOWN_SURFACE_BACKGROUND_REQUIRED` | A background position is given without `background_image`. **What to do:** Add `background_image` or remove the position settings. |
| `MARKDOWN_SURFACE_OVERLAY_REQUIRED` | `overlay_strength` is given with `overlay=none`. **What to do:** Give `overlay=light` or `overlay=dark`. |
| `MARKDOWN_SURFACE_BACKGROUND_UNSAFE`, `MARKDOWN_SURFACE_BACKGROUND_CASE_MISMATCH`, `MARKDOWN_SURFACE_BACKGROUND_CONTEXT_INVALID` | The background image was not found in `assets/`, the path differs in letter case, or the page lies outside `content/<language>/`. **What to do:** Put the file into `assets/` and give the exact path `/assets/...`. |

## Inline components: icon, badge, button, key

Inline components are written inside a paragraph: `:icon[name]{...}`,
`:badge[text]{...}`, `:button[text]{...}`, `:kbd[key]`. The parameters of
each are described on its page: [icon](/en/components/icon/),
[badge](/en/components/badge/), [button link](/en/components/button/),
[key](/en/components/kbd/).

| Code | Cause and what to do |
| --- | --- |
| `CONTENT_COMPONENT_ICON_UNKNOWN` | There is no icon with this name in the Material Symbols set that ships with Docara. Most often this is a typo: `:icon[setings]` instead of `:icon[settings]`. **What to do:** Find the name in the [list of all icons](/en/components/icon/#all-icon-names) and fix it. |
| `CONTENT_COMPONENT_SLOT_INVALID` | The icon name contains capital letters, spaces or other characters, for example `:icon[Search]`. **What to do:** Write the name in lowercase Latin letters, digits and `_`, for example `check_circle`. |
| `CONTENT_COMPONENT_PROP_UNKNOWN` | The component has no such parameter, for example `:kbd[Esc]{size=1}`. **What to do:** Remove the parameter or fix its name from the component page. |
| `CONTENT_COMPONENT_PROP_INVALID` | The parameter value is not in the list of allowed values, for example `:icon[search]{weight=bold}`, or a button has no safe `href`. **What to do:** Take a value from the component page. |
| `CONTENT_COMPONENT_PROP_COMBINATION_INVALID` | The parameters are valid one by one but do not work together, for example `:icon[search]{variant=main}` without a container. **What to do:** Add `container=square` or `container=circle`, or remove `variant`. |
| `CONTENT_COMPONENT_ICON_CATALOG_INVALID` | The icon set files in the installed Docara are damaged or do not match each other. **What to do:** Reinstall Docara through Composer. If the error repeats, report it in the Docara repository. |

## Components and their parameters

SIMAI Framework components are called with a block whose parameters are in
JSON. The parameter list of each component is on its page in the
[component catalogue](/components/).

| Code | Cause and what to do |
| --- | --- |
| `FRAMEWORK_COMPONENT_UNSUPPORTED` | The component is not in the catalogue of this Docara version. **What to do:** Take a component from the catalogue. Do not replace it with a similar HTML element. |
| `FRAMEWORK_COMPONENT_ID_INVALID` | The component name is written with an error. **What to do:** Copy the name from the component page. |
| `FRAMEWORK_DIRECTIVE_UNCLOSED` | The component block is not closed. **What to do:** Add a closing line. |
| `FRAMEWORK_DIRECTIVE_JSON_INVALID` | The component parameters are not valid JSON. **What to do:** Check the quotes and commas. |
| `FRAMEWORK_DIRECTIVE_PROPS_INVALID` | The parameters are not written as an object `{...}`. **What to do:** Wrap the parameters in curly braces. |
| `FRAMEWORK_DIRECTIVE_NESTING_UNSUPPORTED` | The component is nested in another component or block. **What to do:** Move it to the top level of the page. |
| `FRAMEWORK_DIRECTIVE_INDENTATION_UNSUPPORTED` | The component block starts with an indent. **What to do:** Remove the indent. |
| `FRAMEWORK_DIRECTIVE_MARKDOWN_INVALID` | The page is not UTF-8. **What to do:** Save the file as UTF-8. |
| `FRAMEWORK_PROP_UNKNOWN` | The component has no such parameter. The message gives `component:parameter`. **What to do:** Remove the parameter or fix its name. |
| `FRAMEWORK_PROP_REQUIRED` | A required parameter is not set. **What to do:** Add it. |
| `FRAMEWORK_PROP_MANAGED` | Docara sets the parameter itself, for example an identifier. **What to do:** Remove it from the call. |
| `FRAMEWORK_PROP_TYPE_INVALID` | The value has the wrong type, for example a string instead of a number. **What to do:** Fix the type. The message gives the expected type. |
| `FRAMEWORK_PROP_ENUM_INVALID` | The value is not in the list of allowed values. **What to do:** Take a value from the component page. |
| `FRAMEWORK_PROP_MINIMUM_INVALID`, `FRAMEWORK_PROP_MAXIMUM_INVALID` | The number is below the minimum or above the maximum. **What to do:** Give a number in the allowed range. |
| `FRAMEWORK_PROP_MIN_LENGTH_INVALID`, `FRAMEWORK_PROP_MAX_LENGTH_INVALID` | The string is shorter or longer than allowed. **What to do:** Shorten or extend the text. |
| `FRAMEWORK_PROP_PATTERN_INVALID` | The string does not match the parameter format. **What to do:** Check the format on the component page. |
| `FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME` | The parameter value is forbidden in the site's `simai-framework.lock.json`: a static site does not support it, for example `"closable": true` or `"type": "success"` on `ui.alert`. The message gives `component:parameter`. **What to do:** Choose another value; the list of restrictions is on [Smart components ui.*](/en/components/smart-ui/). |
| `SMART_PROPS_INVALID` | The parameters of a Smart component failed the check. The message has the path to the field. **What to do:** Compare the parameters with the description of the Smart component. |
| `TYPED_COMPONENT_UNSUPPORTED` | There is no such Docara block. **What to do:** Check the block name in the [component catalogue](/components/). |

## Page structure

Before output, Docara parses the page into blocks and checks that every
opened block is closed and stands where it is allowed.

| Code | Cause and what to do |
| --- | --- |
| `DOCUMENT_TYPED_DIRECTIVE_UNCLOSED`, `DOCUMENT_COMPONENT_BLOCK_UNCLOSED`, `DOCUMENT_SMART_UNCLOSED` | The block has no closing line. **What to do:** Add a `:::` line with as many colons as the opening line. |
| `DOCUMENT_TYPED_DIRECTIVE_NESTED_FORBIDDEN`, `DOCUMENT_COMPONENT_BLOCK_NESTED_FORBIDDEN`, `DOCUMENT_SMART_NESTED_FORBIDDEN` | Another block stands inside a block that does not accept nested blocks. **What to do:** Move the inner block out. Only [surface](/en/components/surface/), [grid](/en/components/grid/) and [card](/en/components/card/) accept other blocks; which ones is written on the block page. |
| `DOCUMENT_COMPONENT_ALIAS_UNKNOWN` | After `:::` stands a name that is neither a Docara block nor a project component. **What to do:** Check the name in the [component catalogue](/en/components/). |
| `DOCUMENT_COMPONENT_BLOCK_CONTENT_REQUIRED`, `DOCUMENT_COMPONENT_SLOT_REQUIRED` | The block is empty although it needs visible content. **What to do:** Add text inside the block or remove the block. |
| `DOCUMENT_COMPONENT_BLOCK_CHILD_INVALID`, `DOCUMENT_IR_EXAMPLE_COMPONENT_BOUNDARY_INVALID` | A code block starts inside a block and ends outside it, or the other way round. **What to do:** Close the code block before the `:::` line. If the code block itself contains `:::`, surround it with a longer fence: four backticks or `~~~`. |
| `DOCUMENT_IR_CODE_BLOCK_UNCLOSED` | The code block has no closing fence. **What to do:** Add a line with the same backticks as at the start. |
| `DOCUMENT_IR_CODE_BLOCK_OPENING_INVALID` | The first line of the code block is written incorrectly: the fence is shorter than three characters, or `` ` `` and `~` are mixed. **What to do:** Start the block with three backticks or three tildes, then the language. |
| `DOCUMENT_IR_EXAMPLE_UNCLOSED` | An [example](/en/components/example/) has no closing `:::` line. **What to do:** Add it after the last code block of the example. |
| `DOCUMENT_HTML_COMMENT_UNCLOSED` | A `<!--` comment is not closed. **What to do:** Add `-->`. |
| `DOCUMENT_DIRECTIVE_LIMIT_EXCEEDED` | The page has more than 128 blocks. **What to do:** Split the page into several. |
| `DOCUMENT_CONTAINER_CHILD_COUNT_MIN` | A surface or grid is empty. **What to do:** Add content or remove the block. |
| `DOCUMENT_CONTAINER_CHILD_COUNT_MAX` | The block has more nested elements than it accepts, for example more than 64 cards in a surface. **What to do:** Split the content into several blocks. |
| `DOCUMENT_CONTAINER_CHILD_FORBIDDEN`, `DOCUMENT_CONTAINER_SMART_CHILD_FORBIDDEN` | Something the block does not accept is nested in it: for example a surface in a surface, or a project component that has not declared itself embeddable. **What to do:** Move the nested block out. What can be nested is written on the block page. |
| `DOCUMENT_CONTAINER_DEPTH_EXCEEDED` | Blocks are nested too deeply. **What to do:** Reduce the nesting: "surface → grid → card" is the deepest allowed chain. |
| `DOCUMENT_SMART_PROPS_JSON_INVALID` | The parameters of a project component are not valid JSON. **What to do:** Check the quotes and commas. |
| `DOCUMENT_SMART_PROPS_INVALID` | The component parameters are not written as an object `{…}`. **What to do:** Wrap the parameters in curly braces. |
| `DOCUMENT_IR_INPUT_INVALID` | The page could not be read as UTF-8 text. **What to do:** Save the file as UTF-8. |

## Project components

Project components live in the `smart/` folder and are called with a
`:::project.name` block with parameters in JSON. How to create them is
described in [Smart components](/en/development/smart-components/).

| Code | Cause and what to do |
| --- | --- |
| `PROJECT_SMART_ROOT_UNSAFE` | `docara.json` declares the `smart` namespace, but the `smart/` folder does not exist or is a link. **What to do:** Create an ordinary `smart/` folder in the project root. |
| `PROJECT_SMART_TREE_UNSAFE` | The `smart/` folder has a symbolic link or something that is not a file. **What to do:** Replace the links with real files. |
| `PROJECT_SMART_CONFIGURATION_INVALID` | The `smart` section in `docara.json` is written incorrectly. **What to do:** Write `{"smart": {"namespace": "project"}}`. |
| `PORTABLE_SMART_PROPS_INVALID`, `PORTABLE_SMART_PROP_POLICY_FORBIDDEN` | A component parameter failed the check or cannot be set from Markdown. The message has the component and parameter names. **What to do:** Compare the parameters with `docara inspect smart <name> --json`. |
| `PORTABLE_SMART_VIEW_UNKNOWN`, `PORTABLE_SMART_PRESET_UNKNOWN` | The component has no such view (`view`) or preset (`preset`). **What to do:** Take the name from `docara inspect smart <name> --json` or remove the parameter. |
| `PORTABLE_SMART_REQUIRED_SLOT_MISSING` | A required slot is not filled, for example the options of a dropdown. **What to do:** Add child elements to that slot. |
| `PORTABLE_SMART_CHILD_SLOT_FORBIDDEN`, `PORTABLE_SMART_CHILD_COUNT_INVALID`, `PORTABLE_SMART_CHILD_INVALID`, `PORTABLE_SMART_CHILDREN_INVALID`, `PORTABLE_SMART_CHILDREN_COLLISION`, `PORTABLE_SMART_SLOTS_INVALID` | The child elements of the component are written incorrectly: in the wrong slot, more than allowed, of an unsuitable type, or twice, in the call and in the view. **What to do:** Compare the slots and child elements with `docara inspect smart <name> --json`. |
| `PORTABLE_SMART_PARENT_FORBIDDEN` | The component stands inside a component that does not accept it. **What to do:** Move it out or choose an allowed parent. |
| `PORTABLE_SMART_COMPOSITION_DEPTH_EXCEEDED` | Components are nested in each other too deeply. **What to do:** Reduce the nesting. |
| `PORTABLE_SMART_ARTIFACT_INVALID`, `PORTABLE_SMART_ARTIFACT_UNSAFE`, `PORTABLE_SMART_CONTRACT_INVALID`, `PORTABLE_SMART_PROVIDER_MISMATCH`, `PORTABLE_SMART_LEGACY_ADAPTER_INVALID` | The component files in `smart/<name>/` are damaged, do not match the description in `manifest.json` or lie in the wrong place. **What to do:** Run `docara validate smart <name>` and fix the named file. For a new component, start with `docara scaffold smart <name> --dry-run`. |

## Shared examples from the examples folder

A shared example is kept in `examples/<path>/` and called with
`:::example {id="<path>"}`. More on the
[Interactive example](/en/components/example/#shared-example-from-the-project)
page.

| Code | Cause and what to do |
| --- | --- |
| `PROJECT_EXAMPLES_ROOT_MISSING` | The project root has no `examples/` folder. **What to do:** Create it beside `docara.json`. |
| `PROJECT_EXAMPLE_NOT_FOUND` | There is no example folder with this `id`. **What to do:** Check the path: `id="utilities/animation-duration"` looks for `examples/utilities/animation-duration/`. |
| `PROJECT_EXAMPLE_CASE_COLLISION` | The letter case in `id` does not match the folder name, or the folder has two files that differ only in case. **What to do:** Write the path in lowercase and rename the repeats. |
| `PROJECT_EXAMPLE_PATH_ESCAPE`, `PROJECT_EXAMPLE_PATH_INVALID` | The example path leaves `examples/`, or the folder cannot be read. **What to do:** Put the example inside `examples/` as an ordinary folder, without links. |
| `PROJECT_EXAMPLE_FILE_FORBIDDEN` | The example folder has a file other than `index.html`, `index.css`, `index.js` and the `assets/` folder. **What to do:** Move the other files into `assets/`. |
| `PROJECT_EXAMPLE_SOURCE_INVALID` | `index.html`, `index.css` or `index.js` is larger than 1 MiB, not UTF-8, or a symbolic or hard link. **What to do:** Save the file as UTF-8 and shorten it. |
| `PROJECT_EXAMPLE_ASSET_ROOT_INVALID` | `assets` in the example folder is not an ordinary folder. **What to do:** Replace it with a folder. |
| `PROJECT_EXAMPLE_ASSET_INVALID` | A file in `assets/` is a link, larger than 10 MiB, or a text file that is not UTF-8. **What to do:** Replace the file with an ordinary one and make it smaller. |
| `PROJECT_EXAMPLE_ASSET_TYPE_FORBIDDEN` | The file type in `assets/` is not supported. **What to do:** Use AVIF, CSS, GIF, ICO, JPEG, JS, JSON, MP3, MP4, OTF, PDF, PNG, SVG, TTF, TXT, WAV, WebM, webmanifest, WebP, WOFF or WOFF2. |
| `PROJECT_EXAMPLE_ASSET_TOTAL_LIMIT` | The `assets/` files of one example are larger than 50 MiB together. **What to do:** Shrink or remove large files. |

## Page check

These findings are shown by `docara validate page <address>` and
`docara inspect page`, before the build.

| Code | Cause and what to do |
| --- | --- |
| `PAGE_LINK_TARGET_MISSING` | A link leads to a page of this site that does not exist. **What to do:** Fix the link address. |
| `PAGE_ASSET_MISSING` | An image refers to a file that does not exist. **What to do:** Put the file into `assets/` or fix the path. |

## Outline and in-page search

| Code | Cause and what to do |
| --- | --- |
| `DOCUMENT_OUTLINE_DEPTH_INVALID` | `reading.toc_depth` is outside the range 2–6. **What to do:** Give a number from 2 to 6. |
| `DOCUMENT_OUTLINE_HEADING_TEXT_REQUIRED` | A heading without text, for example one made of a single image without alt. **What to do:** Add text or an image alt. |
| `DOCUMENT_OUTLINE_INVALID_UTF8`, `SEARCH_TEXT_INVALID_UTF8` | The page contains bytes that are not UTF-8. **What to do:** Save the file as UTF-8. |
