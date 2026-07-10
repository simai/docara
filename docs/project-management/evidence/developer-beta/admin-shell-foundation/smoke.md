# Smoke

The isolated Testbench runtime proves:

- anonymous Page-list access still redirects to login;
- an administrator sees the Page list inside the Admin-owned shell;
- an administrator sees the create form inside the same shell;
- the shared stylesheet URL and non-production label are rendered;
- no existing application database is used.

Root entry-app browser rendering remains the integration gate.
