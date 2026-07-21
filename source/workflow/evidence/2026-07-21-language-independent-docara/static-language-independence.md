# Static language-independence audit

Date: 2026-07-21
Verdict: PASS

The new declarative runtime, schemas and technical catalogue were scanned for:

- comparisons of runtime locale values with named languages;
- required `ru` or `en` object keys;
- `presentation.ru`, `presentation.en` and equivalent indexed access;
- named-language values in technical schemas and manifests.

No match was found in the new engine. Named locales occur only in language
packs, localized documentation/fixtures and tests that prove those examples.

Explicit boundaries:

- `src/PortableSite/PortableHtmlRenderer.php` is the immutable, opt-in legacy
  rollback renderer and is not used by the new declarative publisher;
- older pre-declarative repository classes and upstream Framework asset
  manifests are not part of this language-pack contract;
- the new product code never infers language identity or direction from a
  locale name.

PHP syntax checks and `git diff --check` pass.
