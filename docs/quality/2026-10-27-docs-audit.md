# Docara documentation audit (2026-10-27)

Method: `$docara` skill, `references/documentation-quality-method.md`.
Target: Docara 3.10.0 (local review branch), documentation site `docs/site`,
Russian locale (118 Markdown pages, about 61,500 words outside code blocks).
Read-only audit; no docs-only trial ran yet (section 7 of the method).

Marking: **measured** facts come from scripts over the sources and the build;
**judged** ones are the auditor's reading of the pages.

## 1. Ground truth

| Surface | Release | Documented | Source |
| --- | --- | --- | --- |
| CLI commands | 22 | 22 on `reference/cli.md` (measured) | `docara list` |
| Typed components | 30 | 30 pages; `docara.alert` catalogue `docs_ref` points at `start/component-model.md` instead of `components/alert.md` (measured) | `resources/component-catalog/typed` |
| Settings fields | all schema fields | generated tables on `reference/docara-json.md`, `reference/section-and-page.md` (measured) | `resources/schemas` |
| Diagnostic codes | 1134 | 420 (37 %) (measured) | `capture-diagnostic-code-coverage.php` |
| UI strings | 110 | 110 on `reference/lang-json.md` (measured) | `capture-lang-reference.php` |

## 2. Coverage map (Diátaxis)

T tutorial · H how-to · Ref reference · E explanation. `есть` / `частично` / `нет`.

| Area | T | H | Ref | E |
| --- | --- | --- | --- | --- |
| Getting started | есть (quick start) | частично (templates, project files) | — | есть (what is Docara, component model) |
| Writing pages | нет (no «first landing», «first multilingual site») | есть (markdown, navigation, redirects, multilingual, profiles) | частично (Markdown syntax mixed into the how-to) | частично |
| Components | нет | частично (usage inside component pages) | частично (no generated parameter tables, no accessibility) | частично (component model) |
| Settings | нет | есть (branding, regions, reading, search, address, preferences) | есть (generated) | есть (levels and inheritance) |
| Build and publish | частично (quick start) | есть (hosting, publish, verify, update, portal) | есть (CLI) | есть (`php-only` build page) |
| Extend (Smart, composition, SDK) | нет (no «first project component») | частично | частично (schemas, atlas) | есть, dense |
| Troubleshooting | — | есть (one symptom page) | частично (37 % of codes) | — |
| Quality, a11y, performance, security | — | частично (quality report) | частично (security page) | нет (a11y, browser support, performance) |
| AI | нет | частично (page profiles, tracking) | частично (`capabilities --json`, llms links) | частично |
| Versions and change | — | есть (update, upgrade) | нет (no changelog, deprecations or support policy on the site) | — |

## 3. Reference vs release

- 714 diagnostic codes have no page (measured). Codes are the first thing a
  reader searches after a failed build.
- `docara.alert` catalogue entry links the wrong page (measured).
- 28 pages under `examples/` and `demonstrator-results/` are 23–149 words each;
  `examples/composition-recipe-compact.md` and `-expanded.md` have no text
  (measured). They read as build fixtures, not reader pages (judged).
- Deprecated parameter names (A1 vocabulary) are diagnosed but not listed on a
  deprecations page (measured: no changelog or deprecation page).

## 4. Page standards

- Component pages: 1 of 30 has «Доступность» (measured); none has a generated
  parameter table; H2 names vary («Общий пример», «Вызов», «Наглядные
  варианты», «Оформление», «Параметры») (measured); typical length 100–180
  words (measured). The Framework reference standard (Пример, Разметка,
  Варианты, Состояния, Доступность, Параметры, См. также) is not followed
  (judged).
- English terms and jargon in Russian prose, mostly in development pages:
  «fail-closed», «LEGO-путь», «portable runtime», «production-каталог»,
  «ownership», English H2 on `components/landing-blocks.md` (judged, with
  examples measured by grep).
- `authoring/markdown.md` mixes a how-to with syntax reference (judged).
- `build/local-preview.md` (147 words) and `migration.md` (178 words) are too
  thin for their task (measured length, judged sufficiency).

## 5. Benchmark

| Criterion | State | Evidence |
| --- | --- | --- |
| First success under 15 minutes | есть | `start/quick-start.md`: real version `^3.0`, checks after steps |
| Search | есть | built-in index, synonyms via page tags |
| Navigation by role and task | частично | header now Главная / Руководство / Компоненты; no entry points per role; few task titles |
| Copyable, valid examples | частично | live examples on component pages; examples checked by build; Markdown snippets elsewhere unchecked |
| Live examples at device widths | есть | `:::example` with the responsive viewer |
| Reference completeness | частично | settings, CLI, strings complete; codes 37 %; components without parameter tables |
| Errors and troubleshooting | частично | one symptom page; error pages by area |
| Migration and versioning | частично | update/upgrade pages; no changelog, deprecations or support window on the site |
| Accessibility guidance | нет | no guide; 1/30 component pages |
| Performance and security | частично | security page; page-weight budget only in CI config; no CSP guidance |
| Integrations and hosting | частично | hosting page; no CI recipes (GitHub Actions, GitLab) |
| AI readiness | частично | llms links, AGENTS.md, `capabilities --json`, page profiles; no «Docara for AI agents» entry page |
| Language versions | нет | Russian only |
| Freshness | есть | generated references with `--check`, quality report in build |

## 6. Scores (0–10)

| Area | Score | Why |
| --- | --- | --- |
| Getting started | 7 | Own quick start with checks; no learning path after it |
| Writing pages | 7 | Strong how-to pages; syntax reference mixed in; no tutorials |
| Components | 5 | Every component has a live page; thin, no accessibility, no parameter tables, inconsistent sections |
| Settings | 8 | Generated reference plus a how-to per area |
| Build and publish | 7 | Hosting, publish, verify, update are solid; thin local preview, no CI recipes |
| Reference | 6 | CLI, schemas, strings, glossary complete; 63 % of codes missing; no changelog |
| Extend | 5 | Deep explanation, heavy jargon, no first-component tutorial |
| Examples and demonstrators | 3 | 28 thin pages, two empty, unclear reader |
| Troubleshooting | 6 | Symptom page plus code pages, limited by code coverage |
| Quality, a11y, performance, security | 4 | Security page only; no a11y, browser support or performance guide |
| AI | 6 | Machine-readable outputs exist; no entry page for agents |
| Language versions | 2 | No English site |

Docara's own `qa report` gives the site 100/100 (measured). The score checks
page hygiene, not the standards above, so it does not show these gaps.

## 7. Ten highest-impact gaps

1. 714 diagnostic codes without a page.
2. Component pages below the page standard: add «Разметка», «Варианты»,
   «Состояния», «Доступность» and a generated «Параметры» table.
3. No learning path beyond the quick start: first landing page, first
   multilingual site, first project component, publish to GitHub Pages.
4. No changelog, deprecation list or support policy on the site.
5. No accessibility guide or browser-support statement.
6. `examples/` and `demonstrator-results/` pages: thin or empty; decide their
   reader or remove them from the site.
7. Jargon and English terms in Russian prose in development pages.
8. No English version.
9. No CI and integration recipes; performance guidance only in CI config.
10. `qa report` does not check page-type standards; add the checks so the
    score reflects them (as ui-doc's `audit-reference-pages`).

## 8. Next steps

1. Owner review of this audit.
2. Restructure and page standards (gaps 2, 3, 6, 7), then reference gaps
   (1, 4, 5, 9), then the `qa report` checks (10).
3. A docs-only trial with 15 persona tasks before calling the set ready; the
   English version after the Russian set passes.

## Method notes and limits

- Coverage of commands is a grep for `docara <command>`; a page that
  describes a command without naming it is not counted.
- Section names and lengths are measured on the Markdown sources; judgement
  of sufficiency is the auditor's.
- The site was not tested by a docs-only trial yet; scores are an audit
  estimate, not a trial result.
