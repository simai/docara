# Acceptance verdict

Date: 2026-07-21
Candidate: the commit containing this evidence, based on
`bd9d42a87e41c2914c9ab7bcb8ff2fdf3b3d3f3f`
Verdict: PASS

Reverse-outcome audit confirms the requested outcome rather than only the
implementation activity:

1. A project may declare any finite set of well-formed BCP 47 locales without
   changing PHP, JavaScript, templates or technical component records.
2. Product/component copy is external, fallback is explicit and cyclic or
   incomplete resolution fails closed.
3. One build publishes every locale with isolated content, routes, navigation
   and search, plus exact `lang`, `dir`, switcher and alternate links.
4. `ru`, `en`, `ar`, `zh-Hans` and `fr-CA` are proven simultaneously; Arabic
   is RTL and project copy may fall back to English without a named-language
   code branch.
5. Existing Russian documentation remains buildable and is published to the
   local stand with a verified rollback copy.
6. Full tests, syntax, schema, static references, duplicate builds and
   responsive browser checks pass.

Accepted scope: new declarative Docara and local `docara.test` publication.
Excluded and not claimed: legacy Jigsaw Docara, public push/release,
production deployment and general production readiness.
