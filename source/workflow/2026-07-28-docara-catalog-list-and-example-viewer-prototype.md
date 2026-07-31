# Docara catalog list and example viewer prototype

## Outcome

- The public component index uses sequential category headings with short vertical link lists instead of a four-column grid.
- A single-surface `Demo / source` viewer is available for review in the component-system prototype.
- Production component detail pages keep their current example layout until the prototype is explicitly accepted.

## Product decisions

- Category hierarchy remains visible, but categories are read from top to bottom.
- Demo and source never compete for horizontal space.
- The source tab is named after its language (`Markdown`, `HTML`, `CSS`, `JSON`, and so on).
- Copy is an action of the source view and is hidden in the demo view.
- Tabs support click, Left/Right, Home/End, selected state, labelled panels and keyboard focus.

## Scope

- `PortableComponentCatalogProjector::indexFragment()` and its focused test.
- `source/workflow/prototypes/docara-component-system-preview.html#publishing`.
- Local documentation rebuild and browser verification.

## Exclusions

- No replacement of the production detail-page example renderer in this batch.
- No Framework release, default-branch merge, tag or production deployment.
- No use of the obsolete Docara skill.

## Verification

- Focused catalogue suite: `11` tests, `1442` assertions — PASS.
- Full PHPUnit suite: `333` tests, `6461` assertions — PASS.
- Production documentation build: `99` pages — PASS.
- Static verification: `198` HTML files, `17327` local references, `0` broken — PASS.
- Prototype DOM: unique IDs, both tab panels and source target present — PASS.
- Prototype inline JavaScript syntax — PASS.
- Browser check of `https://docara.test/ru/components/`: four sequential groups, `26` links, no horizontal overflow — PASS.
- Local deployment backup: `/Users/rim/Sites/docara.test/.docara-backups/build_production-20260729-000102`.

The in-app browser blocks local `file://` navigation by policy. The prototype was therefore structurally and syntactically verified without bypassing that restriction and remains ready for owner review in a normal local browser.

## Status

`implemented_pending_owner_visual_review`
