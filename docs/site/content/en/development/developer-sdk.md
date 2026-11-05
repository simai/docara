---
title: SDK for developers and AI agents
description: How to safely find, create, check and preview a project component or layout with Docara commands — by hand or with an AI agent.
tags: [development, sdk, smart, design, mcp, ai, safety]
---

# SDK for developers and AI agents

The SDK is a set of commands with which a developer or an AI agent adds their
own component or layout to a project without changing Docara itself. These
commands have no separate rendering path: checks and previews go through the
same registries and the same page builder as an ordinary build. What passes
the SDK check also builds on the site.

## The short path

Run the commands from the project root:

```bash
docara capabilities --json
docara doctor
docara list smart
docara inspect smart ui.alert --json
docara schema smart --json

docara scaffold smart project.notice-card --dry-run
docara scaffold --apply=<plan_id>
docara validate smart project.notice-card
docara preview smart --page=/en/project-demos/ --selector=ui.dropdown
docara test smart ui.dropdown --page=/en/project-demos/ --json
```

`capabilities` is the first command for an AI agent. It reports which
operations, parameters and schemas exist in the installed Docara version. The
agent must not carry over knowledge from another project or from a newer
version.

A step-by-step example with a first component is in the
[“Your first project component”](/en/start/first-component/) tutorial.

## Step by step

The command line, the `--json` output and the optional MCP call the same
Docara services, so they share the same check rules.

### 1. Find what already exists

```bash
docara doctor --json
docara list smart --json
docara atlas --json
docara inspect smart project.install-builder --json
docara schema smart --json
```

These commands only read. The response contains the operation, the subject,
the result code, the owner, the file path and the place in it where the value
came from, and a suggestion.

### 2. Make a plan and preview the result

```bash
docara scaffold smart project.notice-card --dry-run
docara preview smart --page=/en/project-demos/ --selector=ui.dropdown
docara test smart ui.dropdown --page=/en/project-demos/ --json
```

A `--dry-run` writes nothing. It shows the paths and content of the future
files, the fingerprints (hashes) of the current files and the plan number,
`plan_id`. The preview builds a page fragment with the same code as the build,
but in a separate folder: it does not reach the site.

To take the plan number in a script, use `--json`:

```bash
PLAN_ID="$(docara scaffold smart project.notice-card --dry-run --json | php -r '$r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $r["data"]["plan_id"];')"
```

### 3. Apply the plan

```bash
docara scaffold --apply="$PLAN_ID" --json
docara validate smart project.notice-card --json
```

Only the plan shown by the dry run is applied. Before writing, Docara checks
all fingerprints again. The command stops and changes nothing if:

- settings or files changed after the dry run;
- a file already exists or differs from an existing one only in letter case;
- a path leaves the project folder through `..` or a link;
- the write touches Docara's own files, the lock file, the build result or
  folders outside the project.

### If an operation is rejected

```text
SDK_WRITE_PATH_UNSAFE
source: .docara-preview
pointer: /operations/preview/output
suggestion: remove the unsafe symlink and repeat dry-run
```

Do not edit the plan, the report or the build receipt. Fix the cause in the
project files, repeat the search and the dry run, and get a new plan. The codes
and what to do about them are in the
[“Developer tool errors”](/en/reference/errors/developer/) reference.

## What you can inspect

`list`, `inspect` and `schema` work with components (`smart`), layouts
(`layout`), views (`view`), sections (`section`), blocks (`block`), providers
(`provider`), test data (`fixture`), states (`state`) and schemas (`schema`).
The plain output and `--json` are two views of one
`docara.operation_result.v1` result; each finding has a permanent code, level,
owner, value source, place in the file and suggestion.

`schema smart --json` returns the schema of the `manifest.json` file — the
same one SIMAI Framework maintains, unchanged. On top of it Docara applies its
own `docara.smart_admission.v1` admission rules; this is not a second format.
So you can check a `manifest.json` from a scaffold against this schema
directly.

`inspect smart` describes Framework, Docara and project components the same
way: the component format is `sf.smart_artifact_abi` version `1.0.0`. The
`provider_adapter` and `template_abi` fields show how the provider connected
the component. The old format name `sf5.smart.artifact.v1` is kept only as an
alias (`storage_compatibility_alias`).

## What the SDK can write

`scaffold` creates only new files in `smart/` or `design/`. It does not
overwrite an existing component and does not write to `src/`, `resources/`,
`content/`, `assets/`, the lock file, the build result or another repository.
Leaving the folder through `..`, symbolic and hard links, a repeated namespace
and an outdated plan are rejected.

A component scaffold follows the SIMAI Framework 5 Smart component format. A
design scaffold creates a linked layout, its view, a section, its view and a
block. Everything created passes the check at once and can be selected in the
page's JSON settings; you can refine the files within their schemas.

## Visual check

```bash
docara qa smart ui.dropdown --page=/en/project-demos/ --dry-run --json
```

The command builds a separate preview and a screenshot plan: a wide and a
narrow screen, the light and dark themes, left-to-right and right-to-left
writing. The plan says what exactly is captured: the component itself, a
region or the whole layout.

Then there are three steps:

1. A browser runner (an optional developer tool) captures a draft reference.
2. `docara qa --finalize-reference=<draft number>` checks the screenshots and
   approves the reference: its number covers the page, the scenarios, the
   screenshot paths and the fingerprint of each image.
3. The runner captures the current state, and `docara qa --verify=<plan number>`
   compares it with the reference.

The check passes only if there are screenshots for all scenarios and there are
no accessibility errors, browser console errors, horizontal scrolling or
differences from the reference. Docara does not take the report on trust: it
recalculates the differences from the screenshots themselves.

For `test layout` and `qa layout`, the page in `--page` must actually use this
layout. Otherwise the command stops with the code
`SDK_TEST_LAYOUT_CONTEXT_MISMATCH` or `QA_LAYOUT_CONTEXT_MISMATCH` instead of
substituting a similar page.

## MCP for AI agents

The local MCP server runs separately:

```bash
php vendor/simai/docara/tools/mcp-docara/server.php
```

It gives the agent the same operations: `capabilities`, `doctor`, `list`,
`inspect`, `schema`, plan, `validate`, `test` and `qa`. It has no check or
rendering logic of its own. By default the server only reads. To let the agent
apply a plan, review the plan yourself and restart the server with the
`--allow-writes` flag. Even then, writing is limited to the project folder and
only to the exact `plan_id`. The site does not need MCP: it runs only on the
developer's machine.

## When you need a full build

A change to an existing page can be checked by building that one page. Adding,
renaming or deleting a page, or editing shared settings, `lang.json` or the
registries, needs a full build, so that the menu, search and receipts update
together.
