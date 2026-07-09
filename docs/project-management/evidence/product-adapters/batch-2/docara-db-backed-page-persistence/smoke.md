# Local persistence smoke

The automated feature suite is the executable smoke for this package-only batch.

Observed sequence:

1. allocate a unique SQLite file in the operating-system temporary directory;
2. boot a Testbench Laravel application and run package migrations;
3. save a draft page with `title`, `slug`, `body`, locale, visibility, and publication status;
4. discard and rebuild the application container while retaining the database file;
5. read the same values through a new repository instance;
6. publish the page and verify the published-only lookup;
7. run migration rollback and verify the package table is absent;
8. close application connections and delete the temporary database file in teardown.

No existing application, staging, or production database was opened or modified.
