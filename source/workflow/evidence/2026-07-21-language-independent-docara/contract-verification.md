# Contract verification

Date: 2026-07-21
Verdict: PASS

- `LocaleTag` validates and canonicalizes well-formed BCP 47 tags, including
  language, script, region, variants, extensions and private-use subtags.
- `LocaleRegistry` accepts an arbitrary non-empty locale map and validates
  unique content roots/public prefixes, explicit direction and acyclic
  fallback chains.
- UI and component copy is resolved through semantic message IDs from external
  language packs. Technical catalogue records contain IDs, states, parameters
  and renderer contracts only.
- Resolved localized component presentations are checked against the exact
  technical state/parameter/enum shape before publication.
- Translated values cannot choose templates, callbacks, scripts or renderer
  IDs.

Focused contract and catalogue matrix: 68 tests, 815 assertions, PASS.
Full regression: 602 tests, 5022 assertions, PASS in 4 minutes 37 seconds.

