# docara-new.test deployment result

## Outcome

`https://docara-new.test` now serves the independently accepted Goal C
product/runtime candidate
`eb35f5c6f18e5eb9be69e91887b09486f5703136`.

The build was produced twice from a detached exact-candidate worktree using
ServBay PHP 8.4.20. Both outputs were byte-identical at complete-tree SHA-256
`44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4`.

## Verification

- full builds: 2/2 identical;
- output: 391 files / 264 HTML / 132 routes;
- static verification: 35,044 local references, `broken=[]`;
- atomic cutover helper preflight: PASS;
- atomic cutover: PASS;
- active and backup inverse preflight after cutover: PASS;
- HTTPS metadata-route smoke: 132/132 returned 200;
- HTTP root: 301 to `https://docara-new.test/`;
- actual versioned CSS/JS and `_docara` indexes: 200;
- desktop 1440 px: no horizontal overflow; search/settings Escape and focus
  return PASS;
- mobile 390 px settings page: no horizontal overflow, console errors=0,
  console warnings=0;
- browser requests: no failed/4xx/5xx requests in the representative mobile
  request ledger.

The home page emitted two log-level Highlight.js fallback notices for the
language label `shell`; Playwright classified them as neither errors nor
warnings. Rendering continued in the documented no-highlight fallback mode.

## Commands

Build and static verification were executed from the detached worktree at the
exact product commit:

```text
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build production
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara verify-static <build-root>
```

Publication used the repository-owned helper with exact old/new digests:

```text
/Applications/ServBay/package/php/8.4/8.4.20/bin/php scripts/atomic-static-cutover.php cutover --parent=/Users/rim/Sites --active=docara-new.test --candidate=.docara-new.test-candidate-goalc-eb35f5c6-20260806 --backup=.docara-new.test-backup-before-goalc-eb35f5c6-20260806 --expected-active-sha256=5dc4112d3a0424ca74fca1b73a27392532cfdff84011369a5c3b38984fc32e9d --expected-candidate-sha256=44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4
```

## Rollback

The immediate previous tree is retained unchanged at
`/Users/rim/Sites/.docara-new.test-backup-before-goalc-eb35f5c6-20260806`:
307 files / 208 HTML, SHA-256
`5dc4112d3a0424ca74fca1b73a27392532cfdff84011369a5c3b38984fc32e9d`.

Exact rollback command:

```text
/Applications/ServBay/package/php/8.4/8.4.20/bin/php scripts/atomic-static-cutover.php rollback --parent=/Users/rim/Sites --active=docara-new.test --candidate=.docara-new.test-candidate-goalc-eb35f5c6-20260806 --backup=.docara-new.test-backup-before-goalc-eb35f5c6-20260806 --expected-active-sha256=5dc4112d3a0424ca74fca1b73a27392532cfdff84011369a5c3b38984fc32e9d --expected-candidate-sha256=44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4
```

No Caddy reload is required because the configured root path remains
`/Users/rim/Sites/docara-new.test`.

## Untouched surfaces and nonclaims

- `docara.test` was not changed;
- Caddy configuration and service state were not changed;
- existing historical backups were not removed;
- no external repository or site was changed;
- no merge, push, tag, public release or production deployment was performed.
