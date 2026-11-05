---
description: "How Smart components work: who owns ui.*, docara.* and project components, and how to declare, check and connect them."
---

# Smart components

A Smart component is a portable interface element. Its description
(`manifest.json`), views, template, and style and script files are checked
during the build. All components go the same way:

```text
Markdown -> typed Document IR -> renderer registry
-> SmartComponentGateway -> DeclarativePageRenderer -> PageBuilder
```

Docara does not pick how to render by the component name. The provider
registry finds the owner of the namespace, then the shared entry
(`SmartComponentGateway`) checks the description, view, preset, parameters,
template and files. An unknown or unsafe value stops the build.

## Who owns the components

| Namespace | Provider | Purpose |
| --- | --- | --- |
| `ui.*` | the pinned SIMAI Framework version (`simai-framework.lock.json`) | SIMAI Framework Smart components |
| `docara.*` | the Docara package (`docara.package`) | the Docara shell and navigation |
| the project namespace | `project.<namespace>` | the components of a particular site |

`ui.alert`, `ui.button`, `docara.brand`, `docara.navigation`, `docara.toc` and
`docara.preferences` go through the same shared entry. The old names
`docara.header` and `docara.outline` are recorded in the component description
as aliases, not in code.

## A project component

Declare one namespace in `docara.json`:

```json
{"smart":{"namespace":"project"}}
```

Components live only in the `smart/` folder. Settings and Markdown cannot name
a PHP class, a function or a template path.

```text
smart/project.notice/
├── manifest.json
├── view/default.json
├── template/default.php
└── assets/notice.css
```

`manifest.json` is written in the SIMAI Framework Smart component format.
Ready examples, including `project.notice`, come with the
`docara init --template=showcase --lang=ru` starter; the minimal starter has
no `smart/` folder. Creating your own component step by step is covered in the
[“Your first project component”](/en/start/first-component/) tutorial. In
Markdown, the component is called like this:

```markdown
:::project.notice
{"title":"Project component","text":"Built by the shared pipeline."}
:::
```

A new `project.*` component needs no changes to the Docara code. The build
stops if a name repeats, the namespace belongs to someone else, the folder
contains a symbolic link, a path leaves the folder through `..`, or a view,
preset, parameter, template or file is unknown.

### Examples in the starter

After `docara init --template=showcase`, the `/ru/project-demos/` page shows
two project components (the showcase starter is written in Russian):

- `project.install-builder` builds and copies a Composer command from
  `ui.input` and `ui.checkbox` fields;
- `project.product-configurator` calculates a demo price in the browser; the
  options of the `ui.dropdown` list in its `options` slot are child
  `ui.list-item` components with `type=text`.

Neither example contacts a server, orders anything, takes payment or runs
commands. You can also call them with an underscore —
`project.install_builder`, `project.product_configurator` — but the main names
use a hyphen. All four Framework components go through the same registry and
shared entry as the others; their description, template, files and browser
behaviour are checked against the pinned version before the HTML is rendered.

`ui.list-item` is supported narrowly here: only as a text option in
`ui.dropdown`. Icons, avatars, badges and a standalone `ui.list-item` are not
supported. You cannot replace the options with an `items` array, your own
markup or a copy of the Framework component.

### Which view is chosen

The same for any provider: an explicitly given view (`view`) wins; if there is
none, the preset (`preset`) picks the view; if there is neither, `default` is
used. This is component data, so a new component or variant needs no
conditions in the Docara code.

## The component template and files

A template is an ordinary PHP file. Docara passes it variables in the
SIMAI Framework 5 Smart component format and introduces no template language
of its own.

| Variable | What it holds |
| --- | --- |
| `$id` | the node identifier (string) |
| `$smart` | the full component name |
| `$manifest` | the checked component description (array) |
| `$view` | the chosen view (array) |
| `$preset` | the chosen preset or an empty array |
| `$props` | the checked parameters from the call (array) |
| `$childrenHtml` | the finished HTML of the child components (string) |
| `$slot` | the slot name or an empty string |

Output parameters only with escaping, for example
`<?= htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>`.
The `$view` object and `SmartTemplateContext` belong only to the older
templates inside the Docara package; new templates do not use them.

A project template counts as trusted developer code. Its path is set only by
the component folder and the entries in `manifest.json` and the view. Styles
and scripts are also listed in `manifest.json`, checked against the files and
connected to the pages together with the other build files.

The site's interface labels come from `content/<language>/lang.json`, the page
text from Markdown. The component description and the project settings are not
a source of documentation text.

### The component format

The component format is called `sf.smart_artifact_abi`, version `1.0.0`, with
the compatibility identifier `sf-smart-artifact-abi-v1`. SIMAI Framework owns
it. The old name `sf5.smart.artifact.v1` refers only to the former file layout,
not to a second format. The pinned revision
`b3cdff87563ff78e7eddf044048a4b298fc69036` passes the view, preset, slot and
hydration rules the same way in SIMAI Framework 5 and in Docara.

`docara schema smart --json` returns the SIMAI Framework
`smart.manifest.schema.json` schema unchanged; a `manifest.json` from a
scaffold passes it directly. Docara's extra requirements are checked by a
separate rule set, `docara.smart_admission.v1`, not by another schema.
`inspect smart ... --json` shows the format the same way for `ui.*`,
`docara.*` and project components; how the provider connected the component is
visible in the `provider_adapter` and `template_abi` fields.

## Checking

```bash
docara doctor
docara inspect smart project.notice --json
docara validate smart project.notice
docara build production
docara verify-static build_production
```

Start a new component with a plan: `docara scaffold smart project.name
--dry-run`, then `docara scaffold --apply=<plan_id>`. The details are on the
[“SDK for developers and AI agents”](/en/development/developer-sdk/) page.

A full build and a single-page build (`--page`) use the same builder. If page
addresses changed, you need a full build; the text of an existing page can be
rebuilt on its own.

## A new SIMAI Framework component

This section is for those who develop Docara itself. A new `:::ui.*` call does
not appear from a change to Markdown parsing, a schema or the catalogue: only
the exact `simai-framework.lock.json` lock file decides admission.

First make sure that plain Markdown or a ready Docara block does not solve the
task — see the [section overview](/en/development/#how-to-extend-capabilities).
Then:

1. add the component description following the public
   `sf.smart_artifact_abi` / `smart.manifest.schema.json` schema: the
   parameters, the rendering method and the immutable source revision;
2. add the description to the SIMAI Framework lock file with the provider
   revision and SHA-256;
3. list all files the component needs, including the dependencies of
   dependencies;
4. add narrow facts about its use in Docara to
   `resources/component-catalog/smart/`: they cannot widen the component
   description;
5. add tests for valid and invalid parameters, constraints, dependencies and
   pinning;
6. check the behaviour, keyboard, themes, screen widths and accessibility in
   the browser;
7. add one documentation page in Markdown and a separate test fixture; neither
   the component description nor `lang.json` owns the page text.

Each required file from the description must get into the connection plan or
be excluded by Docara's rules with a specific reason. `verify-static` checks
each published Framework file against the lock: the path, type, SHA-256 and
the complete set. Before the build folder is cleaned, the content of each
pinned file is checked, and so is whether the set of files matches all
dependencies of the admitted components.

The Framework core and utilities are published separately, with paths as in
the source `distr` folder: this way the core finds its parts without network
requests. A file in this folder does not yet mean the component is admitted:
that needs an accepted description and Docara rules. Server handlers, data
binding and action execution from commercial SIMAI packages are not admitted
in Docara; this restriction is visible in the machine catalogue.

### How to read the component catalogue

The `authoring.parameters` field describes what the author writes, not the
final template parameters. `required: false` means only that the parameter can
be left out. A `default` is present when the exact value is set in
`atlas.example_props`; otherwise the parameter is absent until it is given
explicitly or a preset is applied. `preset` is a separate optional parameter.
`validation` carries the rules for strings and numbers, and `mirrors` shows
which linked property is filled automatically, for example the accessible
name. `authoring.constraints` carries `allowed_combinations` and `requires`
from the description without loss, so that a developer or an AI agent does not
build incompatible combinations.

The build requires the facts in the catalogue to match exactly the
descriptions admitted by the lock: an extra facts file, a missing description,
a mismatched hash or a floating revision stops the build. The
`docara.component_call.v1` schema checks only the shape of a `ui.*` call and
does not decide whether the component is admitted.

Docara's facts cannot raise a component's readiness or widen its name,
category and states. A supported component must be marked `safe_to_suggest`
and `safe_to_render`. Its states match the states of the description in the
same order, except those listed in `consumer_policy.excluded_states`; each
exclusion is tied to a specific forbidden “property — value” pair, which is
itself checked against the schema. The behaviour description comes from
Docara's rules, not copied from the wider Framework description.

The exact supported calls, parameters and examples are in the
[component reference](/components/).

### How to record a need that is not ready yet

If the rendering method or the admission set is not ready yet, add a
non-executable record to `resources/component-catalog/requirements/` with the
state `admission_pending`, `framework_gap` or `deferred`. The record needs an
owner, a reason, what to use in the meantime and an admission condition. The
record makes the gap visible but does not enable the call.

Such a record never turns into `supported` in place. When everything is ready,
it is deleted, and the capability appears from its own source: the Markdown
profile, a Docara block description or the facts about a Smart component with
exact admission in the lock. A working capability requires a rendering method,
tests, documentation and a test fixture. The `_docara/component-catalog.json`
file in the build is not the main SIMAI Framework registry and does not confirm
release readiness. The old v1 description schema appears only in internal
package files for compatibility and is neither a public schema nor a second
format.
