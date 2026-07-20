# Verification summary

- Focused portable contract:
  `31 tests, 577 assertions` — pass.
- Affected regression set after formatting:
  `62 tests, 1007 assertions` — pass.
- Complete PHP 8.2 suite:
  `588 tests, 4841 assertions` — pass in `03:23.597`.
- Pint:
  `php vendor/bin/pint --test` — pass.
- Static build:
  `115` HTML pages, `11,320` local references, `0` broken.
- Deterministic source-build digest, two consecutive builds:
  `ceee0d606da97fd7aada11364256c4ddca71f378f9bbbc8f8e0235f4ebf23dec`.
- Normalized exact live tree digest:
  `eb908c9b0ad139190aa8c239fc37dc063bc2ba47a2ca59bc7cdc165b8d809c42`.
- `git diff --check` — pass.
- Legacy renderer SHA-256 remained
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
