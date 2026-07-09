# Test evidence

Environment:

- declared Composer platform PHP 8.3.31
- verified on PHP 8.3.31 and ServBay PHP 8.4.20
- Laravel framework 13.19.0
- Orchestra Testbench 11.1.0
- PHPUnit 12.5.31
- isolated temporary file-backed SQLite database per test

Command: `composer quality:gate`

Result: passed.

- package validator: passed
- PHP syntax lint: 23 files passed
- PHPStan level 5: no errors
- legacy contract runner: 2 files passed
- PHPUnit feature suite: 6 tests, 23 assertions, passed
- evidence contract: passed
- scope checker: passed
- PHP 8.3 compatibility gate: passed after resolving dependencies against the declared platform

Feature coverage:

1. draft data survives a complete Testbench application refresh and is read through a new repository instance;
2. a draft is absent from published lookup and a published page is returned;
3. `(locale, slug)` uniqueness is enforced by the database;
4. `sqlite::memory:` is rejected as durability evidence;
5. migration rollback removes `docara_pages`.
6. draft and published rows both survive a complete application reboot while
   the public lookup returns only the published row.
