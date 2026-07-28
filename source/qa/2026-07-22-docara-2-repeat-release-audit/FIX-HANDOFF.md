# Correction handoff

## Objective

Turn the working Docara 2 candidate into a reproducible, honestly documented
and locally demonstrable release candidate without reintroducing legacy paths.

## Batch 1 — Product and documentation contract

1. Add the optional target path to `init` while preserving current-directory
   mode, or explicitly choose cwd-only mode and remove `[path]` from every
   surface.
2. Test real commands copied from README, CLI reference and skill.
3. Exclude ignored `composer.lock` from Composer archives.
4. Add an archive determinism test comparing clean and post-install source
   states at the same SHA.

Acceptance: clean PHP 8.2/8.4 suites, exact source distribution and Composer
distribution pass; quick-start commands execute exactly as documented; package
file lists are identical.

## Batch 2 — Control plane

1. Correct the canonical Docara skill after the CLI decision.
2. Repair/update the federation through its supported installer, without
   manually rewriting release symlinks.
3. Verify canonical and installed skill content/checksums and run one installed
   skill smoke task.
4. Repair project-memory track selection and prove that a normal `continue`
   request resolves `docara-consolidation`.

Acceptance: no Jigsaw/Mix instructions remain in the active skill; route and
process resolvers both return the Docara 2 workflow without blockers.

## Batch 3 — Local site replacement

This is a separate operational change because it replaces a legacy local site.

1. Capture backup and rollback evidence for `/Users/rim/Sites/docara.test`.
2. Build from the exact corrected candidate in a clean target.
3. Switch ServBay document root or atomically replace the served build.
4. Repeat desktop/mobile smoke tests at `https://docara.test/`.
5. Archive or remove old Jigsaw/Mix data only after zero-reference verification.

Acceptance: served file inventory/digest matches the exact candidate and no
obsolete legacy routes remain.

## Batch 4 — Release hardening

Add PHP 8.2/8.4 CI coverage, archive acceptance and optional distribution
cleanup. Only then prepare push/PR/tag/publication as a separately gated release
operation.

## Stop conditions

- any user file changes during `init --update`;
- archive contents differ for one SHA;
- the installed skill still references Jigsaw/Mix;
- local-site replacement has no tested rollback;
- exact build and served build differ.
