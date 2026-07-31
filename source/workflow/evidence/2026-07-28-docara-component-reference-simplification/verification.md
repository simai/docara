# Docara component reference simplification verification

Date: 2026-07-28
Workflow: `2026-07-28-docara-component-reference-simplification`
Result: `PASS`

## Product result

- `/ru/components/` contains one semantic table with 28 supported components;
- all generated details use `/ru/components/<id>/` with no public `catalog`
  level;
- navigation links directly to every supported component page;
- unsupported entries remain internal planning records and produce no public
  page, menu item or unavailable-example message;
- detail pages show a live result, copy-ready call, parameters, minimal
  parameter examples, variants, visible considerations, source and provenance;
- the Alert page proves the full pattern with two parameters and eight minimal
  parameter calls.

## Automated verification

```text
PHPUnit: OK (333 tests, 6431 assertions)
Focused correction suite: OK (17 tests, 1434 assertions)
Changed PHP files: Pint PASS
git diff --check: PASS
```

The repository-wide Pint command still reports formatting in unrelated files
already modified before this bounded workflow. No unrelated file was rewritten;
every PHP file changed by this workflow passes Pint.

## Build verification

Two clean production builds were compared byte for byte and were identical.

```json
{
  "schema": "docara.static_build_verification.v1",
  "deployment_base": "/",
  "html_pages": 220,
  "local_references_checked": 18445,
  "broken": []
}
```

The same verification result was obtained from the repository build and from
the build installed in `/Users/rim/Sites/docara.test/build_production`.

## Browser verification

Index at `https://docara.test/ru/components/`:

- 1 table and 28 body rows;
- 0 search/filter controls;
- 0 links to `/components/catalog/`;
- 0 unavailable-example messages;
- no page-level horizontal overflow at desktop width or `390x844`.

Detail at `https://docara.test/ru/components/docara.alert/`:

- visible sections: Example, Call, Parameters, Parameter examples, Variants,
  Considerations, Source and Component metadata;
- 2 parameter rows and 8 parameter-specific minimal calls;
- no collapsed legacy limitations/source disclosure;
- no unavailable-example message or page-level horizontal overflow.

Theme verification used the visible reading-settings UI:

- light: white surface with dark text, no overflow;
- dark: dark surface with light text, no overflow;
- the prior site preference was restored after the check.

## Local deployment and rollback

- local target: `/Users/rim/Sites/docara.test/build_production`;
- rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/component-reference-20260728-192718/build_production.previous`;
- action-gate evidence:
  `/Users/rim/Sites/docara.test/source/output/action-gates/action-gate-report-20260728162638.json`.

No merge, tag, package publication, public deployment or production-readiness
claim was made.
