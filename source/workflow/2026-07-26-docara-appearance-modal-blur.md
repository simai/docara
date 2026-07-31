# Docara appearance preferences: modal background blur

## Outcome

Extend the existing `appearance` reader-preference group without creating a
second visual group. Add an author default and reader override for modal
background blur. The authored default is `large`.

## Product contract

- Author setting: `settings.modal_blur`.
- Values: `none`, `small`, `medium`, `large`.
- Reader field: `appearance.modal_blur`.
- Group: `appearance` (`Оформление`).
- Default: `large`.
- Effect: `docara.modal_blur`, applied before paint.
- Target: every `sf-modal[data-docara-transient-dialog]` owned by Docara.
- Framework mechanism: the existing `overlay-class` API and
  `backdrop-blur-*` utilities. No Docara-owned blur implementation.
- The modal scrim remains the Framework modal scrim; blur is an additional
  effect and never replaces darkening.

## Information architecture

Two controls do not justify additional groups. Theme and modal blur remain in
`appearance`, while each field receives its own visible title and description.
Create separate groups only after independent typography, motion, or layout
families exist.

## Compatibility and safety

- Missing `settings.modal_blur` resolves to `large`.
- Hiding the reader field disables the reader override but does not disable the
  authored value.
- Invalid values fail schema validation.
- Stored reader overrides remain versioned and site-scoped.

## Routing note

The federation resolver interpreted the word "settings" as Codex workspace
settings and selected the deprecated Docara skill. This task concerns product
UI settings, so execution follows the raw `dev`, `ux`, and SIMAI Framework
owner guidance, as explicitly requested by the product owner.

## Verification

- Reader preference compiler and portable builder focused suite: PASS,
  `5 tests`, `28 assertions`.
- Full PHPUnit suite: PASS, `331 tests`, `5137 assertions`.
- Production build: PASS, `90` authored pages.
- Static verification: PASS, `198` HTML pages, `14236` local references,
  zero broken references.
- Served tree equals the verified production build; HTTP exposes the new field,
  Russian copy and `backdrop-blur-large` default.
- Browser acceptance on `https://docara.test/ru/`: PASS. The setting changes
  both transient modals immediately, survives reload, and returns to the
  authored `large` value. Search and reader settings both resolve to a black
  Framework overlay with computed `blur(8px)` in light and dark themes.
- Browser console warnings and errors: none.
- `git diff --check`: PASS.

## Local publication and rollback

- Served path: `/Users/rim/Sites/docara.test/build_production`.
- Rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/appearance-modal-blur-20260726-173756/build_production.previous`.
- Source worktree remains intentionally uncommitted because it contains
  pre-existing unrelated changes; this batch did not overwrite or stage them.
