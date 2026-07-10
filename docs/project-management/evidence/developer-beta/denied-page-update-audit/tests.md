# Verification

- Focused denial feature: 1 test, 22 assertions passed.
- Full Docara suite: 21 tests, 217 assertions passed on PHP 8.4.20.
- PHP lint: 34 files passed.
- PHPStan level 5: no errors.
- Launch and scope checks passed for the bounded batch.
- Browser flow on disposable SQLite passed: allowed actor created a Page,
  forbidden actor submitted the already-open edit form, received 403, and the
  administrator then saw `Permission denied` in Audit history.
- Persisted Page remained `Secured browser page | ORIGINAL BODY MUST REMAIN |
  draft | v1`.
- Audit sequence contained create plus exactly one denied update; forbidden
  title/body markers appeared in neither Page storage nor audit payload.
- Existing `larena.test` MySQL was not mutated.
