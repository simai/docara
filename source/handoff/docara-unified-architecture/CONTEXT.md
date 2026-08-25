# Context for the architecture reset

> Routing status: **superseded historical context**. Use
> `source/handoff/2026-08-09-docara-current-main-onboarding/START.md` for the
> terminal state. The body below is retained for architecture provenance only.

## What was achieved before this branch

The existing Docara implementation demonstrates a portable PHP build, SIMAI
Framework integration, documentation and landing layouts, locale routing,
search, navigation, component examples, partial-build experiments and a large
amount of visual refinement. Those capabilities are valuable and must be
preserved through evidence, not by preserving every implementation path.

## What became structurally wrong

The audit preceding this branch found that ownership is blurred:

- public documentation prose appears in language-pack and catalog structures;
- some pages are projected from PHP/JSON instead of owned by physical Markdown;
- generated examples and trusted HTML can enter the page outside one typed
  document pipeline;
- full and partial builds can filter after expensive projections rather than
  call one PageBuilder for the requested route;
- component rendering and documentation content are coupled;
- current behavior is difficult to explain without reading a long task history.

The accepted correction is stricter than the initial contract: public shared
locale strings live only in `content/<locale>/lang.json`; `resources/i18n` and
`site.json` have no target compatibility path. Package-owned CLI/build messages
remain outside public builds. Typed Document IR is in-memory, with serialization
limited to disposable cache, search, `--dump-ir` or tests. Full and single-page
builds share the complete PageBuilder pipeline and differ only by route set.

The latest audit snapshot found only a small physical Markdown surface compared
with the generated public catalog, asymmetric locale records and several large
projectors. Exact counts may change; M0 must refresh them and store commands and
evidence rather than copying these observations as timeless facts.

## Why the new branch starts with mapping

Some target ideas may already exist partially in the baseline. Assuming that
they are absent would cause needless rewriting; assuming that they are complete
would preserve hidden parallel paths. M0 therefore maps actual code and tests
to every target module and deletion gate before runtime changes.

## What the current task contributes

The previous task is useful as historical evidence for visual and authoring
decisions: component syntax, examples, tables, code blocks, menu behavior,
layouts, branding, themes and framework-first constraints. Its messages are not
canonical requirements. Accepted decisions have been consolidated into
`docs/specification` and `graph/specs`.

## Product boundary

Docara is not Larena in miniature. It deliberately shares the same philosophy
of arrays, typed nodes, registries, Smart components and declarative layouts,
but remains a static, file-based and portable product without Laravel, a
database or an administrative CMS runtime.
