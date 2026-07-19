# Batch 3 — independent exact tester verdict

Candidate: `73eae43b9e8f715c0dc978390f4e60a1011465c9`
Candidate tree: `0f1aa5544dabf9631550a349caa9641f04384bd3`
Verdict: `PASS`

## Two exact surfaces

The independent tester kept the distribution and QA boundaries separate:

1. the standard `git archive` is the exact distribution/build surface;
2. a detached full Git tree at the same SHA is the exact PHPUnit surface,
   because committed `/tests export-ignore` correctly keeps the internal test
   corpus out of the user distribution archive.

No test or source file was copied from the live worktree. The detached clone
matched candidate HEAD and tree, had a clean status and an exact `git ls-tree`
digest.

## Evidence

- distribution archive SHA-256:
  `22e2114774490e8955fe5e687bf731b73efcff50d45c77dd90a356f3d5d0ec86`;
- a second archive was byte-identical and `git get-tar-commit-id` returned the
  exact candidate SHA;
- full-tree `git ls-tree -r` digest:
  `aeab46799a9679904cc45626a3f3bb5f0f27af05da153c4b93a1eb9b9cfda93e`;
- Composer strict validation and platform requirements: PASS;
- Pint: PASS;
- PHP syntax: `206` files, PASS;
- JSON parsing: `40` files, PASS;
- JavaScript syntax: `13` files, PASS;
- PHPUnit: `464 tests`, `2308 assertions`, zero errors/failures/skips;
- JUnit SHA-256:
  `2f1c5e4e44beeee9d36cd6f68038e40fae8dd08cbf5b5bbb9756732424e9342c`;
- two clean production builds: each `43` HTML pages, `4339` local
  references, zero broken and `50` files;
- both production tree digests:
  `826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b`;
- `diff -qr` between the builds: no differences;
- `git diff --check f482ced..73eae43`: PASS;
- Framework lock mirrors and six fixed 40-hex revisions: PASS;
- three published Framework asset hashes match the lock.

Compatible dependency bytes were reused only after byte identity checks for
`composer.json`, the vendor tree, `installed.json` and `installed.php`.

One preliminary sandbox run is excluded: only three external
JSONPlaceholder DNS failures occurred. The authoritative exact-tree run with
the endpoint available exited zero and produced the JUnit evidence above.

This verdict accepts only Batch 3 implementation. It does not claim public
release, production readiness, Batch 4 or completion of the wider Goal.
