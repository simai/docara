# C2 deterministic page metadata

Candidate parent: `d7943ed`

## Contract

- Git history for the exact page source remains an allowed immutable source for
  `updated_at`, `revision` and `author`.
- When that history is unavailable, all three fields are `null`.
- Filesystem `filemtime` is never used as public page metadata.
- `_docara/page-metadata.json` remains inside the public tree and every digest;
  no verifier or comparison scope was weakened.

## Regression proof

`PortableDocumentationSiteTest` copies the real 103-route documentation site
into two independent non-Git roots, assigns their Markdown files two distinct
mtime epochs, builds both sites and compares the SHA-256 map for all 305 output
files. It explicitly asserts the presence of page metadata and null audit
fields for all 103 pages.

```text
PHPUnit focused: 2 tests, 1,503 assertions, PASS
Pint touched files: PASS
PHP lint touched files: PASS
git diff --check: PASS
```

This is source-level regression evidence. C5 separately requires the stronger
artifact-level proof from two fresh Composer dist consumers at different
extraction times.
