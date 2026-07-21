# Workflow: user-ready documentation for declarative Docara

Date: 2026-07-21
Status: accepted
Workflow ID: `2026-07-21-docara-user-ready-documentation`
Process: Goal Mode Documentation
Baseline: `0f10afde92b93dd39703823ab22a2920b450a15b`
Publication target: `https://docara.test/`

## Goal

Привести документацию новой Docara к user-ready состоянию: устранить все
устаревшие и противоречивые инструкции, закрепить актуальный способ установки,
сделать все JSON и shell-примеры исполняемыми, добавить практические
руководства по обновлению проекта, брендированию, мультиязычному сайту и
созданию language pack, явно объяснить необязательность page JSON, добавить
developer-путь для создания Layout, Section, Block, View Tree и
Smart-компонента, обновить карту документации и changelog, затем пересобрать
docara.test и проверить все читательские маршруты, команды, ссылки и примеры.

## Audiences and reading paths

| Audience | Reader result | Entry | Required path |
| --- | --- | --- | --- |
| New author | first verified site | `/start/` | install -> edit Markdown -> build -> verify -> serve |
| Site owner | controlled site evolution | `/authoring/` | files -> branding -> layout -> locales -> update -> publish |
| Content author | pages without unnecessary config | `/authoring/markdown/` | Markdown-only -> optional sidecar -> components |
| Migrating owner | explicit safe transition | `/migration/` | inventory -> clean portable project -> verify -> rollback |
| Extension developer | safe registered capability | `/development/` | architecture -> composition extension -> tests -> catalogue |
| Maintainer/AI | drift-resistant source map | `docs/README.md` | owner source -> update trigger -> verification |

## Target documentation map

| Path | Genre | Purpose | Status |
| --- | --- | --- | --- |
| `README.md`, `docs/site/content/start.md`, `reference/cli.md` | tutorial/reference | one current immutable installation path | correction required |
| `authoring/project-files.md` | explanation | Markdown-only page and optional sidecar | correction required |
| `authoring/branding.md` | how-to | title, label, logo, favicon and header | planned |
| `authoring/localization.md` | explanation | locale model and valid minimal registry | correction required |
| `authoring/multilingual-site.md` | tutorial | build a real multi-locale site | planned |
| `authoring/language-packs.md` | how-to/reference | create and validate a project pack | planned |
| `build/update.md` | how-to | update package/starter with backup and rollback | planned |
| `development/composition-extensions.md` | developer how-to | Layout -> Region -> Section -> Slot -> Block -> Smart/View Tree | planned |
| `development/architecture.md` | explanation | language-pack and composition source map | correction required |
| `docs/README.md`, `docs/changes.md` | map/changelog | owner paths and completed capabilities | correction required |

## Specialists

| Specialist | Role | Gate |
| --- | --- | --- |
| documentation-methodologist | gatekeeper | genre and reader-task completeness |
| audience-analyst | reviewer | shortest complete reading paths |
| information-architect | author | map and navigation |
| user-docs-writer | author | author/site-owner guides |
| developer-docs-writer | author | extension path |
| style-editor-ru | reviewer | clear Russian and stable terminology |
| technical-verifier | gatekeeper | commands, JSON, paths and runtime facts |
| docs-maintainer | reviewer | drift and ownership boundaries |
| publication-integrator | gatekeeper | build, local publication and rollback |

The installed legacy Docara skill is not used: it describes the retired
Jigsaw product. The current repository, schemas, tests and generated site are
authoritative for the declarative product.

## Done when

- no current user path installs legacy Docara or an obsolete candidate;
- every copyable JSON and shell example in the affected reader paths is
  executable or explicitly labelled as a fragment;
- the five requested user/developer guides exist, are linked from their
  audience entrypoints and distinguish author from maintainer actions;
- Markdown-only pages and optional page sidecars are explained explicitly;
- README, public pages, architecture and changelog do not contradict the
  multi-locale runtime or language-pack ownership;
- documentation contract tests cover installation references, locale example,
  reader paths and new page inventory;
- full documentation build, static verifier, deterministic duplicate build,
  desktop/mobile browser acceptance and HTTP smoke pass;
- the accepted build is published only to local `docara.test` with a verified
  timestamped rollback copy;
- repository changes are committed; no public release or production-readiness
  claim is made.

## Batches

- [completed] Correctness: installation, contradictory claims and invalid examples.
- [completed] User paths: optional sidecar, branding, multilingual site, language packs, update.
- [completed] Developer path: registered composition extension.
- [completed] Navigation, docs map, changelog and automated contract coverage.
- [completed] Build/static/determinism/browser/publication acceptance.
- [completed] Reverse-outcome audit and exact final full suite.
- [completed] Implementation commit:
  `dd76a0aca4ecb10b173d7262ff2a39558c1042bb`.

## Current acceptance evidence

- accepted runtime candidate exists on GitHub branch at exact `0f10afd…`;
- 64 authored Markdown pages, one H1 each;
- all JSON fences parse; key site and pack examples pass product schemas;
- documented multilingual project builds `ru`, `en` and RTL `ar` outputs;
- partial project language pack resolves missing messages through explicit `en` fallback;
- shell fences contain no reference placeholders;
- production output: 164 HTML pages, 18,768 checked local references, zero broken;
- duplicate builds are byte-identical;
- local ServBay output digest:
  `98d419a958e40893bdf3e8e6fc0063a8e49026d7042c51d799290e3e0001984c`;
- local rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/build_production-pre-final-docs-20260721-133121`;
- desktop/mobile browser checks passed: active navigation, responsive regions,
  mobile menu, search discovery, zero horizontal overflow and zero console errors.
- exact final full suite: 607 tests, 5,254 assertions, PASS.

## Acceptance

Reader-route matrix:
`source/workflow/evidence/2026-07-21-docara-user-ready-documentation/reader-route-matrix.md`.

Final verdict:
`source/workflow/evidence/2026-07-21-docara-user-ready-documentation/acceptance.md`.

## Verification plan

- run exact commands or bounded disposable projects for install/update examples;
- validate JSON snippets with product schemas and runtime checks;
- extend `DocumentationContractTest` for stable reader paths and known drift risks;
- run full PHPUnit, PHP lint and `git diff --check`;
- build documentation twice and compare file manifests;
- run `verify-static` on source, staging and served trees;
- verify required routes and reader journeys in browser at desktop/mobile widths.

## Nonclaims

This goal does not publish a package version, create a release/tag, migrate
`ui-doc`, archive legacy repositories or claim production readiness.
