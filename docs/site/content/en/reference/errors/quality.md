---
description: Codes of the docara qa report on documentation quality — completeness, freshness, findability, readability, accessibility and links — with the cause and the fix.
tags: [quality, score, errors]
---

# Documentation quality errors

These codes come from [`docara qa report`](/en/authoring/documentation-quality/).
The finding code stands at every page in the `quality-report.json` report
and in the "Fix first" list. Findings do not stop the build: "warning" means
that a reader will probably stumble, "advice" that the page is worth a look.
Every failed check takes 25 points (warning) or 10 points (advice) off the
page's group.

## Completeness

| Code | Level | Cause and what to do |
| --- | --- | --- |
| `QUALITY_PROFILE_PART_MISSING` | warning | The page lacks a part that its profile requires, for example numbered steps. If the profile was guessed, the level is advice. **What to do:** Add the part or set a suitable profile. `docara validate page <address>` shows the details. |
| `QUALITY_TEMPLATE_MISMATCH` | warning | The page does not follow its template from `docara.authoring.json`: a required section is missing, there is an extra section or the order is wrong, there is no live example, or a generated section was edited by hand. **What to do:** Bring the sections in line with the template, list a deliberately skipped section in `template_omit`, or run the program that writes the section again. `docara validate page <address>` shows the details; see [Page templates](/en/authoring/page-templates/). |
| `QUALITY_DESCRIPTION_MISSING` | warning | There is no `description` in the front matter or in the `<page>.page.json` file. A description that the build takes from the first paragraph does not count. **What to do:** Write one sentence about what the reader gets on the page. |
| `QUALITY_DESCRIPTION_LENGTH` | advice | The description is shorter than 40 or longer than 200 characters. **What to do:** Fit the description into one sentence of the right length. |
| `QUALITY_PAGE_STUB` | warning | The page has fewer than 80 words outside code blocks. **What to do:** Finish the page, merge it with a neighbour or mark it `draft: true`. |
| `QUALITY_PLACEHOLDER_LEFT` | warning | Hint text from the `docara scaffold page` template is left on the page. Text inside code blocks does not count. **What to do:** Replace the hint with real text. |
| `QUALITY_DRAFT_LINKED` | warning | The page links to a draft with `draft: true` that is not on the site. **What to do:** Publish the draft or remove the link. |

## Freshness

| Code | Level | Cause and what to do |
| --- | --- | --- |
| `QUALITY_SOURCE_CHANGED` | warning | The tracked source of the page changed after the last review. It works only with [documentation tracking](/en/authoring/documentation-tracking/). **What to do:** Update the page and confirm the review through `docara documentation plan` and `apply`. |

## Findability

| Code | Level | Cause and what to do |
| --- | --- | --- |
| `QUALITY_NOT_IN_NAVIGATION` | advice | No site menu leads to the page. **What to do:** Show the page in the menu or link to it from the section page. |
| `QUALITY_NOT_IN_SEARCH` | advice | Search is on, but the page is not in the search index. **What to do:** Remove `search.indexed: false`, or exclude a service page from the score through `quality.exclude`. |
| `QUALITY_ORPHAN_PAGE` | warning | No other page links to this one from its text. Menu links do not count. The home page of a language is not checked. **What to do:** Link to it from pages that the reader opens earlier. |
| `QUALITY_NO_RELATED_LINKS` | advice | The text has no links to other pages of the site and no "See also" section. **What to do:** Add links to the text or a "See also" section. |
| `QUALITY_TOPIC_UNCLEAR` | advice | The page has no tags, and neither the description nor the first paragraph repeats any word of four letters or more from the title, even in another form. **What to do:** Add `tags` or name the topic of the title in the description. |

## Readability

| Code | Level | Cause and what to do |
| --- | --- | --- |
| `QUALITY_SENTENCES_LONG` | advice | Sentences are longer than 20 words on average. Inline code counts as one word. Text in tables and paragraphs that list names in code, for example a list of icons, do not count. **What to do:** Split long sentences: one thought, one sentence. |
| `QUALITY_SENTENCE_TOO_LONG` | advice | There is a sentence longer than 40 words. As with the average length, tables and lists of names in code do not count. **What to do:** Split it into two or three. |
| `QUALITY_PAGE_TOO_LONG` | advice | The page has more than 2500 words outside code blocks. **What to do:** Split the page by tasks or topics. |
| `QUALITY_NO_SUBHEADINGS` | advice | More than 600 words and no `##` sections. **What to do:** Add sections so that the page can be scanned. |
| `QUALITY_HEADING_TOO_DEEP` | advice | There are level 5 or 6 headings. **What to do:** Manage with four levels or split the page. |
| `QUALITY_LATIN_HEAVY` | advice | In a language with a non-Latin script, more than 25% of the words outside code are written in Latin letters. **What to do:** Translate or explain the terms; format file, field and command names as code. |
| `QUALITY_TERM_NOT_IN_GLOSSARY` | advice | Three or more English terms appear in the text at least twice but are not in the site glossary. Common English words such as source, output or title do not count as terms. The check works if the site has a glossary. **What to do:** Explain the terms in the glossary or replace them with plain words. |

## Accessibility

| Code | Level | Cause and what to do |
| --- | --- | --- |
| `QUALITY_IMAGE_ALT_MISSING` | warning | An image has no alternative text. **What to do:** Write what it shows: `![Build diagram](scheme.png)`. |
| `QUALITY_LINK_TEXT_GENERIC` | warning | The link text does not say where it leads ("here", "click here", "more", or the same words in other languages), or there is no text at all. **What to do:** Name the target of the link: `[Publishing the site](/en/build/publish/)`. |
| `QUALITY_HEADING_ORDER` | warning | The page does not start with one `#` heading, or it has several such headings. **What to do:** Keep one `#` heading at the start of the page. |
| `QUALITY_HEADING_LEVEL_SKIPPED` | warning | A heading skips a level, for example `####` follows `##`. **What to do:** Go down one level at a time. |
| `QUALITY_TABLE_HEADER_MISSING` | warning | A table has no header row. **What to do:** Add a first row with the column names. |
| `QUALITY_LANG_MISSING` | warning | The built page has no `lang` attribute, or it does not match the language of the page. **What to do:** Check the page language in `docara.json`: the build writes `lang` from it. |

## Links

| Code | Level | Cause and what to do |
| --- | --- | --- |
| `QUALITY_LINK_BROKEN` | warning | A link in the text leads to a page or file that is not in the build, or outside the site. **What to do:** Fix the address or restore the page. |
| `QUALITY_ANCHOR_MISSING` | warning | A link leads to an anchor `#…` that the target page does not have. **What to do:** Link to an existing heading. A renamed heading changes its anchor. |

## Errors of the command itself

| Code | Cause and what to do |
| --- | --- |
| `QUALITY_BUILD_MISSING` | There is no full build to check: `build_<env>/_docara/` has no `page-metadata.json`. **What to do:** Run `docara build <env>` without `--page` and run the report again with the same `--env`. |
| `QUALITY_ENVIRONMENT_INVALID` | The environment name in `--env` contains forbidden characters. **What to do:** Use letters, digits, `_` and `-`, as in `docara build`. |
| `QUALITY_MIN_SCORE_INVALID` | `--min-score` is not a whole number from 0 to 100. **What to do:** Give a number, for example `--min-score=80`. |
| `QUALITY_TOP_INVALID` | `--top` is not a whole number. **What to do:** Give a number, for example `--top=15`. |
| `QUALITY_OUTPUT_UNSAFE` | The `build_<env>.docara` folder or the report file is a symbolic link or not a folder, or the write failed. **What to do:** Remove the link and run the build again. |
| `QUALITY_SCORE_BELOW_MINIMUM` | Strict mode: the score is below `--min-score` or `quality.min_score`. The command ends with exit code 1. **What to do:** Fix the "Fix first" items or lower the threshold. |

## See also

- [Documentation quality](/en/authoring/documentation-quality/) — how the score is computed
- [Page profiles](/en/authoring/page-profiles/)
- [Error codes](/en/reference/errors/)
