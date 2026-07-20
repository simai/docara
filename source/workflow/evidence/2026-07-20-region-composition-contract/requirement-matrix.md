# Requirement matrix

Date: 2026-07-20
Verdict: PASS

| Requirement | Evidence | Result |
| --- | --- | --- |
| Site/section/page accept `layout.regions` | schema and configuration tests | PASS |
| Configuration inherits deterministically | site -> section -> page provenance test | PASS |
| Reset preserves a complete structural layout | layout reset regression | PASS |
| Optional regions can be disabled | compiler and renderer tests | PASS |
| Disabled regions are absent from HTML | unit, static and browser checks | PASS |
| Required region cannot be disabled | negative `main` test | PASS |
| Sections/blocks/Smart IDs are bounded | strict schema enums and definition repository | PASS |
| Dynamic data uses fixed safe bindings | resolver negative matrix | PASS |
| No arbitrary template/class/callback surface | schema, resolver and trusted-template boundary | PASS |
| Compiler consumes resolved configuration | builder passes layout and provenance into pipeline | PASS |
| Resolved plan explains source | layout provenance and diagnostics | PASS |
| Larena adapter preserves region state | adapter fixture and semantic parity | PASS |
| User/developer documentation is published | `/authoring/regions/` and architecture/reference updates | PASS |
| A visible demonstration exists | declarative preview route | PASS |
| Legacy renderer remains unchanged | exact SHA-256 | PASS |
| Full regression passes | 580 tests, 4,804 assertions | PASS |
| Build is deterministic and statically valid | two builds, 115 HTML, 11,256 refs, zero broken | PASS |
| Local deployment is reversible | staging, backup, previous-active and rollback path | PASS |
| Desktop/mobile browser acceptance passes | exact DOM/viewport checks | PASS |

## Nonclaims

- The declarative renderer has not replaced the accepted primary publisher.
- This goal does not declare full visual parity or product/release readiness.
- Only registered `docara.docs` and the current bounded shell Smart set are
  author-callable through region JSON.
- A new footer component or another layout remains a separate owner-reviewed
  extension.
