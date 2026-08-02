# R1-C.4 semantic documentation gates

Status: `pass`

Parent revision: `43fcba1ed4d107c83c2e1687506af7784864d718`

Candidate revision: recorded by the checkpoint commit containing this evidence.

## Outcome

Documentation validity is now a semantic product gate rather than a structural
file-count check. One shared `MarkdownLocalLinkVerifier` checks repository
documentation and the exact contents extracted from a release ZIP. The release
verifier therefore rejects the R1 failure mode where README points to omitted
files.

`DocumentationContractTest` also fails closed if the retired public
`language_pack`, `languages/<locale>.json`, pack schema/data/runtime, prose
namespaces or forbidden `examples` namespace return. The positive contract is
physical Markdown plus `content/<locale>/lang.json` with the exact UI namespace
set from `lang.schema.json`.

## Focused checks

```text
vendor/bin/phpunit --filter 'DocumentationContractTest|MarkdownLocalLinkVerifierTest|ReleasePackageTest'
OK (20 tests, 1450 assertions)

vendor/bin/pint --test
PASS
```

The artifact-level negative fixture creates a deterministic ZIP whose README
links to a missing packaged document. `scripts/verify-release-package.php`
returns exit code 1 and the resolved broken target. The positive fixture with
the same surface and an included target returns exit code 0.

## Full checkpoint verification

Environment: macOS, PHP 8.4.20, PHPUnit 11.5.56.

```text
vendor/bin/phpunit --colors=never
OK (390 tests, 6024 assertions)

vendor/bin/pint --test
PASS

composer validate --strict
PASS (Composer emitted host PHP 8.4 deprecation notices only)

PHP lint: PASS for src/, scripts/ and tests/
tracked/project JSON decode: PASS
git diff --check: PASS
```

## Boundaries

- Public route links inside `docs/site/content/**` remain the responsibility of
  the built-site static verifier; they are not filesystem-relative source links.
- Root README and packaged non-site Markdown links are verified against the ZIP
  inventory itself.
- This checkpoint creates no release candidate and does not claim local release
  readiness. R1-C.5 must exercise the verifier against the new exact artifact.

Rollback: revert the R1-C.4 checkpoint commit. No product content, URL or
project-owned consumer file is changed by this batch.
