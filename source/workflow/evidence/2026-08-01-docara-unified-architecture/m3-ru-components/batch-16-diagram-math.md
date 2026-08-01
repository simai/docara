# M3.3 batch 16: Diagram and Math

Date: 2026-08-01

Verdict: PASS

Parent SHA: `0c3305c9d0ebc75574842c52ab36cdc1a3a72ec3`

Candidate SHA: commit containing this evidence

## Ownership and runtime

- `/ru/components/diagram/` -> `docs/site/content/ru/components/diagram.md`;
- `/ru/components/math/` -> `docs/site/content/ru/components/math.md`.

Both reuse generic typed-directive IR and the existing PageBuilder/renderer
path. Diagram preserves Mermaid source, figcaption and accessible image role;
Math preserves inline/block TeX and labels. No Mermaid/KaTeX dependency,
runtime engine, family-specific compiler, IR or registry was added.

## Legacy reduction and rollback

- allowlist: `24 -> 22`; Russian pack: `22 -> 20`;
- generated component details: `9 -> 7`; physical ownership: `22/32 -> 24/32`;
- retired example hashes:
  - Diagram: `870be5941e2fee44a56811c729c980ed4f7afedf3259bb6209700b035ff1fe06`;
  - Math: `c8fc011bfc1c197116d014f0fbed17b6fadc5e8a9d6bb6d6363b4aac61978fae`.

Capability tests now read physical owners. Repository search found zero active
references to the localized fixtures. Commit revert is the rollback path.

## Verification

- full: 103 pages; isolated: one Diagram/Math page each;
- exact tree SHA: `8f67a9f7fffa68a95a8ccf8648aa936eadd28c914266600ed7549d80c15fad1b`;
- static: 206 HTML, 18,939 references, 0 broken;
- Diagram HTML: `c6b66b4d3ff1da42216927371ba1bdc5d20886543369fbee0eedfd2b36431159`;
- Math HTML: `9bedd02d7092eea07a991d73d2e85b84a0a7ddfd28381c6e67ee28206bc5290c`;
- focused suite: 100 tests, 4,685 assertions, PASS with two inherited warnings;
- full PHPUnit: PASS, 374 tests and 6,475 assertions with two inherited
  warnings; lint, JSON, graph and diff hygiene: PASS.

## Browser evidence

Desktop-light Diagram renders one accessible Mermaid figure with zero overflow.
Mobile-dark Math renders one inline and two block formulas with zero overflow.
Both have zero console warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| diagram | desktop-light | `72f8e1e2232aa18ef5d3cd8f37753a5ef078d88e0b13e7bf5f1980da73dc573b` |
| math | mobile-dark | `a0a34b7ba43bdbec1f468c84d445b95e58e05fd2d46c699510160604fb52559b` |

## Boundary

Batch 16 PASS; M3 remains open. 24/32 physical, 8 generated. Batch 17 is
Code-from-file and isolated HTML.
