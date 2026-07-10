# Browser smoke

Target: disposable file-backed SQLite application at `127.0.0.1`, removed
after verification.

1. Started the local/testing demo-login mode with an explicit actor field.
2. Logged in as allowed `user:admin.local` and created `secured-browser-page`.
3. Kept its edit form open and entered forbidden replacement title/body.
4. In a second tab changed the shared session to `user:forbidden`.
5. Submitted the open edit form and observed `403 Forbidden`.
6. Logged back in as allowed admin and opened Audit history.
7. Observed newest-first `Permission denied`, Page slug, forbidden actor,
   denied status and timestamp.
8. Queried SQLite: original Page fields/version remained unchanged, one denied
   event existed and neither forbidden input marker existed in audit payload.
9. Closed both tabs, stopped the server and removed the database.

Result: `PASS`.
