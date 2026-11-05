---
description: How to install the Docara skill for an AI agent, what it can do and how to work with it: a new site, moving documentation, edits, checks and preparing to publish.
tags: [ai, agent, skill, claude, codex]
---

# Working with an AI agent

The Docara skill teaches an AI agent to run a documentation site: create a
project, write pages, add components, build and check the site. The agent works
in your project folder, and you give it tasks in plain words. The skill is open,
under the Apache 2.0 license, and needs no tools other than Docara itself.

## Installation

The skill is the `skills/docara` folder in the
[simai/ai-skill-docara](https://github.com/simai/ai-skill-docara) repository.
Copy it into your agent's skills folder:

```bash
git clone https://github.com/simai/ai-skill-docara.git
# Claude Code
cp -R ai-skill-docara/skills/docara ~/.claude/skills/docara
# Codex
cp -R ai-skill-docara/skills/docara ~/.codex/skills/docara
```

The skill also suits other agents that support the
[Agent Skills](https://agentskills.io/specification) format: see the agent's
documentation for its skills folder. Building needs the same
[PHP, Composer and Node.js](/en/start/templates/) as without an agent.

## What you can ask for

The agent picks the skill on its own when the project has a `docara.json` or you
mention Docara. Sample tasks:

- "Create a documentation site for my library in English and Russian."
- "Move the documentation from the `docs/` folder to Docara and set up the menu."
- "Add an installation page with a tab for each system."
- "Update Docara to the latest version and check that the site builds."
- "The build fails with an error: find out why and fix it."

The skill relies on the Docara version installed in the project: it reads
`docara capabilities --json` and assumes no commands that version lacks. When it
finishes, the agent lists what it changed and which checks it ran.

## Publishing

The agent builds the site, runs `verify-static` and shows the result on a local
address. The finished site is a plain `build_production/` folder that you can
put [on any static host](/en/build/publish/). The skill does not deploy to a live server
by itself: access, a backup and the permission to deploy stay with you.

## The AGENTS.md file

`init` puts an `AGENTS.md` file in a new project: the page standard and the check
commands for any agent, even without the skill. The skill adds what that file
lacks: settings, components, updates and error diagnosis.

## See also

- [Quick start](/en/start/quick-start/): the same steps without an agent.
- [Developer SDK](/en/development/developer-sdk/): commands with JSON output and an MCP server for the agent.
- [Documentation quality](/en/authoring/documentation-quality/): how to check what the agent wrote.
