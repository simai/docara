# Verification summary

Candidate:
`a29c1ab03462415879ec7383e6cf53e1dcccb1c2`

## Code and repository gates

- YAML parse: PASS for launch, current memory and track memory.
- Pint: PASS for all changed PHP implementation and tests.
- `git diff --check`: PASS.
- Composer validation: PASS.
- Project doctor: PASS with no findings or blockers.
- Local commit action gate: PASS for environment, Git history, pre-commit,
  repository hygiene, secrets and source policy.

The Composer binary supplied by ServBay emits dependency deprecation notices
under PHP 8.4, but `composer.json` is valid. The Homebrew Composer shebang is
not usable because that external PHP installation references a missing ICU 73
library; Composer was therefore executed explicitly through ServBay PHP.

## Tests

- Focused declarative and builder acceptance:
  44 tests, 667 assertions, PASS.
- Full repository suite under an explicit UTC PHP configuration:
  568 tests, 4,625 assertions, PASS.

The first non-UTC full run produced two legacy Jigsaw snapshot differences:
midnight dates were shifted to the previous day by ServBay's
`Etc/GMT+5` CLI setting. The same two tests pass with UTC
(2 tests, 142 assertions), and the complete UTC suite is green. No snapshot was
rewritten.

## Static builds

Two disposable builds were produced from the same candidate-equivalent source
state.

- build digest:
  `ad232d46d29a9b39e67a542be424fe8e845a403d044dd5900a241568c440cdf9`;
- `diff -qr`: PASS;
- HTML pages per build: 66;
- local references checked per build: 6,036;
- broken references: 0.

The resolved page diagnostics contain:

- 57 page records;
- 44 pages rendered through the shadow declarative pipeline;
- 44 semantic parity PASS records;
- 44 Larena adapter parity PASS records;
- 3 real pages exercising `ui.alert`:
  `/`, `/development/`, and `/migration/`;
- 13 generated records outside page-source applicability.

## Candidate identity

After the candidate commit:

- `git rev-parse HEAD` returned
  `a29c1ab03462415879ec7383e6cf53e1dcccb1c2`;
- `git diff --exit-code HEAD -- .` passed;
- focused acceptance was repeated against committed HEAD and passed;
- `git show HEAD:src/PortableSite/PortableHtmlRenderer.php | shasum -a 256`
  returned the accepted legacy hash.

`git archive` is not used as PHPUnit proof because repository export rules
intentionally omit tests. The full and focused suites instead ran against the
candidate-equivalent clean tree, and the post-commit zero-diff check proves
that the committed implementation bytes are the tested bytes.
