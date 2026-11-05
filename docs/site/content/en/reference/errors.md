---
description: Docara error codes with the cause and the fix. How to read an error message, the common settings codes and links to the codes by section.
tags: [error, errors, error-code, diagnostics]
---

# Error codes

When a build or a command stops, Docara prints a code in square brackets and
a short explanation in English:

```text
[FRONT_MATTER_KEY_UNKNOWN] Supported keys: title, description, ... Source [content/en/start.md], line [3], column [1].
```

The code does not change between versions and does not depend on the
language, so it is convenient to search by. The explanation after the code
names the file, line or field to fix. Commands with `--json` return the same
code in the list of diagnostics, in the `code` field.

Fix the source files (Markdown, `docara.json`, `section.json`,
`<page>.page.json`, `lang.json`, `redirects.json`) and run the build again.
Do not edit the result in `build_local` or `build_production`: the next
build replaces it.

## What a diagnostic consists of

Every error has a stable code and level, a safe path to the file and the
JSON Pointer of the field, a line and column for data from a file, an owner,
the source of the value (provenance) and a hint on what to do. The ordinary
output, `--json` and the optional MCP adapter are three views of one
`OperationResult`.

```bash
docara doctor --json
docara atlas --json
docara inspect smart ui.dropdown --json
docara inspect page /en/start/component-model/ --json
docara schema smart --json
```

A build receipt links a page address with the chain of source files and
settings, the engine version, the SIMAI Framework version, the hashes of
component descriptions, templates and assets, the hash of the parsed document
and the value sources, without private absolute paths. The field tables in
the reference give the exact place in the schema for each field:
`resources/schemas/<file>#<pointer>`.

If an error points to a file, first fix the named path, field or line, run
`doctor` or `validate` again, then the preview and the build. Do not edit a
build receipt to "fix" the source of a value.

## Codes by section

| Section | What it covers |
| --- | --- |
| [Page and Markdown errors](/en/reference/errors/content/) | Page metadata (front matter), Markdown blocks, components and their parameters, the outline |
| [Site settings errors](/en/reference/errors/site/) | Redirects, languages and translations, search, background effects, the 404 page, the site address, the theme, the brand, the portal |
| [Build errors](/en/reference/errors/build/) | The project root and paths, settings layers, rebuilding one page, files in the result, replacing the build folder |
| [Developer tool errors](/en/reference/errors/developer/) | The `scaffold`, `preview`, `qa` and `documentation` commands and the local MCP |
| [SIMAI Framework delivery errors](/en/reference/errors/framework/) | The lock file `simai-framework.lock.json`, presets, loading warnings, Framework file checks |
| [Presentation and extension errors](/en/reference/errors/extend/) | The menu and layout regions, your own shell composition, data bindings, project components, layouts and sections, composition recipes |
| [Update errors](/en/reference/errors/upgrade/) | The `upgrade` command and its rollback |
| [Build check marks](/en/reference/errors/verify-static/) | The `verify-static` marks that start with `@` |
| [Documentation quality errors](/en/reference/errors/quality/) | Findings of `docara qa report`: completeness, freshness, findability, readability, accessibility, links |
| [Internal errors](/en/reference/errors/internal/) | Checks of Docara itself, what to do if they fire, and the marks of passed checks |

## Common codes

These codes appear in any project: they check the settings files and the
folder structure.

| Code | Cause and what to do |
| --- | --- |
| `JSON_INVALID` | A settings file is not valid JSON: an extra or missing comma, single quotes, a comment. **What to do:** Open the file from the message and fix the syntax. An editor with JSON checking shows the exact place. |
| `JSON_OBJECT_REQUIRED` | The file holds an array, a string or a number instead of an object `{...}`. **What to do:** Wrap the settings in curly braces. |
| `SCHEMA_VALIDATION_FAILED` | A field is unknown, has the wrong type or a value outside the list. The message gives the JSON Pointer of the field, for example `/settings/theme`. **What to do:** Compare the field with the reference for [site settings](/en/reference/docara-json/), [section settings](/en/reference/section-and-page/) or [page settings](/en/reference/section-and-page/). Remove fields that are not in the reference. |
| `FILE_NOT_FOUND` | A required file is missing: `docara.json` or a file it refers to, for example `simai-framework.lock.json`. **What to do:** Run the command from the site folder or restore the file. The path in `framework_lock` is relative to the site folder. |
| `SYMLINK_FORBIDDEN`, `ROOT_SYMLINK_FORBIDDEN` | The path to the content, or the site folder itself, goes through a symbolic link. **What to do:** Replace the link with a real folder or file. Docara does not follow links, so that the build does not read files outside the project. |
| `PATH_ESCAPE_FORBIDDEN` | A path from the settings leads outside the site folder, for example through `../`. **What to do:** Give a path inside the project. |
| `CONTENT_ROOT_INVALID`, `CONTENT_ROOT_NOT_FOUND` | `content_root` is empty, unsafe or points to a folder that does not exist. **What to do:** Give a relative path to an existing folder with Markdown, for example `content`. |
| `PAGE_EXTENSION_INVALID` | A page has an extension other than `.md` or `.markdown`. **What to do:** Rename the file. |
| `PAGE_OUTSIDE_CONTENT_ROOT` | A page lies outside `content_root`. **What to do:** Move the page into the content folder or fix `content_root`. |
| `PORTABLE_OUTPUT_COLLISION`, `PAGE_SOURCE_ROUTE_AMBIGUOUS` | Two pages give the same address, for example `guide.md` and `guide/index.md`. **What to do:** Keep one of them or rename the other. |
| `FILE_INVALID` | A path from the settings points to something other than a regular file, for example a folder. **What to do:** Give the path to a file. |
| `PAGE_SOURCE_EXTENSION_INVALID` | The content folder holds a page without the `.md` extension. **What to do:** Rename the file to `.md`. |
| `PAGE_SOURCE_PATH_INVALID`, `PAGE_SOURCE_OUTSIDE_LOCALE_ROOT` | The page path contains `..` or leaves the language folder. **What to do:** Put the page inside the language folder. |
| `PAGE_SOURCE_LOCALE_ROOT_MISSING` | The content folder of a language from `locales` does not exist. **What to do:** Create the folder or fix the `content_root` of the language. |
| `PAGE_SOURCE_SYMLINK_FORBIDDEN` | The content folder holds a symbolic link. **What to do:** Replace it with a real file or folder. |
| `PAGE_SLUG_INVALID` | The `slug` of a page contains forbidden characters or `..`. **What to do:** Use lowercase Latin letters, digits, `-` and `/`. |
| `PAGE_SLUG_RESERVED` | The `slug` takes a Docara service address, for example `_docara`. **What to do:** Choose another address. |
| `PAGE_DRAFT_NOT_PUBLISHED` | A one-page build (`--page`) was requested for a draft with `draft: true`. **What to do:** Remove `draft` or build another page. |
| `PAGE_ROUTE_UNKNOWN` | The command got an address that matches no page. **What to do:** Check the address: it starts and ends with `/`, for example `/en/start/`. |
| `SECTION_DESCRIPTOR_LEGACY_NAME` | A section file with the old name `_section.json` was found. **What to do:** Rename it to `section.json`. Do not keep both files. |
| `COMPOSITION_RECIPE_RUNTIME_REQUIRED` | The `node` command or the SIMAI Framework page composition code was not found. The build needs Node.js 20 or newer; the finished site does not. **What to do:** Install Node.js 20 or newer, or give the path in the `DOCARA_NODE_BINARY` variable. |
| `PORTABLE_PAGE_TRANSFORM_FAILED` | A command from `page_transforms` ended with an error. The message has its exit code and output. **What to do:** Fix the command or its input. The command gets the folder of the built site as its last argument. |
| `PORTABLE_INCREMENTAL_BASE_MISSING` | A one-page build (`--page`) was started without a finished full build. **What to do:** First run a full build without `--page`. |
| `AUTHORING_JSON_INVALID`, `AUTHORING_SCHEMA_INVALID`, `AUTHORING_FILE_UNSAFE`, `AUTHORING_RULE_CONFLICT`, `AUTHORING_PROFILE_UNKNOWN` | An error in `docara.authoring.json`: invalid JSON, a link instead of a file, two rules with different profiles for one page, or an unknown profile. **What to do:** See [Page profiles](/authoring/page-profiles/#file-errors). |
| `AUTHORING_TEMPLATE_UNKNOWN`, `AUTHORING_TEMPLATE_INVALID`, `AUTHORING_RULE_INVALID`, `AUTHORING_TEMPLATE_OMIT_INVALID`, `PAGE_TEMPLATE_GENERATED_REGION_INVALID` | An error in page templates: an unknown template, a repeated section id or heading, a rule without a profile and template, an invalid `template_omit`, or generated-region marks without a pair. **What to do:** See [Page templates](/en/authoring/page-templates/#file-errors). |
| `PAGE_PROFILE_ONE_H1_MISSING`, `PAGE_PROFILE_INTRODUCTION_MISSING`, `PAGE_PROFILE_MULTIPLE_SECTIONS_MISSING`, `PAGE_PROFILE_MAIN_MATERIAL_MISSING`, `PAGE_PROFILE_ORDERED_STEPS_MISSING`, `PAGE_PROFILE_STRUCTURED_REFERENCE_MISSING`, `PAGE_PROFILE_ACTION_MISSING` | A `docara validate page` warning: the page lacks a part that its profile requires. The build continues. **What to do:** Add the missing part or change the profile. See [Page profiles](/authoring/page-profiles/#profiles-and-what-they-check). |
| `PAGE_PROFILE_INFERRED` | A `docara validate page` hint: the page has no profile, and Docara guessed it from the path and the text. The build does not stop. **What to do:** If the type was guessed correctly, write `profile:` in the front matter. See [Page profiles](/authoring/page-profiles/#if-no-profile-is-set). |
| `PAGE_EDITORIAL_REVIEW_REQUIRED` | A `docara validate page` reminder: a person checks the meaning questions of the profile. **What to do:** Go through the list of questions from the report. This is not an error. |
| `PAGE_TEMPLATE_SECTION_MISSING`, `PAGE_TEMPLATE_SECTION_UNKNOWN`, `PAGE_TEMPLATE_SECTION_ORDER` | A `docara validate page` note: the `##` sections of the page do not match its template: a required one is missing, an extra one is present or the order is wrong. The build does not stop. **What to do:** Bring the sections in line with the template, or list a deliberately skipped section in `template_omit`. See [Page templates](/en/authoring/page-templates/#what-docara-validate-page-checks). |
| `PAGE_TEMPLATE_EXAMPLE_MISSING`, `PAGE_TEMPLATE_SUBSECTION_EXAMPLE_MISSING` | A `docara validate page` note: a section, or a `###` heading in it, has no live example that the template requires. **What to do:** Add an `:::example` block. |
| `PAGE_TEMPLATE_GENERATED_SECTION_EDITED` | A `docara validate page` note: a generated section has text outside the `<!-- docara:generated -->` marks. **What to do:** Move the edit into the source code or into the program that writes the section, and run it again. |
| `PAGE_TEMPLATE_LOCALE_UNDECLARED` | A `docara validate page` hint: the template has no headings for the language of the page, so the page is not checked. **What to do:** Add headings in that language to the `heading` of the template sections. |
| `QA_DRY_RUN_REQUIRED` | `docara qa` was run without the `report` or `review-packet` kind and without `--dry-run` with an exact `--page`. **What to do:** To score the documentation, run `docara qa report`; for a browser check, run `docara qa <kind> <id> --dry-run --page=<address>`. |

## If a code is not in the reference

The reference starts with the codes that authors and site owners meet most
often. The other codes (internal checks of the build, Smart components and
the Framework lock) are so far described only by the message in the error
itself. The list of undescribed codes is kept in the Docara repository in
`docs/diagnostic-code-coverage.json` and is updated together with the code.

If a code is not described, read the explanation after it: it almost always
has the file and field name. If that is not enough, open
[Troubleshooting](/troubleshooting/) or ask in the
[Docara repository](https://github.com/simai/docara/issues) and attach the
full text of the error.
