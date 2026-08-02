# R1-C public locale source boundary

Date: 2026-08-02  
Parent revision: `218ff1fb99f9c4c9f75a53a7716a19cb1ae0376f`  
Rollback: restore the explicit paths from the parent revision; no project-owned
consumer content is deleted by this checkpoint.

## Runtime finding

`PortableSiteBuilder` already constructed `Translator` with
`ContentLanguageRepository` and no package language-pack repository. Production
searches found no caller of `LanguagePackRepository` or component prose lookup;
the remaining consumers were legacy tests and the finite migration allowlist.

The checkpoint therefore removes:

- `src/I18n/LanguagePack.php`;
- `src/I18n/LanguagePackRepository.php`;
- `resources/schemas/language-pack.schema.json`;
- `resources/language-packs/{ar,en,fr-CA,ru,zh-Hans}.json`;
- locale `language_pack` fields from schema, starter, runtime model and loader;
- language-pack prose maxima from the legacy allowlist.

Historical blob IDs are preserved by Git at the parent revision:
`4c09b19f`, `8585fbd`, `9ab7b86`, `4470159`, `0bfd01e`, `5940895`,
`27a3264`, `ababe42`.

## Positive contract

- one page owner: `content/<locale>/<route>.md`;
- shared public UI copy: `content/<locale>/lang.json`;
- `Translator` resolves only `ContentLanguageRepository` plus explicit locale
  fallback;
- `examples` is removed from allowed `lang.json` namespaces, matching the
  accepted authoring contract;
- a corrupt synthetic `resources/language-packs/ru.json` is ignored by the
  public builder, while a public `language_pack` config field fails schema
  validation.

## Verification

- focused locale/catalog/build matrix: `30 tests, 1578 assertions` — PASS;
- full PHPUnit on PHP 8.4.20: `378 tests, 5873 assertions` — PASS;
- Pint: PASS;
- Composer strict validation: PASS (environment-owned PHP 8.4 deprecation
  notices only);
- repository JSON parse: `502` files — PASS;
- `git diff --check`: PASS;
- real documentation build exercised by the suite: `103` pages — PASS.

Negative guard strings naming the retired path remain intentionally in
`SourceBoundaryValidator` and negative tests. Historical evidence under
`source/workflow/evidence` is immutable and is not counted as an active
production/package reference.
