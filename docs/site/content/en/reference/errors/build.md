---
description: Build error codes — the project root and paths, settings layers, rebuilding one page, files and addresses in the result, writing and replacing the build folder — with the cause and the fix.
tags: [error, build, page, path, build-folder]
---

# Build errors

These errors happen when Docara reads the project and writes the result: the
`build` and `serve` commands and the one-page rebuild `build --page`. Errors
in page text are described separately, in
[Page and Markdown errors](/en/reference/errors/content/).

## Project root and paths

The project root is the folder with `docara.json`. All paths in the settings
are written relative to it.

| Code | Cause and what to do |
| --- | --- |
| `ROOT_NOT_FOUND`, `PORTABLE_ROOT_INVALID` | The project folder does not exist or cannot be read. **What to do:** Run the command from the folder with `docara.json` or give the path to it. |
| `ROOT_PATH_INVALID` | The path to the project is empty or contains `..`. **What to do:** Give the path to the project folder without steps up a level. |
| `ABSOLUTE_PATH_FORBIDDEN` | The settings give an absolute path, for example `/Users/…/content`. **What to do:** Write the path relative to the project root: `content/en`. |
| `RELATIVE_PATH_INVALID` | A path in the settings is empty or contains a backslash `\`. **What to do:** Write the path with `/`: `content/en`. |
| `PORTABLE_FILE_READ_FAILED` | A page file cannot be read. **What to do:** Check that the file exists and is readable. |

## Settings layers

Settings are combined from `docara.json`, `section.json` and
`<page>.page.json`. How a branch reset works is described in
[Site, section and page](/en/settings/levels-and-inheritance/#inheritance-order).

| Code | Cause and what to do |
| --- | --- |
| `CONFIGURATION_ROOT_INVALID` | A settings file is not a JSON object, for example an array or a string. **What to do:** Start the file with `{` and end it with `}`. |
| `RESET_DIRECTIVE_INVALID` | `$reset` is not given as `true`, or there are other keys beside `$value`. **What to do:** Write `{"$reset": true}` or `{"$reset": true, "$value": {…}}` without other keys. |

## Rebuilding one page

`docara build --page /en/section/page/` rebuilds one page on top of a
finished full build. If the full build is out of date, or something changed
that affects other pages, the command stops so as not to leave the site half
old.

| Code | Cause and what to do |
| --- | --- |
| `PORTABLE_PAGE_SELECTOR_INVALID` | The page address is not written as a public address. **What to do:** Give the address with `/` at the start and end: `/ru/components/badge/`. |
| `PORTABLE_PAGE_NOT_FOUND` | The last full build has no page with this address; for example, the page was just added or renamed. **What to do:** Run a full build. |
| `PORTABLE_INCREMENTAL_BUILD_PURPOSE_CHANGED` | The finished build was made for another environment. **What to do:** Run a full build for the environment you need, then rebuild pages. |
| `PORTABLE_INCREMENTAL_ENGINE_CHANGED` | Docara or its dependencies were updated after the full build. **What to do:** Run a full build. |
| `PORTABLE_INCREMENTAL_THEME_CHANGED` | The site theme changed after the full build. **What to do:** Run a full build. |
| `PORTABLE_INCREMENTAL_EXAMPLE_CHANGED` | A shared example from the `examples/` folder changed, and it may stand on other pages too. **What to do:** Run a full build. |
| `PORTABLE_INCREMENTAL_DIAGNOSTICS_INCOMPLETE`, `PORTABLE_INCREMENTAL_DIAGNOSTICS_INVALID` | The finished build lacks full details about the pages, or a page has changed its address since. **What to do:** Run a full build. |
| `PORTABLE_INCREMENTAL_ATLAS_BASE_INVALID`, `PORTABLE_INCREMENTAL_BACKLINK_BASE_INVALID`, `PORTABLE_INCREMENTAL_COMPONENT_INDEX_BASE_INVALID`, `PORTABLE_INCREMENTAL_EXAMPLE_BASE_INVALID`, `PORTABLE_INCREMENTAL_FRAMEWORK_ASSET_BASE_INVALID`, `PORTABLE_INCREMENTAL_SCHEMA_REFERENCE_BASE_INVALID`, `PORTABLE_INCREMENTAL_SEARCH_BASE_INVALID` | The shared site data in the finished build is missing or damaged: the component catalogue, backlinks, search, examples. Usually this is a build from an old version or an interrupted build. **What to do:** Run a full build. |
| `PORTABLE_INCREMENTAL_BASE_COPY_FAILED` | The finished build could not be copied into a temporary folder. **What to do:** Check the disk space and the permissions on the build folder, then try again. |

## Files and addresses in the result

| Code | Cause and what to do |
| --- | --- |
| `PORTABLE_ASSET_OUTPUT_COLLISION` | A file from the content lands at the same address as a Docara service file, for example in `_docara/` or in place of a page. **What to do:** Rename the file or move it into `assets/`. |
| `PORTABLE_ASSET_SOURCE_COLLISION` | A file from the root `assets/` folder that a page refers to, and another file from a language folder, get one address but differ in content. **What to do:** Rename one of the files. |
| `PORTABLE_LOCAL_ASSET_RECEIPT_UNSAFE` | A file from `/assets/` that a page refers to does not exist, or is a symbolic or hard link. **What to do:** Put an ordinary file into `assets/` and rebuild the site. |
| `PORTABLE_BUILD_LOCALE_MISMATCH` | A page lies in the folder of one language, but the build runs for another. **What to do:** Check the `content_root` of the languages in `docara.json` and put the page into the folder of its language. |

## Writing and replacing the build folder

Docara builds the site into a temporary folder and replaces the old result
only when the new build is ready. If the replacement fails, the previous
build is put back.

| Code | Cause and what to do |
| --- | --- |
| `DESTINATION_OUTSIDE_SITE_FORBIDDEN` | The build folder lies outside the project or is not named `build` or `build_*`. **What to do:** Build into `build_<environment>` inside the project. |
| `DESTINATION_SYMLINK_FORBIDDEN` | The build folder is a symbolic link. **What to do:** Replace the link with an ordinary folder; to publish elsewhere, copy the finished build. |
| `DESTINATION_INPUT_OVERLAP_FORBIDDEN` | The build folder matches the source folders or is nested in them, for example in `content/`. **What to do:** Choose a separate `build_<environment>` folder. |
| `DESTINATION_ROLLBACK_SYMLINK_FORBIDDEN` | The build folder or the receipts folder is a symbolic link. **What to do:** Replace the link with an ordinary folder; to publish elsewhere, copy the finished build. |
| `DESTINATION_ROLLBACK_PREPARE_FAILED`, `DESTINATION_PROMOTION_FAILED` | The new build could not be put in place of the old one; the old build was restored. **What to do:** Check the folder permissions and close programs that hold its files, then try again. |
| `DESTINATION_PROMOTION_AND_RESTORE_FAILED` | The new build could not be put in place, and the old one could not be returned either. **What to do:** Run a full build again; do not publish the build folder until then. |
| `DESTINATION_ROLLBACK_CLEANUP_FAILED` | The build is ready, but the temporary copy of the previous build could not be deleted. **What to do:** Delete the folder `<build folder>.docara-rollback` or `<build folder>.docara.rollback` beside the build folder by hand. |
| `DESTINATION_CANDIDATE_INVALID` | The temporary folder of the new build disappeared or has no receipts. **What to do:** Build again; do not run two builds of one project at the same time. |
| `BUILD_PURPOSE_UNSUPPORTED` | `verify-static` checks a build that was not made for publication, for example by the `serve` command. **What to do:** Build the site with `docara build production` and check that build. |
| `PORTABLE_DIAGNOSTIC_WRITE_FAILED`, `PORTABLE_PERFORMANCE_RECEIPT_WRITE_FAILED` | A service report of the build could not be written. **What to do:** Check the disk space and the write permissions, then try again. |
| `PORTABLE_PERFORMANCE_HTML_INVALID`, `PORTABLE_PERFORMANCE_PAGE_INVALID`, `PORTABLE_PERFORMANCE_PAGE_UNSAFE`, `PORTABLE_PERFORMANCE_RESOURCE_OUTSIDE_BASE`, `PORTABLE_PERFORMANCE_RESOURCE_TRAVERSAL`, `PORTABLE_PERFORMANCE_RESOURCE_UNSAFE`, `PORTABLE_PERFORMANCE_RESOURCE_URL_INVALID`, `PORTABLE_VENDOR_SELECTION_INPUT_INVALID` | The page weight report could not parse a finished page or a file it refers to: the file disappeared, leads outside `base_url` or became a link. **What to do:** Check `base_url` in `docara.json` and the file links in the project templates, then run a full build. |

## See also

- [Error codes](/en/reference/errors/)
- [Commands](/en/reference/cli/)
- [Site, section and page](/en/settings/levels-and-inheritance/)
