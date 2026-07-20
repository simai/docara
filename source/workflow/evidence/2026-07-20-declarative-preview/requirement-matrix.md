# Requirement matrix

| Requirement | Evidence | Verdict |
| --- | --- | --- |
| Every supported authored page is browsable | receipt: 45 rendered, 0 skipped | PASS |
| One catalogue exposes the whole set | `/_docara/declarative-preview/` | PASS |
| One deterministic machine receipt exists | `index.json`, two equal builds | PASS |
| Internal supported links stay in preview | projector tests and browser navigation | PASS |
| Original destinations remain evidence | `data-docara-original-href` tests | PASS |
| Builder contains no preview HTML/CSS/JS | fixed templates under `resources/previews` | PASS |
| Templates are trusted and presentation-only | registry and architecture tests | PASS |
| Exact Framework assets are used | `FrameworkAssetPlan`, static verifier | PASS |
| Legacy renderer remains unchanged | exact SHA-256 `a28e914…` | PASS |
| Unknown/tampered preview fails closed | negative receipt test | PASS |
| Existing builds without preview remain verifiable | verifier compatibility tests | PASS |
| Detailed locations and order are documented | repo and generated documentation pages | PASS |
| Local site is staged, backed up and reversible | deployment evidence | PASS |
| Desktop/mobile result can be inspected | browser acceptance | PASS |
| Primary publisher switched | explicitly false | NOT CLAIMED |
| Full visual parity | explicitly false | NOT CLAIMED |
| Production readiness | explicitly false | NOT CLAIMED |
