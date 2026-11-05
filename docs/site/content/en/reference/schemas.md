---
description: "JSON schemas of the Docara settings and service files: which file matches which schema and what the build checks beyond the schema."
---

# JSON schemas

JSON schemas describe every Docara settings file and service file. Docara
uses JSON Schema Draft 2020-12 and rejects unknown fields, wrong value types
and unsafe paths at once.

| Schema identifier | File | Purpose |
| --- | --- | --- |
| `docara.site.v1` | `docara.json` | Site settings |
| `docara.section.v1` | `section.json` | Inherited section settings |
| `docara.page.v1` | `<page>.page.json` | Settings of one page |
| `docara.redirects.v1` | The file from `redirects_file` | Internal redirects |
| `docara.redirect_receipt.v1` | `build_<environment>.docara/redirects.json` | A checkable result of publishing the redirects |
| `docara.component_call.v1` | A normalised component call | The form of the identifier and parameters; what can be called is decided by component admission |
| `docara.framework_lock.v1` | `simai-framework.lock.json` | Pinned versions of the runtime, the component descriptions and the assets |
| `https://schemas.simai.io/docara/component-catalog-entry/v1` | An entry in the package sources | One entry of the component catalogue |
| `https://schemas.simai.io/docara/typed-component-definition/v1` | `resources/component-catalog/typed/*.json` | The description of a typed component |
| `docara.effective_component_catalog.v1` | `_docara/component-catalog.json` | The component catalogue of a specific build |
| `docara.search_index.v1` | `_docara/search-index.json` | The local search index |
| `docara.lang.v1` | `content/<locale>/lang.json` | Only the shared UI labels of a locale |
| `docara.translation_lock.v1` | `translations.lock.json` | Accepted translation hashes, the review level and exclusions |
| `docara.translation_status.v1` | `build_<environment>.docara/translation-status.json` | A deterministic report on the pages and keys of a locale |
| `docara.documentation_source.v1` | JSON from the project sources or a built-in provider | The public contract of what the documentation describes |
| `docara.documentation_lock.v1` | `documentation.lock.json` | Accepted links between pages, examples and fingerprints of the source API |
| `docara.documentation_status.v1` | `build_<environment>.docara/documentation-status.json` | A derived report on how current the documentation is against the source code |
| `docara.authoring.v1` | `docara.authoring.json` | [Page profiles](/en/authoring/page-profiles/) for `docara validate page` |

One more project file has no Docara schema: `page-weight.json` sets page
weight budgets for the `verify-page-weight.php` script; see
[Checking the build](/en/build/verify/). The format of `redirects.json` is
described in [Redirects](/en/authoring/redirects/), and `lang.json` in the
reference [lang.json interface labels](/en/reference/lang-json/).

## Checked examples

A minimal valid site:

<!-- docara-example valid schema=site.schema.json -->
```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json"
}
```

An invalid site with an unknown field:

<!-- docara-example invalid schema=site.schema.json error=SCHEMA_VALIDATION_FAILED -->
```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "unknown_setting": true
}
```

The invalid example shows the expected error; do not copy it into a project.

## Fields and levels

Which fields you can set at the site, section and page level, how they are
inherited and what their built-in values are is described in
[Site, section and page](/en/settings/levels-and-inheritance/). The full
field tables with types and validation rules are in the
[docara.json](/en/reference/docara-json/) and
[section.json and page.json](/en/reference/section-and-page/) references.

A presentation branch must hold at least one field: `{}` and `[]` are
rejected. An explicit reset is written as `{"$reset": true}`; you can combine
the reset with new keys of the same branch. Starter values, for example
search turned on, come from the shipped `docara.json`, not from a hidden
default.

## The component catalogue of a build

The machine-readable catalogue joins plain Markdown, typed Docara components
and Smart components admitted by the pinned Framework version. Requirement
records name unavailable features but give no syntax to call them.

The root of the catalogue holds immutable details of the Framework version
and the provider, sorted entries, `content_sha256` and a short list of what
the catalogue does not promise. The exact parameters, states, limits and
examples live in the source entries and the reference examples and are shown
in the [component reference](/components/), not repeated here.

`_docara/component-catalog.json` is a build result. The author does not edit
it and cannot extend the set of Smart components through it, through
Markdown or through presentation JSON.

## The page plan after the build

For every page Docara creates an explainable plan (the resolved page plan)
before writing the HTML.

The file `build_<environment>.docara/resolved-page-plans.json` (a build
receipt beside the result, not inside it) holds an array of pages with the
following data:

| Field | Meaning |
| --- | --- |
| `resolved_page_plan.configuration` | The result after inheritance |
| `trace` | The input files, their role, schema and SHA-256 |
| `provenance` | Which file set each value |
| `canonical_hash` | The hash of the canonical plan |
| `component_runtime` | Component calls and the asset plan |
| `output`, `url` | The physical and public routes |
| `declarative.plan.schema` | The version of the full `docara.resolved_render_plan.v2` |
| `declarative.plan.layout.view_tree` | The resolved safe layout frame |
| `declarative.plan.regions.*[].slots` | The named slots of a section |
| `declarative.plan.regions.*[].blocks` | Block calls with a stable identifier, slot and Smart component |
| `declarative.plan.diagnostics` | The result of composition and the View Tree check |

The plan is not a second source to edit. It is a generated diagnostic and
integration artifact. Change the Markdown or JSON, then rebuild the site.

The resulting fields `/reading/breadcrumbs`, `/reading/toc`,
`/reading/toc_depth` and `/reading/previous_next` are in `configuration`, and
their source is in `provenance`. The computed breadcrumbs, outline and
neighbour links themselves are not written into the plan as editable
settings: the build derives them deterministically from the content and the
canonical topology.

## Checks beyond the schema

The schema confirms the form of a file, and the build also checks:

- that paths exist and do not leave their folders;
- symbolic links and reserved result folders;
- the type, size and hash of brand files;
- the meaning of the Framework lock and that version references are
  immutable;
- component descriptions, parameters, dependencies and the asset layout;
- that every page belongs to a declared language, BCP 47 tags,
  non-overlapping language folders, fallback language cycles,
  `locales.missing_page_policy` and a separate `documentation_version`;
- that a redirect target exists, there are no duplicates, chains or cycles,
  and addresses stay inside `base_url`;
- that two pages do not write to one file.

So JSON that passed the schema check does not yet guarantee a successful
build if an external file or a build rule is broken.
