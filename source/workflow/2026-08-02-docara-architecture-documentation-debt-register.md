# Docara architecture and documentation debt register

Status: `closed_by_executor_evidence`; independent artifact retest remains
separate

Auditor source (read-only provenance):
`/Users/rim/Documents/GitHub/larena-workspace/source/workflow/2026-08-02-docara-architecture-documentation-debt-audit.md`

Audited revision: `3c491e5bfdf60c8227954b27d50dc050f058d71b`.
The auditor's twelve identifiers, priorities, affected surfaces and completion
meaning are preserved below. This copy is updated only with executor status and
evidence; it does not rewrite the independent source.

## Debt inventory

### DOCARA-DEBT-001 — obsolete required `language_pack` public field

Priority: P1. `site.schema.json`, locale models/registry, starter and docs still
require a field that PageBuilder does not consume. Done when fresh init and all
public contracts use only `content/<locale>/lang.json` for shared UI copy and
no public config/runtime field remains.

### DOCARA-DEBT-002 — public localization guides teach the retired model

Priority: P1. Language-pack/localization/multilingual/configuration pages teach
`languages/<locale>.json`, `language_pack`, component presentations and a
generated catalogue. Done when public docs clearly separate Markdown page
owners from shared locale UI labels and contain none of those instructions.

### DOCARA-DEBT-003 — documentation test positively requires legacy

Priority: P1. `DocumentationContractTest` requires the old guide/schema and
constructs `LanguagePackRepository`. Done when the new model is positive and
retired vocabulary/config/generated prose are negative failures.

### DOCARA-DEBT-004 — acceptance and roadmap overclaim readiness

Priority: P1. Checked statements contradict schemas/docs/artifact. Done when
claims are reopened during correction and rechecked only against fresh exact
candidate evidence.

### DOCARA-DEBT-005 — packaged README has broken local links

Priority: P1. README links to excluded `docs/specification/README.md` and
`docs/portable-format.md`. Done when an unpacked exact-ZIP local-link checker
reports zero broken links.

### DOCARA-DEBT-006 — route-owner file convention is ambiguous

Priority: P2. Starter uses flat `<route>.md`; specification examples use
`<route>/index.md`. Done when flat files are the recommended convention and
`index.md` is only a documented compatible form if retained by runtime.

### DOCARA-DEBT-007 — promised front matter and missing-page policy absent

Priority: P1/P2. `title`, `description`, `tags`, `draft`, `translation_key`
and `locales.missing_page_policy` are specified but not one runtime contract.
Done when parser/config/schema/diagnostics/tests implement the accepted fields
and fail/fallback behavior.

### DOCARA-DEBT-008 — logical architecture is not mapped to real code

Priority: P2. Architecture names nonexistent physical folders/classes and
inexact diagnostics. Done when a responsibility-to-class table and exact
stable runtime error-code table let a maintainer navigate without guessing.

### DOCARA-DEBT-009 — mixed `I18n` ownership hides dead pack runtime

Priority: P2. Locale routing/public `lang.json` and legacy package packs share
one area. Done when production consumers are inventoried and dead pack
repository/schema/data/runtime is removed after zero-reference, or a necessary
internal message contour is proved unable to enter public builds.

### DOCARA-DEBT-010 — component docs still promise generated public prose

Priority: P1/P2. Development/reference/build and repository developer docs
mention generated component pages/catalogue. Done when they describe physical
Markdown pages, one gateway and navigation/search derived from route results.

### DOCARA-DEBT-011 — public project tree omits locale directory

Priority: P2. `authoring/project-files.md` shows paths such as
`content/section.json`. Done when the example is literally reproducible from
the starter and distinguishes site-wide from locale-specific settings.

### DOCARA-DEBT-012 — `examples` namespace contract/schema conflict

Priority: P2. Authoring contract forbids `examples` while `lang.schema.json`
allows it. Done when one explicit namespace list is identical in schema,
runtime, docs and tests.

## Status table

| Debt | Status | Evidence |
| --- | --- | --- |
| DOCARA-DEBT-001 | closed | public field/schema/model removed; `r1c-language-boundary.md` |
| DOCARA-DEBT-002 | closed | public guides use only Markdown + locale `lang.json`; `r1c-authoring-runtime.md` |
| DOCARA-DEBT-003 | closed | positive new contract and negative legacy gates; `r1c-semantic-gates.md` |
| DOCARA-DEBT-004 | closed | readiness withdrawn, old artifact superseded, new gate remains pending independent retest |
| DOCARA-DEBT-005 | closed | ZIP link verifier and exact artifact broken=0; `r1c-candidate-and-update.md` |
| DOCARA-DEBT-006 | closed | flat owner recommended, compatible `index.md` documented and collision tested |
| DOCARA-DEBT-007 | closed | front matter and missing-page policy implemented/tested; `r1c-authoring-runtime.md` |
| DOCARA-DEBT-008 | closed | actual class/error-code mapping in architecture specification |
| DOCARA-DEBT-009 | closed | dead pack runtime/schema/data removed after consumer inventory |
| DOCARA-DEBT-010 | closed | public docs describe physical owners and derived navigation/search |
| DOCARA-DEBT-011 | closed | public project tree uses literal locale directory paths |
| DOCARA-DEBT-012 | closed | one schema/runtime/docs namespace list; `examples` rejected |

Remaining debt count: 0. Closure binds to source `56a2abf8…` and artifact
`04c18c95…`; structural tests are supplemented by exact ZIP, consumer,
update/rollback, public build/static and browser evidence. This does not pass
the tester-owned local release-readiness gate.
