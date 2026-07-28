# Batch 4 — Human-Centered Simplicity preacceptance

Baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Status: candidate-ready self-review; independent exact complete-diff verdict
pending.

## Primary outcome

A reader can choose the useful appearance preference and undo that choice
without learning Docara internals or confronting unrelated layout controls.

## Complete changed-surface inventory

| Purpose | Exact files | Necessity |
| --- | --- | --- |
| Preference decision | `batch-4-reader-settings-decision.md` | Fixes the useful scope, Framework mapping, precedence, migration and acceptance contract before implementation. |
| Author documentation | `docs/site/content/authoring/reader-settings.md`; `authoring/configuration.md`; `authoring/layout-and-navigation.md`; `start.md` | Explains the author default, reader override, system behavior and reset from the existing beginner/configuration paths. |
| Framework ownership | `src/Framework/FrameworkAssetPlanner.php` | Uses the pinned Core boot contract to disable its competing binary resolver while retaining exact Framework classes, tokens and components. |
| Presentation and interaction | `src/PortableSite/PortableHtmlRenderer.php` | Renders the Framework-native trigger/radios/actions, native dialog, early theme restore and bounded preference controller. |
| Integration tests | `tests/PortableSiteBuilderTest.php` | Covers generated semantics, exact option count, persistence primitives, inherited reset target and absence of unadmitted Smart resources. |
| Implementation and acceptance evidence | `batch-4-reader-settings-implementation.md`; `batch-4-browser-ux-design-preacceptance.md`; `batch-4-correction-theme-ownership.md`; this file | Records commands, deterministic digest, rejected-candidate findings, correction proof, real-browser scenarios, product judgement and nonclaims. |

## Simplicity decisions

- one appearance preference instead of a general settings system;
- one author default plus one reader override instead of competing layout
  registries;
- native dialog and radio semantics instead of a custom modal/selection model;
- immediate application instead of save/cancel state;
- contextual reset hidden until useful;
- browser zoom and author `layout.max_width` retain their existing ownership;
- no new schema, service, database, dependency, Smart asset or Framework fork.

## Protective complexity retained

Pre-paint restoration, guarded storage, legacy-cookie compatibility, live
system changes, cross-tab synchronization, focus return, one-dialog-at-a-time
coordination, inherited reset behavior, exact Framework pins and deterministic
build verification prevent user-visible drift. They are bounded to the one
preference and are not a second theme system. Disabling Core's later binary
theme bootstrap is required ownership integration: Docara still applies the
exact Framework theme classes and tokens, but only one resolver may choose
which class is active.

## Preacceptance verdict

Every current changed file is covered by the inventory and contributes to the
reader outcome, documentation or proof. No optional control or decorative
surface remains. The worktree is candidate-ready, but only an independent
review of the exact baseline-to-candidate diff may return the closure verdict.
