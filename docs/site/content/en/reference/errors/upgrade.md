---
description: Error codes of the docara upgrade command — choosing the version, checking the candidate, applying the plan and rolling back — with the cause and the fix.
---

# Update errors

These codes come from the `docara upgrade` command. Until the check passes,
the project files do not change, and on an error during apply Docara
restores the previous `composer.lock`, `vendor/` and build. The update order
is described on [Updating Docara](/build/update/).

## Choosing the version

| Code | Cause and what to do |
| --- | --- |
| `UPGRADE_ACTION_CONFLICT` | More than one action is given: `--check`, `--dry-run`, `--apply`, `--rollback`. **What to do:** Keep one action. |
| `UPGRADE_TARGET_VERSION_INVALID` | `--to` is not an exact stable version `X.Y.Z` but a branch, a range, `latest` or a pre-release. **What to do:** Give an exact version, for example `--to=3.7.0`. |
| `UPGRADE_CURRENT_VERSION_INVALID` | The installed Docara version in `composer.lock` is not recognised. **What to do:** Run `composer install` and try again. |
| `MAJOR_UPGRADE_REQUIRED` | Another major version was requested, for example 3.x for a project on 2.x. It is not installed automatically. **What to do:** Move by hand following [Moving from 2.x to 3.0](/en/build/update/#moving-from-2-x-to-3-0). |
| `UPGRADE_DOWNGRADE_FORBIDDEN` | A version older than the installed one was requested. **What to do:** To go back, use `docara upgrade --rollback=latest`. |
| `UPGRADE_TARGET_RESOLUTION_MISMATCH` | Composer chose a different version than requested, usually because of a constraint in `composer.json`. **What to do:** Check the `simai/docara` constraint in `composer.json`. |
| `UPGRADE_ENGINE_ADOPTION_REQUIRED` | The project was created before the engine ownership file (`.docara/engine`) existed. **What to do:** Run `docara update --dry-run --adopt --json` once, check the plan, then run `docara update --apply --json` and repeat `upgrade`. |

## Project and Composer

| Code | Cause and what to do |
| --- | --- |
| `UPGRADE_PROJECT_UNSAFE`, `UPGRADE_PROJECT_INVALID` | The project folder was not found, is a link or lacks a required project file, for example `docara.json`. **What to do:** Run the command from the site folder. |
| `PROJECT_LOCAL_RUNTIME_REQUIRED` | The project has no `composer.json`, `composer.lock` and `vendor/` of its own: Docara is not installed into the project. **What to do:** Run `composer require simai/docara:^3.0` in the site folder and try again. The old engine is not touched. |
| `UPGRADE_PROJECT_PATH_UNSAFE` | The `.docara/upgrades` folder is a symbolic link. **What to do:** Remove the link. |
| `UPGRADE_COMPOSER_MISSING` | The `composer` command was not found. **What to do:** Install Composer or add it to `PATH`. |
| `UPGRADE_COMPOSER_INVALID`, `UPGRADE_COMPOSER_LOCK_INVALID` | `composer.json` or `composer.lock` is damaged. **What to do:** Restore the file and run `composer install`. |
| `UPGRADE_COMPOSER_FAILED` | Composer could not install the new version: no network, a dependency conflict. The message has the Composer error text. **What to do:** Fix the cause from the Composer output and try again. |

## Checking the candidate

Before the replacement, Docara builds and checks the site on a copy of the
project.

| Code | Cause and what to do |
| --- | --- |
| `UPGRADE_CANDIDATE_CHECK_FAILED` | A check failed on the new version: `doctor`, `validate`, the build or `verify-static`. The message has the details. **What to do:** Fix the error found in the project, or stay on the current version. |
| `UPGRADE_CANDIDATE_INCOMPLETE`, `UPGRADE_CANDIDATE_INVALID` | The project copy with the new version was not built completely. **What to do:** Run `docara upgrade` again. |
| `UPGRADE_FAILED` | A general update error without a separate code. **What to do:** Read the message; the project files are restored in this case. |

## Plan and apply

| Code | Cause and what to do |
| --- | --- |
| `UPGRADE_PLAN_ID_INVALID` | `--apply` got a different identifier than `--dry-run` gave. **What to do:** Copy `plan_sha256` from the `--dry-run` result. |
| `UPGRADE_PLAN_NOT_FOUND`, `UPGRADE_PLAN_INVALID`, `UPGRADE_PLAN_HASH_MISMATCH` | The plan was not found or was changed. **What to do:** Run `--dry-run` again. |
| `UPGRADE_PLAN_STALE` | Project files changed after `--dry-run`. **What to do:** Run `--dry-run` again and apply the new plan. |
| `UPGRADE_INCOMPLETE` | The previous update was interrupted. **What to do:** Run `docara upgrade --rollback=latest` and try again. |
| `UPGRADE_PROMOTION_FAILED`, `UPGRADE_COMPENSATION_FAILED` | `vendor/` could not be replaced or restored: no permissions, or the disk is full. **What to do:** Check the permissions on the project folder and the disk space. Then run `composer install`. |
| `UPGRADE_STATE_COLLISION`, `UPGRADE_STATE_COPY_FAILED`, `UPGRADE_STATE_WRITE_FAILED`, `UPGRADE_STATE_CLEANUP_FAILED`, `UPGRADE_JOURNAL_INVALID` | The service files in `.docara/upgrades/` could not be written or read. **What to do:** Check the permissions on the `.docara/` folder. |

## Rollback

| Code | Cause and what to do |
| --- | --- |
| `UPGRADE_ROLLBACK_ID_INVALID` | An invalid rollback identifier. **What to do:** Give `latest` or the `rollback_id` from the `--apply` result. |
| `UPGRADE_ROLLBACK_UNAVAILABLE` | There is no update to roll back. **What to do:** No rollback is needed: the project is already on the previous version. |
| `UPGRADE_ROLLBACK_STALE` | `vendor/` changed after the update, and the rollback would overwrite those changes. **What to do:** Restore the version by hand: `composer require simai/docara:<version>`. |
