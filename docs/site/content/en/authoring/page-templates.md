---
description: Page templates in docara.authoring.json — the exact list of sections for pages of one kind, the docara validate page check, a new page from a template and generated sections.
---

# Page templates

A template gives pages of one kind the same set of sections: which `##`
sections there are, in which order and which of them are required. A reader
finds the same thing in the same place on every reference page, and a person
and an AI assistant write new pages the same way.

A [profile](/en/authoring/page-profiles/) says which type a page is, for
example reference, and checks general signs: whether there is a table or a
list. A template is more exact: it names the sections themselves. A template
may have a profile, and then a page from the template gets it too.

Templates are optional. While no page has a template, Docara checks nothing
new.

## Example

A template for component pages and a rule that gives it to every page in the
`components/` folder:

```json
{
  "schema": "docara.authoring.v1",
  "templates": {
    "component": {
      "description": "A component page in the reference.",
      "profile": "reference",
      "sections": [
        {"id": "example", "heading": "Example", "required": true, "example": true},
        {"id": "markup", "heading": "Markup", "required": true,
         "hint": "The minimal correct markup and the required parts."},
        {"id": "variants", "heading": "Variants", "example_per_subsection": true},
        {"id": "accessibility", "heading": "Accessibility", "required": true},
        {"id": "classes", "heading": "Classes", "required": true, "generated": true},
        {"id": "see-also", "heading": "See also"}
      ]
    }
  },
  "rules": [
    {"match": "components/**", "template": "component"}
  ]
}
```

A page from this template starts with a `#` heading and a short
introduction, then come `## Example`, `## Markup` and the rest of the list.
The «Variants» and «See also» sections may be left out; the others are
required.

## Template fields

| Field | What it sets |
| --- | --- |
| `templates` | The project's templates. The key is the template id: lowercase Latin letters, digits, `_` and `-`. |
| `description` | What the template is for. Helps an AI assistant pick the right one. |
| `profile` | The profile of pages from the template. A profile in the front matter or in a rule with `profile` wins. |
| `other_sections` | `true` allows `##` sections that are not in the template. By default `false`: an extra section is a finding. |
| `sections` | The sections, in order. |
| `sections[].id` | The section's permanent id. `template_omit` refers to it. |
| `sections[].heading` | The text of the `##` heading. A string for every language or an object per language, for example `{"ru": "Пример", "en": "Example"}`. |
| `sections[].required` | `true` — the section is required. |
| `sections[].example` | `true` — the section needs a live `:::example`. |
| `sections[].example_per_subsection` | `true` — a live example is needed under every `###` heading of the section, so each variant or state has its own example. |
| `sections[].generated` | `true` — the section is written by the project's program, not by a person, see [Generated sections](#generated-sections). |
| `sections[].hint` | What to write in the section. It goes into a new page as a comment. |

## Which template a page gets

The template of a page is found like this; the first match wins:

1. `template` in the page's front matter.
2. A rule in `rules` with a `template` field whose path matches the page.

If rules with different templates match a page, the check stops with
`AUTHORING_RULE_CONFLICT`. One rule may name both `profile` and `template`.

```markdown
---
template: component
---

# Button
```

If a page leaves a section out on purpose, for example a component has no
classes of its own, list its id in `template_omit`:

```markdown
---
template_omit: [classes]
---
```

## What docara validate page checks

`docara validate page` compares the page's `##` sections with the template
and reports each difference with its own code. Headings inside code blocks
do not count, and an `{#id}` anchor at the end of a heading does not get in
the way.

| Code | What is wrong |
| --- | --- |
| `PAGE_TEMPLATE_SECTION_MISSING` | A required section is missing. |
| `PAGE_TEMPLATE_SECTION_UNKNOWN` | The section is not in the template, and `other_sections` is off. |
| `PAGE_TEMPLATE_SECTION_ORDER` | The sections are out of order. |
| `PAGE_TEMPLATE_EXAMPLE_MISSING` | A section with `example` has no live example. |
| `PAGE_TEMPLATE_SUBSECTION_EXAMPLE_MISSING` | A `###` heading of a section with `example_per_subsection` has no live example. |
| `PAGE_TEMPLATE_GENERATED_SECTION_EDITED` | A generated section has text outside its generated region. |
| `PAGE_TEMPLATE_LOCALE_UNDECLARED` | The template has no headings for the page's language. This is a hint: pages in that language are not checked. |

Like profile findings, they do not stop the build. In `docara qa report`
they make one `QUALITY_TEMPLATE_MISMATCH` finding and lower the page's
completeness score.

```bash
php vendor/bin/docara validate page /en/components/button/ --json
```

## A new page from a template

`docara scaffold page --template` creates a page with every section of the
template, in order:

```bash
php vendor/bin/docara scaffold page components/badge --template=component --title="Badge"
```

The new page has:

- `template`, the template's profile and `draft: true` in the front matter;
- hint text in the introduction and in each section;
- the `hint` as an HTML comment, which the site does not show;
- an empty generated region in a generated section;
- a hint to add a live example where one is needed.

Replace the hints, add the examples and remove `draft: true`.
`docara qa report` finds hints left on the page. A page from a template and a
page from a [documentation source](/en/authoring/documentation-tracking/) do
not combine: give one of them.

## Generated sections

A generated section is written by the project's program, for example a table
of classes from CSS or an API reference from a manifest. A person does not
edit it: to change it, change the source code or the program.

The program writes the section between two markers, each on its own line:

```markdown
## Classes

<!-- docara:generated -->
| Class | CSS |
| --- | --- |
| `sf-button` | `display: inline-flex` |
<!-- /docara:generated -->
```

The section's heading may also go inside the markers when the program writes
the whole section. The markers work on any page, not only in template
sections. A generated region does not affect translation freshness: the
program rebuilds it in every language itself. Unpaired markers stop the check
with `PAGE_TEMPLATE_GENERATED_REGION_INVALID`.

## File errors

| Code | Cause and fix |
| --- | --- |
| `AUTHORING_TEMPLATE_UNKNOWN` | A rule, the front matter or `--template` names a template that is not in `templates`. **Fix:** correct the id or add the template. |
| `AUTHORING_TEMPLATE_INVALID` | Two sections of a template share an id or a heading, or a generated section asks for a live example. **Fix:** give sections different ids and headings; the program writes an example in a generated section. |
| `AUTHORING_RULE_INVALID` | A rule has neither `profile` nor `template`. **Fix:** add one of them or remove the rule. |
| `AUTHORING_TEMPLATE_OMIT_INVALID` | `template_omit` lists an id that is not in the page's template. **Fix:** list ids of the template's sections. |

## See also

- [Page profiles](/en/authoring/page-profiles/) — the page type and `docara.authoring.json`
- [Page metadata](/en/authoring/markdown/#page-metadata) — the `template` and `template_omit` keys
- [Documentation quality](/en/authoring/documentation-quality/) — the score from 0 to 100
