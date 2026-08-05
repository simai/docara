# C2 — design and interface root

The physical `/ru/design/` root and four child owners explain the three-level
composition model, map the real layout/view/section/block files, enumerate all
publisher shell surfaces and keep outer page/head application-owned.

Atlas directives project effective layout/section/block/view/binding/preset and
project entries from accepted registries. Three internal previews point to
existing production routes and use the accepted PageBuilder path; they do not
create a second preview engine or accepted production receipt.

Focused contract: `GoalCPublicDocumentationTest::design_root_maps_every_shell_surface_and_real_insertion_file`.

Rollback: revert the C2 commit; no existing route or design artifact is changed.
