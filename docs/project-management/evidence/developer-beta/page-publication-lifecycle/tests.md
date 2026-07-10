# Verification

- PHP lint: 32 files passed.
- PHPStan: passed with no errors.
- Focused lifecycle plus authoring suites: 9 tests, 138 assertions passed.
- Full feature suite: 20 tests, 193 assertions passed on file-backed SQLite.
- Scope checker: passed for the bounded implementation file set.
- Disposable browser lifecycle: login, create draft, protected preview,
  anonymous draft 404, publish, anonymous public 200, live edit while remaining
  published, unpublish, anonymous public 404.
- Final SQLite state: one draft Page, `published_at=null`, version 4, events in
  order `created`, `published`, `updated`, `unpublished`.
- Temporary server, tab, target and sibling package workspace were removed.
