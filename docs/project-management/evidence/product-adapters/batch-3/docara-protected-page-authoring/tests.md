# Tests

Status: passed on PHP 8.3.31.

- package quality gate passed;
- linted 27 PHP files;
- PHPStan reported no errors;
- 2 contract scripts and 9 PHPUnit feature tests passed;
- 50 assertions cover durable persistence plus HTTP authoring;
- unauthenticated write returned 401;
- forbidden actor returned 403 and the page remained unchanged;
- persistent admin actor created, edited and published a page;
- three ordered audit events persisted;
- page and audit rows survived application refresh/reconnect;
- audit payload excluded body content;
- no `larena-docara-*` temporary database remained.
