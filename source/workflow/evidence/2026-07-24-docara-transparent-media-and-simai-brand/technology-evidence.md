# Technology conformance evidence

## Product contract

- Owner: Docara portable PHP generator.
- Content: Markdown plus strict inherited JSON configuration.
- Rendering: registered Docara components and SIMAI Framework presentation
  primitives.
- Media: author-owned PNG assets with a real alpha channel; surface colors
  remain owned by the Framework theme.
- Delivery: deterministic static `build_production`, verified before reversible
  local publication.

## Evidence

- Hero and Promo use `object-fit: contain`;
- rectangular product screenshots keep `object-fit: cover`;
- four accepted PNG assets report `hasAlpha: yes`;
- active product surfaces use `SIMAI Framework`;
- PHPUnit: 320 tests, 4,962 assertions, PASS;
- Pint and `git diff --check`: PASS;
- `verify-static`: 198 HTML files, 10,908 local references, zero broken;
- source and served trees are identical;
- desktop light/dark and mobile light browser inspection: PASS;
- browser console errors and warnings: zero;
- rollback copy retained at
  `/Users/rim/Sites/docara.test/.docara-backups/transparent-media-20260724-013133`.

Verdict: **conformant for transparent landing media, canonical SIMAI spelling
and the local test site**.

No commit, push, merge, tag, package publication or public/production release
claim is made.
