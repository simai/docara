# R1-C.6 integrated and browser verification

Status: `implementation_pass_pending_independent_retest`

Candidate source: `56a2abf8bad05923f689141afc0bb045aa4d6734`
Candidate ZIP: `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`

## Runtime and quality

- PHPUnit on macOS/PHP 8.4.20: 390 tests, 6,024 assertions, PASS;
- focused documentation/package gate: 20 tests, 1,452 assertions, PASS;
- Pint and Composer strict validation: PASS;
- PHP lint for tracked PHP, tracked JSON/YAML decode and `git diff --check`:
  PASS;
- fresh-consumer Composer audit: advisories 0, abandoned packages 0;
- two public full builds: 103 routes/305 files, byte-identical;
- full/single HTML parity: 103/103;
- static verifier: 206 HTML, 21,437 references, broken 0;
- multilingual fixture: 40 routes, 78 HTML, 3,790 references, broken 0.

Host Composer 2.7.1 emits PHP 8.4 deprecation notices from its own PHAR; command
exit and validation/audit results are green. PHP 8.3 and a complete Linux matrix
were not run and remain explicit compatibility gaps.

## Exact browser evidence

Fresh screenshots were captured from the corrected package build, not inherited
from R1/M5. The RU public build covers landing, examples and component pages at
1920, 1440 and 390 in light/dark themes. A minimal physical Arabic Markdown
fixture built by the same packaged engine covers RTL at 1440 light and 390 dark;
it proves engine behavior, not full Arabic translation.

All seven cases report page overflow 0 and console errors 0. Keyboard-visible
focus, Esc focus return for settings/search/mobile navigation, temporary copy
feedback, tab selection, tree mouse/Enter behavior, responsive tables and
reduced-motion media are exercised. Machine-readable cases and screenshot
hashes are in `browser-results.json`.

## Independent boundary

The executor has completed the correction candidate matrix. The next action is
a read-only independent rebuild and exact-artifact retest. Until that verdict,
`local_release_readiness` stays pending and no release/production claim is made.
