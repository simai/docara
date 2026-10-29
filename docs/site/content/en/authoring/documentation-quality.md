---
description: A documentation quality score from 0 to 100 — what docara qa report checks, how the score is computed, the strict mode for CI and the meaning review package for a person or AI.
tags: [quality, score, check, report, ci, ai]
profile: reference
---

# Documentation quality

`docara qa report` checks the built site and gives the documentation a score
from 0 to 100. The report shows which pages need attention and what to fix
first. `docara qa review-packet` prepares the questions a program cannot
answer: is it written correctly, is it clear, does the page solve the task. A
person or an AI assistant answers them.

Both commands work offline and change nothing in the pages. Docara itself
does not call AI. The same site always gets the same score.

## How to run it

Build the site first, then run the report for the same build:

```bash
php vendor/bin/docara build production
php vendor/bin/docara qa report --env=production
php vendor/bin/docara qa report --env=production --html --top=15
```

| Option | What it does |
| --- | --- |
| `--env` | Which build to check: `build_<env>/`. The default is `local`, as for `docara build`. |
| `--top` | How many items to show in the "fix first" list. The default is 10. |
| `--html` | Also write the report as a page. |
| `--min-score` | Strict mode: the command fails if the score is below the number. |
| `--json` | Print the result as JSON. |

The report is written next to the build, not into it:
`build_production.docara/quality-report.json`, and with `--html` also
`build_production.docara/quality-report.html`. These files do not reach the
published site.

Sample output:

```text
Documentation quality: 93/100 — 102 page(s) scored, 48 need attention, 15 skipped.

  completeness   83
  freshness      n/a (documentation tracking off)
  findability    93
  readability    95
  accessibility  100
  links          100

Fix first:
  1. +0.07  /ru/components/figure/  QUALITY_DESCRIPTION_MISSING
       No description in front matter or in the page file. Add description: one sentence about what the reader gets from the page.

Biggest gains by check (fixed on every page):
  +2.86  QUALITY_DESCRIPTION_MISSING on 42 page(s)

Report (json): build_production.docara/quality-report.json
```

If the project has `docara.authoring.json`, `docara build` prints one line
with the score after the build:

```text
Documentation quality: 93/100, 48 page(s) need attention (docara qa report --env=production).
```

Without that file the build prints nothing new. The build never stops
because of the score.

## What is checked

The checks fall into six groups. Each check has a permanent code, a level and
a hint on how to fix it. The "warning" level means the reader will probably
stumble. The "advice" level means the page is worth a look.

| Group | What is checked |
| --- | --- |
| Completeness | The page has the parts its profile requires. It has a `description` of 40 to 200 characters. The page has at least 80 words. No hint text from the `scaffold page` template is left. There are no links to drafts. |
| Freshness | The tracked sources of the page have not changed since the last review. The group works only when [documentation tracking](/en/authoring/documentation-tracking/) is on. |
| Findability | The page is in the menu and in search. Other pages link to it. It links to other pages itself or has a "See also" section. It has tags, or a word from the title repeats in the description or the first paragraph. |
| Readability | Sentences average no more than 20 words, and none is longer than 40 words. The page is no longer than 2500 words. A page over 600 words has `##` sections. There are no level 5 and 6 headings. In a language with a non-Latin script, no more than 25% of the words are in Latin script. English terms are explained in the site glossary. |
| Accessibility | Images have alternative text (`alt`). Link text says where the link goes, not "here". The page starts with one `#` heading. Heading levels go in order. Tables have a header row. The page language is set. |
| Links | Links lead to existing pages and files of the build, and `#…` anchors exist on the target page. |

The full list of codes with causes and fixes is on
[«Ошибки качества документации»](/ru/reference/errors/quality/) (in Russian).

Words are counted in the page text without code blocks, live examples and
buttons. Latin script is counted only for languages with another script:
Russian, Ukrainian, Chinese and others. The site glossary is a page named
`glossary.md`, `глоссарий.md` or `словарь.md`, or the address from the
`quality.glossary` setting. A term is a lowercase word in Latin script that
appears in the page text at least twice. Names like Docara, abbreviations like
JSON and ordinary English words like source, output or title are not terms.
Singular and plural are one term: if the glossary has asset, the word assets
is explained too.

The page topic counts as named if at least one title word of four letters or
more appears in the description or the first paragraph, in any form: for
example «Ошибки обновления» and «коды ошибок» match. Hyphenated words are
compared by their parts. If the title has no word longer than three letters,
for example "Code", the check does not apply.

Pages with `noindex` are left out of the score: the site itself hides them
from search.

## How the score is computed

The formula is simple and the same for every site:

1. Every group of a page starts with 100 points. A failed "warning" check
   takes 25 points, an "advice" check 10. A check takes points once, however
   many times the problem occurs on the page. A group does not go below 0.
2. The page score is the weighted average of the groups: completeness 25,
   freshness 10, findability 20, readability 15, accessibility 15, links 15.
   If a group does not apply, for example freshness without tracking, it is
   left out and the other weights keep their proportions.
3. The score of a group across the site and the site score are a plain
   average over the pages.

Example: a page has no description (completeness, warning) and an unclear
topic (findability, advice). Completeness gets 75, findability 90, the other
groups 100. Without documentation tracking the weights add up to 90, so the
page score is (25 × 75 + 20 × 90 + 15 × 100 × 3) / 90 ≈ 91.

A page "needs attention" if it has at least one failed "warning" check.

## What to fix first

For every problem found the report computes how many points the site score
would gain if only that problem were fixed. The "Fix first" list is ordered
by that number. On a tie "warning" checks come first, then pages that more
other pages link to.

Below it the output shows "Biggest gains by check": how many points fixing
one check on all pages at once would give. This list is a good start for a
big clean-up.

## Strict mode for CI

By default the report only advises. To make it stop a CI check, set a lower
bound for the score:

```bash
php vendor/bin/docara qa report --env=production --min-score=80
```

Or once in `docara.authoring.json`:

```json
{
  "schema": "docara.authoring.v1",
  "quality": {
    "min_score": 80,
    "glossary": "/en/reference/glossary/",
    "exclude": ["examples/**"]
  }
}
```

| Field | What it sets |
| --- | --- |
| `quality.min_score` | The lower bound of the score, from 0 to 100. If the score is lower, `qa report` exits with code 1 and reports `QUALITY_SCORE_BELOW_MINIMUM`. The `--min-score` option wins over this field. |
| `quality.glossary` | The address of the glossary page if it is not called `glossary`, `глоссарий` or `словарь`. |
| `quality.exclude` | Pages left out of the score, for example service examples. Paths start from the language folder, as in `rules`: `*` is part of a name, `**` any folders. |

## Meaning review

A program cannot tell whether a page is correct. So `docara qa review-packet`
prepares a package of questions for a person or an AI assistant:

```bash
php vendor/bin/docara qa review-packet /en/start/quick-start/ --env=production
php vendor/bin/docara qa review-packet --env=production
```

With an address the package covers one page, without it the whole site. The
package is written to `build_production.docara/review-packets/` in two forms:
JSON for programs and Markdown for reading. It contains:

- four questions for every page: is everything correct according to the
  sources, is it clear to the reader, does the page solve the task, do the
  examples work;
- the questions of the page profile, for example "Are all conditions named
  before the first step?" for a how-to;
- the page text, its profile, the readers from `audiences` and the tracked
  sources;
- the problems from the latest `qa report`;
- the answer format: question, verdict (`pass`, `fail` or `unsure`), quote
  and fix.

The `AGENTS.md` file that `docara init` creates asks an AI assistant to run
`qa report` and `qa review-packet` for every changed page. The same actions
are available over MCP: the `docara_qa_report` and `docara_qa_review_packet`
tools.

## What the report does not check

- External links: the check works offline.
- Whether facts and examples are right: these are questions for the meaning
  review package.
- Styling and layout: the browser checks `docara qa smart`, `region` and
  `layout` cover that (see the [CLI commands](/ru/reference/cli/#qa), in
  Russian).

## See also

- [Page profiles](/en/authoring/page-profiles/) — the parts a page type requires
- [Documentation freshness](/en/authoring/documentation-tracking/) — the source for the freshness group
- [«Ошибки качества документации»](/ru/reference/errors/quality/) (in Russian) — all check codes
- [CLI commands](/ru/reference/cli/#qa) (in Russian) — the options of `qa report` and `qa review-packet`
- [Checking the build](/en/build/verify/)
