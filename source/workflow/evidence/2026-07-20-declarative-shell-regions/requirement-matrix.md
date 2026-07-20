# Requirement matrix

| Requirement | Evidence | Verdict |
| --- | --- | --- |
| Immutable shell context after topology resolution | `PageCompositionContext`; builder integration | PASS |
| Header, sidebar and outline region plans | compiler and portable diagnostics assertions | PASS |
| Larena Smart manifest vocabulary | three `larena.ui.smart_manifest.v1` manifests | PASS |
| Honest component ownership | `kind: composite`, `simai/docara`, null frontend runtime | PASS |
| Trusted immutable rendering | view models, fixed registry, presentation-only template test | PASS |
| Four-level navigation and active path | four-level fixture and rendered depth/current assertions | PASS |
| Fail closed beyond four levels | `DECLARATIVE_NAVIGATION_DEPTH_EXCEEDED` fixture | PASS |
| Structural shell parity | positive builder fixture and negative mismatch fixture | PASS |
| Larena projection parity | adapter assertions and diagnostic verdict | PASS |
| No markup in parser/compiler/builder | architecture boundary test | PASS |
| Published renderer unchanged | exact SHA-256 assertion | PASS |
| Full regression | 572 tests, 4,701 assertions | PASS |
| Deterministic static build | two equal digests; verifier 66/6,036/0 | PASS |
| Full-shell visual/publication migration | explicitly outside this goal | NOT CLAIMED |
