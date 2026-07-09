# Smoke

The executable Testbench HTTP scenario exercised the real Docara routes and the
Auth and Access middleware aliases from their package providers. It confirmed
401 without a session, 403 for `user:forbidden`, and create → edit → publish for
`user:admin_identity:1`. The application was refreshed before re-reading the
published page and audit rows from the file-backed SQLite database.

This is package-level local/testing acceptance. The entry-app still must prove
the real login form, session cookie and composed browser flow.
