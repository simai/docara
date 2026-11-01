---
description: Page profiles in docara.authoring.json — the type of a page, what docara validate page checks, how Docara guesses the type, a new page of a type and AGENTS.md for AI assistants.
---

# Page profiles

A profile says what type a page is: a how-to, a reference, a landing page and
so on. By the profile `docara validate page` checks whether the page has the
parts it needs, for example numbered steps in a how-to, and gives a list of
questions for the editor. The check changes nothing in the Markdown, does not
call AI and does not stop the build.

Profiles are optional. Without a `docara.authoring.json` file and without
`profile` in the front matter, pages are not checked and the build prints
nothing new. A new project from `docara init` gets this file right away,
together with a skeleton of pages by type and an `AGENTS.md` file for AI
assistants.

## The `docara.authoring.json` file

The file sits at the project root next to `docara.json`:

```json
{
  "schema": "docara.authoring.v1",
  "default_profile": "article",
  "rules": [
    {"match": "index.md", "profile": "landing"},
    {"match": "start.md", "profile": "tutorial"},
    {"match": "reference/**", "profile": "reference"},
    {"match": "development/architecture.md", "profile": "explanation"}
  ]
}
```

| Field | What it sets |
| --- | --- |
| `schema` | The file type. Always `docara.authoring.v1`. |
| `default_profile` | The profile of pages that no rule matched. Without it the profile of such pages is guessed, see [If no profile is set](#if-no-profile-is-set). |
| `infer_profiles` | `false` turns profile guessing off. The default is `true`. |
| `rules` | Rules: which profile pages get by their path. |
| `rules[].match` | The path of the page file from the language folder, for example `reference/cli.md`. `*` stands for part of a name inside one folder, `**` for any number of folders, `?` for one character. |
| `rules[].profile` | The profile for matching pages. |
| `rules[].template` | The template for matching pages, see [Page templates](/en/authoring/page-templates/). A rule needs `profile`, `template` or both. |
| `templates` | Page templates: which sections, in which order, see [Page templates](/en/authoring/page-templates/). |
| `audiences` | The list of site readers, for example `["reader", "author"]`. For now it is only checked for format and does not affect the result. |

If two rules with different profiles match a page, the check stops with
`AUTHORING_RULE_CONFLICT`. Narrow the paths so each page has one profile.

## A profile in the front matter

The profile of one page can be set in the page itself. It wins over the rules
of `docara.authoring.json`:

```markdown
---
profile: how_to
---

# How to publish the site
```

## Profiles and what they check

| Profile | What for | What the page must have |
| --- | --- | --- |
| `landing` | An entry to a section and a call to action | One `#` heading, an introduction, at least two `##` sections, a link or a block |
| `article` | A connected story about one topic | One `#` heading, an introduction, at least one `##` section |
| `tutorial` | Learning by example from start to result | One `#` heading, a numbered list of at least two steps |
| `how_to` | Solving one practical task | One `#` heading, a numbered list of at least two steps |
| `reference` | Exact facts to look up | One `#` heading, an introduction, a table, list, code or block |
| `explanation` | Reasons, design and limits | One `#` heading, an introduction, at least one `##` section |

If a part is missing, `docara validate page` reports a
`PAGE_PROFILE_<PART>_MISSING` code, for example
`PAGE_PROFILE_ORDERED_STEPS_MISSING`. Every checked page also gets
`PAGE_EDITORIAL_REVIEW_REQUIRED` — a list of questions about meaning for the
editor, which the program does not answer.

```bash
php vendor/bin/docara validate page /en/start/ --json
```

After `docara build` the build prints one line with the documentation
quality score and the number of pages that need attention, but the site is
built anyway. `docara qa report` gives the detailed report, see
[Documentation quality](/en/authoring/documentation-quality/).

## If no profile is set

If neither the front matter, nor a rule, nor `default_profile` matched a
page, Docara guesses the profile from the path and the text. The rules are
checked in order, and the first one wins:

| Sign | Profile |
| --- | --- |
| An `index.md` file in any folder | `landing` |
| Folder or file name: `tutorial`, `getting-started`, `quickstart`, `learn` | `tutorial` |
| Folder or file name: `how-to`, `howto` | `how_to` |
| Folder or file name: `reference`, `api`, `glossary` | `reference` |
| Folder or file name: `explanation`, `concepts`, `architecture` | `explanation` |
| At least three numbered steps | `how_to` |
| Tables, lists and definitions make at least 60% of the text lines | `reference` |
| Everything else | `article` |

A guessed profile is only a hint. `docara validate page` shows it with the
`PAGE_PROFILE_INFERRED` code and the reason, and the missing parts are marked
`advisory`. The build does not count them and does not stop. If the type was
guessed right, write `profile:` into the front matter. To turn guessing off,
add `"infer_profiles": false` to `docara.authoring.json`.

Guessing works only when the project has `docara.authoring.json`. This way
old sites without the file build as before.

## A new page of a type

`docara scaffold page` creates a page of the needed type: with front matter,
a place for `description` and the sections of that type.

```bash
php vendor/bin/docara scaffold page --type=how_to --title="How to rotate the keys"
php vendor/bin/docara scaffold page guides/install --type=tutorial --title="Installation"
```

Without an address the page goes to the folder of its type, if there is one
(`getting-started/`, `how-to/`, `reference/`, `explanation/`), and the file
name comes from the title. The template text is written in the language of
the main locale: Russian or English, and English for other languages. The
page is created with `draft: true`: replace the hint text and remove this
line so the page appears on the site. With `--dry-run` the command only
prepares a plan, as for Smart components.

## Instructions for AI assistants

`docara init` puts `AGENTS.md` at the project root. Many AI coding assistants
read this file. It describes the project, the page types and their required
parts, the writing rules, the command for a new page and the checks to pass
before finishing the work. You can edit the file.

If `AGENTS.md` already exists, `init` leaves it alone and writes the
instructions to `docs/AGENTS.docara.md`. Link to it from your own
`AGENTS.md`.

The same standard is returned by `docara capabilities --json` (the
`documentation_standard` field) and by the `docara_authoring_standard` MCP
tool.

## File errors

| Code | Cause and fix |
| --- | --- |
| `AUTHORING_JSON_INVALID` | The file is not valid JSON. **Fix:** correct the syntax. |
| `AUTHORING_SCHEMA_INVALID` | The file does not hold an object. **Fix:** start the file with `{"schema": "docara.authoring.v1", ...}`. |
| `SCHEMA_VALIDATION_FAILED` | An unknown field, a wrong profile, or a `match` path that starts with `/` or contains `..`. **Fix:** compare the file with the table above. |
| `AUTHORING_FILE_UNSAFE` | The file is a symbolic or hard link. **Fix:** replace it with an ordinary file. |
| `AUTHORING_RULE_CONFLICT` | Rules with different profiles match a page. **Fix:** narrow the paths in `match`. |
| `AUTHORING_PROFILE_UNKNOWN` | An unknown profile. **Fix:** use one of the six profiles. |

## See also

- [Page templates](/en/authoring/page-templates/) — the exact list of sections for pages of one kind
- [Page metadata](/en/authoring/markdown/#page-metadata) — the `profile` key
- [Starters and environment](/en/start/templates/) — the skeleton by page type and `AGENTS.md`
- [CLI commands](/ru/reference/cli/) (in Russian) — `scaffold page` and `validate page`
- [Documentation quality](/en/authoring/documentation-quality/) — a score from 0 to 100 and the meaning review package
- [Documentation freshness](/en/authoring/documentation-tracking/)
- [JSON schemas](/ru/reference/schemas/) (in Russian) — the `docara.authoring.v1` schema
- [Error codes](/ru/reference/errors/) (in Russian)
