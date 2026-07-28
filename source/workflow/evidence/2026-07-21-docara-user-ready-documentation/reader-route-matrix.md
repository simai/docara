# Reader route and thread-history coverage

Date: 2026-07-21
Candidate baseline: `0f10afde92b93dd39703823ab22a2920b450a15b`

## Coverage of previously discussed Docara concepts

| Topic from the working thread | Current reader-facing owner | Result |
| --- | --- | --- |
| Markdown, JSON and generated output | `authoring/project-files.md`, `authoring/configuration.md` | explained |
| Whether every Markdown file needs JSON | `start.md`, `authoring/project-files.md` | explicitly optional |
| Site, section and page inheritance | `authoring/inheritance.md`, `authoring/configuration.md` | explained and demonstrated |
| Layout and docs/landing presets | `authoring/layout-and-navigation.md` | explained with live examples |
| Header, sidebar, main, outline and footer | `authoring/regions.md` | enable/disable, inheritance and content calls documented |
| Sections, Blocks and Smart calls inside regions | `authoring/regions.md`, `development/composition-extensions.md` | author and maintainer paths separated |
| Templates separate from compiler | `development/architecture.md`, `development/composition-extensions.md` | exact owner paths documented |
| Declarative build chain | `development/architecture.md`, `development/declarative-preview.md` | stages and diagnostics documented |
| Brand, logos and favicon | `authoring/branding.md` | new executable how-to |
| Unlimited locale registry and RTL | `authoring/localization.md`, `authoring/multilingual-site.md` | corrected and executable |
| Project language packs and fallback | `authoring/language-packs.md` | new schema-backed how-to |
| Components and Smart components | `components.md`, generated catalog, `development/extensions.md` | user lookup and maintainer admission separated |
| Adding Layout, Section, Block, View Tree and Smart | `development/composition-extensions.md` | new fail-closed registration path |
| Updating Docara without losing content | `build/update.md` | new backup, update, verify and rollback path |
| Installation before portable release | `start.md`, `reference/cli.md`, `troubleshooting.md` | immutable remote candidate fixed |
| Legacy and template migration | `migration/*` | explicit, no implicit conversion |

## Corrections made in this goal

- retired candidate `2640503…` replaced by remote-verified `0f10afd…`;
- false “one build = one language” statement replaced by multi-locale contract;
- invalid locale fallback graph corrected by configuring every fallback locale;
- component technical data, translated presentation and exact fixtures assigned
  to separate owners;
- generic legacy `composer require simai/docara` removed from portable copy paths;
- page sidecars made explicitly optional;
- placeholder shell examples moved to `text` fences or replaced with exact
  executable commands.

## Reader acceptance paths

| Reader | Entry | Required finish |
| --- | --- | --- |
| New author | `/start/` | installed candidate, built, verified, served over HTTP |
| Content author | `/authoring/` | Markdown-only page, optional JSON, component lookup |
| Site owner | `/authoring/branding/` | product identity and assets verified in both themes |
| Multilingual owner | `/authoring/multilingual-site/` | three locale roots, RTL and explicit fallback |
| Translator | `/authoring/language-packs/` | schema-valid project pack with working fallback |
| Upgrader | `/build/update/` | backup, immutable package update, diff, verify, rollback |
| Extension developer | `/development/composition-extensions/` | registered definition, template/ViewModel boundary and tests |

## Remaining product boundary

The documentation describes an accepted local portable candidate, not a
published stable package or production-ready release. Project-level plugin
discovery for arbitrary composition definitions does not exist: new executable
IDs remain explicit package-maintainer registrations.
