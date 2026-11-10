---
description: A 20-minute tutorial — create your own project component with scaffold, style it with SIMAI Framework classes and put it on a page.
tags: [tutorial, project-component, smart, scaffold]
profile: tutorial
---

# Tutorial: a first project component

When the ready blocks are not enough, a project can have its own component.
In this tutorial you create a notice card with a title and text, style it
with SIMAI Framework classes and put it on the home page. It takes about 20
minutes.

You will go through six steps:

1. Declare the namespace of project components.
2. Draw up a scaffold plan.
3. Apply the plan.
4. Style the template.
5. Put the component on a page.
6. Build the site and check the result.

You need a project from the [quick start](/en/start/quick-start/), for
example one created with `php vendor/bin/docara init .`.

## 1. Declare the namespace

All project components start with one shared name, so they never clash with
the built-in ones. Add a `smart` field to `docara.json`:

```json {title="docara.json"}
{
  "smart": { "namespace": "project" }
}
```

Leave the other fields of the file as they are. The names `ui` and `docara`
belong to the built-in components.

## 2. Draw up a scaffold plan

```bash
php vendor/bin/docara scaffold smart project.notice-card --dry-run
```

The command writes nothing. It shows which files it would create and returns
a plan number in the `plan_id` field:

```text
"plan_id": "3fe97af5…"
```

## 3. Apply the plan

Pass the whole plan number:

```bash
php vendor/bin/docara scaffold --apply=<plan_id>
```

Three files appear:

```text
smart/project.notice-card/
  manifest.json          description: parameters, purpose, accessibility
  view/default.json      the default view
  template/default.php   the template that prints the HTML
```

`manifest.json` already declares two required parameters, `title` and
`text`. Check the scaffold:

```bash
php vendor/bin/docara validate smart project.notice-card
```

Every check in the answer must be marked `PASS`.

## 4. Style the template

The template gets the parameters in `$props` and prints HTML. Replace
`smart/project.notice-card/template/default.php`:

```php {title="smart/project.notice-card/template/default.php"}
<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<aside data-docara-smart="project.notice-card" class="flex flex-col gap-1/2 p-2 radius-surface border bg-surface-page m-bottom-1">'
    . '<strong>' . $title . '</strong>'
    . '<p class="m-0">' . $text . '</p>'
    . '</aside>';
```

- `htmlspecialchars` escapes the text, so nobody can slip markup in through
  the parameters. Do not remove it.
- `flex`, `p-2`, `radius-surface`, `border` and `bg-surface-page` are SIMAI Framework
  utilities. Docara loads their styles itself and adapts the colours to the
  light and dark themes.

## 5. Put the component on a page

Append to the end of `content/en/index.md`:

```markdown
:::project.notice-card
{"title":"New version","text":"The service is updated: search is faster now."}
:::
```

Parameters are written in JSON between the `:::` lines.

## 6. Build and check

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Open `http://127.0.0.1:8000/` and scroll the home page down: there is a
framed card with the title "New version" and the text below it. Stop the
preview with `Ctrl+C`.

Now check that the build watches the parameters. Remove `"text"` from the
call and build again — the build stops:

```text
[PORTABLE_SMART_PROPS_INVALID] Portable Smart [project.notice-card] has invalid [props.text.required]. Source [content/en/index.md:38:1].
```

The message names the parameter and the page line. Put `"text"` back.

## If something goes wrong

| Message | What to do |
| --- | --- |
| `SCAFFOLD_NAMESPACE_FORBIDDEN` | The component name does not start with the namespace from `docara.json`. Check step 1 and write `project.<name>`. |
| `SCAFFOLD_PLAN_ID_INVALID` | `--apply` got only part of the plan number. Copy the whole `plan_id`. |
| `SCAFFOLD_TARGET_EXISTS` | The component already exists. Pick another name. |
| `SMART_REGISTRY_COMPONENT_NOT_FOUND` | A typo in the component name on the page: there is no such component in `smart/`. |

Other codes are in the error reference:
[developer tools](/en/reference/errors/developer/) and
[layout and extension](/en/reference/errors/extend/#project-components).

## What next

- How project components work — styles and scripts in `assets/`, views,
  presets: [Smart components](/en/development/smart-components/).
- The whole path from plan to checks for developers and AI agents:
  [SDK for developers and AI agents](/en/development/developer-sdk/).
- A component can go inside a [surface](/en/components/surface/)
  once it declares itself embeddable.
