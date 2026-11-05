---
description: "Build security: which paths, files and data Docara rejects, why the build stops and where to find the error codes."
---

# Security

A Docara build follows the rule "stop when in doubt" (fail closed). It does
not try to guess the author's intent if the JSON is invalid, a component has
an unknown parameter or a path leads somewhere unsafe.

If you start from a visible symptom, open
[Troubleshooting](/en/troubleshooting/). This page describes the exact
boundaries and error codes.

## Main boundaries

- All paths in the settings are relative and do not leave the site root.
- A symbolic link in place of the root, the content folder, the lock file or
  the result folder is rejected.
- Raw HTML is removed; unsafe links are forbidden.
- The names `_docara` and `.docara` are reserved.
- Two pages at one address are detected before the previous build is
  cleared.
- Redirects accept only internal `source` and `target` addresses without
  query parameters and an anchor. Duplicates, a redirect to itself, chains,
  cycles and a missing target are rejected before the previous build is
  cleared.
- Every page lies in the `content_root` of exactly one declared language, and
  the language tag of the page is in the `locales` list.
- Simple component parameter values are escaped when written to HTML.
- The search index is checked against its schema before the result is
  cleared. Search in the browser accepts only addresses of the same site
  inside `base_url` and shows results as text, not as HTML.
- `navigation.hidden` and `search.indexed: false` do not restrict access to a
  page: its HTML and search JSON stay public. Do not put secret data in the
  source files or in the built site.
- Anchor links are checked together with files. A repeated HTML `id`, a
  missing anchor and an invalid percent-encoding fail the build check.

## What is forbidden in settings and the project

Docara accepts identifiers and data, but not executable project handlers. The
build stops if it meets:

- leaving the root through `..`, an absolute path or including a file outside
  the allowed folders;
- leaving through a symbolic or hard link, and names that differ only in
  letter case;
- a class name, a callback function, a PHP template or a filesystem path
  inside Markdown or settings;
- raw HTML or CSS, and external embedding outside an explicit list of allowed
  sources or an isolated frame;
- two owners of one namespace or file, and an implicit replacement of a
  package component;
- `scaffold`, `qa` or `preview` commands writing into package files, the lock
  file, build output or external folders;
- a stale change plan or a plan whose hash does not match.

An error does not change a single byte outside the checked project root.
Error messages show the relative path, a pointer to the field and the place
in the file, but not secrets and not the full paths of your machine.

## What the documentation does not promise

The documentation describes a checked local process. It does not claim
support for every SIMAI Framework component, fully offline work or
intelligent search.

## Error codes

Error codes with the cause and the fix are in the
[error code reference](/en/reference/errors/):

- [pages and Markdown](/en/reference/errors/content/) — front matter, blocks,
  components, links and images;
- [site settings](/en/reference/errors/site/) — redirects, languages,
  `lang.json`, search, effects, the 404 page;
- [updating Docara](/en/reference/errors/upgrade/);
- [build check](/en/reference/errors/verify-static/) — the `verify-static`
  marks, for example `@missing-fragment` and `@duplicate-html-id`.

An exact error code is more useful than a general "the build does not work":
first fix the source file, then build again and
[check the result](/en/build/verify/). Do not fix a `build_*` folder by hand.
