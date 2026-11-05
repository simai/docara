---
description: Error codes of the commands for developers and AI agents — scaffold starters, preview, qa visual checks, documentation tracking, the local MCP — with the cause and the fix.
tags: [error, scaffold, preview, qa, documentation, mcp, sdk]
---

# Developer tool errors

These errors are returned by the commands that create and check parts of the
project: `scaffold`, `preview`, `qa`, `documentation`, and the same set of
operations through the local MCP. How these commands work is described in
[SDK for developers and AI agents](/en/development/developer-sdk/).

Most commands work in two steps: first `--dry-run` makes a plan and returns
its hash `plan_id`, then `--apply=<plan_id>` applies exactly that plan. If
the project changed between the steps, the plan is not applied.

## Project

| Code | Cause and what to do |
| --- | --- |
| `SDK_PROJECT_ROOT_INVALID` | The project folder is a symbolic link or does not exist. **What to do:** Run the command from the real project folder. |
| `SDK_PROJECT_CONFIG_MISSING` | The folder has no ordinary `docara.json` file. **What to do:** Go to the site folder or create a project with `docara init`. |
| `SDK_PROJECT_CONFIG_INVALID` | `docara.json` is not valid JSON. **What to do:** Fix the syntax of the file. |
| `SDK_PROJECT_NAMESPACE_INVALID` | `smart.namespace` in `docara.json` contains forbidden characters. **What to do:** Start the name with a lowercase Latin letter and use only lowercase letters, digits, `_` and `-`, for example `project`. |
| `SDK_PROJECT_OWNED_ROOT_UNSAFE` | The `smart/` or `design/` folder that the commands write to is a symbolic link. **What to do:** Replace the link with an ordinary folder. |
| `SDK_WRITE_PATH_UNSAFE`, `SDK_WRITE_COLLISION` | The command cannot write a file safely: the path leads outside the project, goes through a link, or the file already exists. **What to do:** Remove the link or the existing file named in the message, and run `--dry-run` again. |
| `SDK_DISCOVERY_KIND_UNKNOWN`, `SDK_SCHEMA_KIND_UNKNOWN` | The `list`, `inspect` or `schema` command got an unknown kind. **What to do:** Take a kind from `docara list` or from the command's `--help`. |
| `SDK_TEST_LAYOUT_CONTEXT_MISMATCH` | `docara test layout` got a page that uses another layout. **What to do:** Give in `--page` a page where this layout is actually chosen. |
| `SDK_ARGUMENT_REQUIRED` | The command lacks a required argument; its name follows the colon in the message. **What to do:** Add the argument, for example `id` for `validate smart <id>`. |
| `SDK_DISCOVERY_SUBJECT_UNKNOWN` | `inspect` did not find an object with this `id`. **What to do:** Take the `id` from `docara list <kind>`. |
| `SDK_VALIDATION_KIND_UNKNOWN`, `SDK_TEST_KIND_UNKNOWN` | `validate` or `test` got an unknown kind. **What to do:** For `validate` give `project`, `page`, `source`, `smart`, `layout`, `view`, `section` or `block`; for `test`, `smart` or `layout`. |
| `SDK_TEST_PAGE_REQUIRED` | `docara test` was run without a page. **What to do:** Add `--page=/en/address/`. |
| `SDK_OPERATION_UNKNOWN` | An unknown operation was called through MCP or the SDK. **What to do:** Use the operations from `docara capabilities --json`. |
| `SDK_INPUT_INVALID` | A command argument is written incorrectly. **What to do:** Check `docara <command> --help`. |
| `SDK_OPERATION_FAILED` | The operation stopped for a reason that has no code of its own. **What to do:** Read the message text; if it does not help, see [Internal errors](/en/reference/errors/internal/). |

## Starters: scaffold

| Code | Cause and what to do |
| --- | --- |
| `SCAFFOLD_KIND_INVALID` | An unknown starter kind. **What to do:** Give `smart`, `design` or `page`. |
| `SCAFFOLD_NAMESPACE_FORBIDDEN` | The component name does not start with the project namespace. **What to do:** Write `project.name` if `docara.json` has `"namespace": "project"`. |
| `SCAFFOLD_TARGET_EXISTS` | The starter file or folder already exists. **What to do:** Choose another name or delete the old starter. |
| `SCAFFOLD_PAGE_ARGUMENT_REQUIRED` | The language or the title is not given for a page. **What to do:** Add `--locale=en --title="…"`. |
| `SCAFFOLD_PAGE_ROUTE_INVALID` | The page address is written incorrectly. **What to do:** Write it in lowercase Latin letters, digits and hyphens inside the language: `guides/new-page`. |
| `SCAFFOLD_PAGE_PROFILE_INVALID` | An unknown page type in `--type`, or the template from `--template` has no profile and `--type` is not given. **What to do:** Give `landing`, `article`, `tutorial`, `how_to`, `reference` or `explanation`, or add a `profile` to the template. |
| `SCAFFOLD_PAGE_SOURCE_ARGUMENT_INVALID` | Only one of `--source` and `--entity` is given. **What to do:** Give both or neither. |
| `SCAFFOLD_PAGE_VALIDATION_FAILED` | The page was created, but the build does not find it, for example because a navigation setting hides it. **What to do:** Check the `section.json` of the section and run `docara validate page <address>`. |
| `SCAFFOLD_DRY_RUN_REQUIRED` | A plan is applied without a preview first. **What to do:** First run the command with `--dry-run`, then `--apply=<plan_id>`. |
| `SCAFFOLD_PLAN_ID_INVALID` | The wrong hash was passed to `--apply`. **What to do:** Copy the whole `plan_id` from the `--dry-run` answer. |
| `SCAFFOLD_PLAN_INVALID`, `SCAFFOLD_PLAN_HASH_MISMATCH` | The plan file in `.docara/sdk-plans/` is damaged or was changed by hand. **What to do:** Make the plan again with `--dry-run`. |
| `SCAFFOLD_PLAN_STALE`, `SCAFFOLD_CONTENT_HASH_MISMATCH` | After `--dry-run`, `docara.json` or the files the plan was going to write changed. **What to do:** Run `--dry-run` again and apply the new plan. |
| `SCAFFOLD_PLAN_COLLISION` | A plan with this hash is already written. **What to do:** Apply it with `--apply=<plan_id>` or delete the plan file from `.docara/sdk-plans/`. |
| `SCAFFOLD_SOURCE_TEMPLATE_INVALID` | The example template from the documentation source is damaged. **What to do:** Update the source and run `--dry-run` again. |

## Fragment preview: preview

`docara preview` shows one component, region or layout from a finished page
of the site.

| Code | Cause and what to do |
| --- | --- |
| `PREVIEW_PAGE_INVALID`, `PREVIEW_PAGE_UNKNOWN` | The page in `--page` is not written as a public address, or there is no such page. **What to do:** Give the address with `/` at the start and end: `/en/project-demos/`. |
| `PREVIEW_PAGE_MISSING`, `PREVIEW_PAGE_OUTPUT_INVALID`, `PREVIEW_RECEIPT_INVALID` | The page is not built yet, or the build is out of date. **What to do:** Run a full build and try again. |
| `PREVIEW_SELECTOR_INVALID`, `PREVIEW_SELECTOR_FORBIDDEN` | `--selector` is given for `page` or `layout`, or for other kinds it is missing or written incorrectly. **What to do:** For `page` and `layout` remove `--selector`; for the others give the name in lowercase: `--selector=ui.dropdown`. |
| `PREVIEW_TARGET_NOT_FOUND` | The page has no such component or region. **What to do:** Choose a page where it stands, or check the name. |
| `PREVIEW_WATCH_OPTIONS_INVALID` | `--interval` or `--max-cycles` is outside the allowed values. **What to do:** Give an interval from 50 to 5000 milliseconds and a number of cycles of at least 0. |
| `PREVIEW_DEPENDENCY_PATH_INVALID` | A file that `--watch` follows lies outside the project or became a link. **What to do:** Remove the link and restart the preview. |
| `PREVIEW_OUTPUT_COLLISION`, `PREVIEW_PUBLIC_ROOT_INVALID`, `PREVIEW_PUBLIC_ROOT_PURPOSE_INVALID`, `PREVIEW_PUBLIC_FILE_INVALID`, `PREVIEW_HTML_INVALID`, `PREVIEW_ASSET_PROVENANCE_INVALID` | The preview folder is taken, damaged or not built for preview. **What to do:** Delete the preview folder named in the message and run the command again. |

## Visual check: qa

`docara qa` captures a component, region or layout in the light and dark
theme, on a wide and a narrow screen, and compares the captures with a
reference.

| Code | Cause and what to do |
| --- | --- |
| `QA_KIND_INVALID` | An unknown check kind. **What to do:** Give `smart`, `region` or `layout`. |
| `QA_LAYOUT_CONTEXT_MISMATCH` | The page in `--page` uses another layout. **What to do:** Give a page where this layout is chosen. |
| `QA_TARGET_LOCATOR_MISSING`, `QA_TARGET_BINDING_INVALID` | The checked element was not found on the page, or the plan belongs to another element. **What to do:** Check the name and the page, and make the plan again with `--dry-run`. |
| `QA_PLAN_ID_INVALID`, `QA_PLAN_ID_MISMATCH`, `QA_ARTIFACT_INVALID`, `QA_PREVIEW_BINDING_INVALID`, `QA_PLAN_COLLISION` | The plan in `.docara-qa/plans/` does not match its hash, is damaged or already exists. **What to do:** Pass the exact `plan_id` from the `--dry-run` answer or make the plan again. |
| `QA_REFERENCE_DRAFT_INVALID`, `QA_REFERENCE_DRAFT_BINDING_INVALID`, `QA_REFERENCE_DRAFT_SCENARIOS_INVALID`, `QA_REFERENCE_COLLISION` | The reference draft is incomplete, was captured for another plan or is already approved. **What to do:** Capture the reference again for the current plan and run `--finalize-reference` again. |
| `QA_REFERENCE_INVALID`, `QA_REFERENCE_BINDING_INVALID`, `QA_REFERENCE_ID_MISMATCH`, `QA_REFERENCE_MANIFEST_MISMATCH` | An approved reference was changed after approval. **What to do:** Do not edit the reference captures by hand; approve a new reference. |
| `QA_SCREENSHOT_INVALID`, `QA_REPORT_BINDING_INVALID`, `QA_REPORT_SCENARIOS_INCOMPLETE` | There are fewer captures than in the plan, or the report was made for another plan. **What to do:** Run the comparison again for the approved plan. |
| `QA_VISUAL_DIFF_MISMATCH` | A capture differs from the reference. **What to do:** Look at the difference; if the change is intended, approve a new reference. |
| `QA_ACCEPTANCE_FAILED` | There are accessibility errors, browser console errors or horizontal scrolling. **What to do:** Fix the problems found in the report and run the check again. |

## Documentation tracking

Tracking compares pages with sources, for example with the description of
SIMAI Framework components. The setup is described in
[Documentation freshness](/en/authoring/documentation-tracking/).

| Code | Cause and what to do |
| --- | --- |
| `DOCUMENTATION_TRACKING_DISABLED` | Tracking is off. **What to do:** Turn on `documentation_tracking` in `docara.json`. |
| `DOCUMENTATION_PROVIDER_UNKNOWN` | An unknown source provider. **What to do:** Give `simai_framework` or `contract_json`. |
| `DOCUMENTATION_SOURCE_UNKNOWN`, `DOCUMENTATION_SOURCE_DUPLICATE`, `DOCUMENTATION_SOURCE_ID_MISMATCH` | The source is not configured, is given twice, or its `id` in the settings does not match the `id` in the source file. **What to do:** Keep one source with the same `id` in both places. |
| `DOCUMENTATION_SOURCE_CONTRACT_INVALID`, `DOCUMENTATION_SOURCE_HASH_MISMATCH` | The source file is damaged or does not match the pinned version. **What to do:** Restore the source file from the same version as in `simai-framework.lock.json`. |
| `DOCUMENTATION_FRAMEWORK_LOCK_INVALID`, `DOCUMENTATION_FRAMEWORK_RULES_INVALID`, `DOCUMENTATION_FRAMEWORK_RULES_MISSING` | The pinned SIMAI Framework version does not suit this Docara version. **What to do:** Update Docara or restore the lock that it ships. |
| `DOCUMENTATION_ENTITY_UNKNOWN`, `DOCUMENTATION_ENTITY_DUPLICATE` | The source has no entity with this key, or the key repeats. **What to do:** Take the key from `docara documentation status --json`. |
| `DOCUMENTATION_ROUTE_INVALID`, `DOCUMENTATION_ROUTE_LOCALE_INVALID` | The page of the entity was not found, several were found, or it is not in the main language of the source. **What to do:** Link the entity to one page in the `source_locale` language. |
| `DOCUMENTATION_EXAMPLE_CASES_MISSING` | The page lacks required examples listed in the source. **What to do:** Add the examples named in the message. |
| `DOCUMENTATION_EXCLUSION_REASON_REQUIRED` | An entity is excluded without a reason. **What to do:** Write the reason for the exclusion. |
| `DOCUMENTATION_REVIEW_INVALID` | An unknown review kind in `--review`. **What to do:** Give `ai_verified` or `human_reviewed`. |
| `DOCUMENTATION_STATUS_FILTER_INVALID` | An unknown status in `--status`. **What to do:** Take a status from [Statuses](/en/authoring/documentation-tracking/#statuses). |
| `DOCUMENTATION_LOCK_INVALID`, `DOCUMENTATION_LOCK_DUPLICATE`, `DOCUMENTATION_LOCK_SOURCE_LOCALE_MISMATCH` | `documentation.lock.json` is damaged, an entity is recorded in it twice, or its language does not match the settings. **What to do:** Do not edit the file by hand: restore it from history and accept the changes with `docara documentation accept`. |
| `DOCUMENTATION_PLAN_ID_INVALID`, `DOCUMENTATION_PLAN_INVALID`, `DOCUMENTATION_PLAN_HASH_MISMATCH`, `DOCUMENTATION_PLAN_COLLISION` | The plan in `.docara/documentation-plans/` does not match its hash, is damaged or already exists. **What to do:** Pass the exact `plan_id` from the `--dry-run` answer or make the plan again. |
| `DOCUMENTATION_PLAN_STALE` | A page or the source changed after `--dry-run`. **What to do:** Run `--dry-run` again and apply the new plan. |
| `DOCUMENTATION_PAGE_INVALID`, `DOCUMENTATION_INPUT_UNSAFE` | A page or source file is a link or lies outside the project. **What to do:** Replace the link with an ordinary file inside the project. |
| `DOCUMENTATION_WRITE_FAILED`, `DOCUMENTATION_WRITE_PATH_UNSAFE`, `DOCUMENTATION_WRITE_CASE_COLLISION` | A file could not be written: no permissions, the path goes through a link, or it differs from an existing one only in letter case. **What to do:** Fix the path or the permissions and try again. |
| `DOCUMENTATION_SOURCE_COMPATIBILITY_ADAPTER` | A warning: the pinned SIMAI Framework version has no description of its own for tracking, and Docara builds one from the Framework rules it ships itself. **What to do:** Nothing; the warning goes away when the pinned Framework version starts shipping its own description. |

## Local MCP

| Code | Cause and what to do |
| --- | --- |
| `MCP_METHOD_NOT_FOUND`, `MCP_TOOL_UNKNOWN` | The client called a method or tool that Docara does not have. **What to do:** Get the list of tools with the `tools/list` method and call only those. |
| `MCP_WRITE_CAPABILITY_REQUIRED` | Applying a plan is forbidden: MCP was started read-only. **What to do:** Review the plan, then restart the local MCP with `--allow-writes`. |

## See also

- [SDK for developers and AI agents](/en/development/developer-sdk/)
- [Commands](/en/reference/cli/)
- [Documentation freshness](/en/authoring/documentation-tracking/)
- [Error codes](/en/reference/errors/)
