# Goal 3 correction — contained writes, diagnostic parity and visual acceptance

Date: 2026-08-03
Status: `ready_for_independent_audit`
Project mode: `productization`
Audit marker: `4a1444b1-0f6c-454a-8ed4-83fb6a06969d`
Second correction audit marker: `019fc76b-c201-7770-8ff0-117ca22df873`
Branch: `codex/docara-unified-architecture`
Input handoff HEAD: `17be51d90c7e68c7389c917b2c4c0d0697dc35c3`
Rejected product candidate: `8cd695ffdef2adf3fa4475b4d0d3e9ba948da560`
Rejected ZIP: `e63d2edb1040c22504bc8b486abfbccdde9095c53f16d825afb58dec1b27ffb8`
Accepted Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`
Evidence: `source/workflow/evidence/2026-08-03-docara-goal3-correction/INDEX.md`

## Goal

Close the complete Goal 3 outcome after independent audit: every SDK/CLI/MCP/QA
write remains project-contained before its first filesystem mutation; actual
human/JSON/MCP failures share one complete structured diagnostic contract; and
Smart, region and layout QA have production-context-bound 24-scenario browser
evidence. Produce a new exact candidate only after the whole regression,
package and consumer matrix is green.

## Done When

- reusable containment policy guards every Goal 3 generated/write root before
  delete, mkdir, copy, write or rename;
- preview/QA symlink, nested-parent, hardlink, collision, traversal, stale-plan
  and rollback tests prove zero external mutation and no partial tree;
- real CLI human/JSON and MCP positive/negative paths preserve operation,
  subject, exit/isError, source/pointer/location, owner, provenance and
  suggestion without absolute/private leakage;
- Smart, region and layout each pass 8 browser scenarios through the accepted
  PreviewKernel/PageBuilder path;
- full, focused, build/static, cross-host, package and two-consumer matrices
  are green and deterministic;
- graph, generated context, specification, workflow and handoff bind one new
  exact implementation candidate and stop at `ready_for_independent_audit`.

## Ownership

- coordination and recovery: `teamlead`;
- runtime implementation: `dev`;
- exploit, diagnostic and browser verdict: `tester`;
- public/developer wording: `docs`;
- canonical/derived state: `graph`;
- execution: `single_agent`; no subagents or external writes;
- installed stale Docara skill is disabled and forbidden.

The federation route classified this correction as UX because of the browser
matrix. That route is incomplete for the critical filesystem/SDK outcome, so
repository Goal 3 plus raw owner sources above remain authoritative.

## Batch plan

| Batch | Outcome | Verification | Status |
| --- | --- | --- | --- |
| C3.0 | freeze correction and preserve RED exploit/diagnostic baselines | exact disposable reproductions and external tree ledgers | complete |
| C3.1 | one generated-root containment guard used by preview, QA, scaffold and MCP write surfaces | focused security matrix and zero external diff | complete |
| C3.2 | one exception-to-operation-result/diagnostic mapping for CLI human/JSON and MCP | real-command golden parity and schema negatives | complete |
| C3.3 | production-bound Smart/region/layout QA | 24 browser scenarios, reports and screenshots | complete |
| C3.4 | integrated deterministic build/package/consumer regression | full/focused/static/cross-host/package/two consumers | complete |
| C3.5 | exact candidate governance and handoff | graph/context/docs checks, clean commits/worktree | complete |
| C3.6 | file-backed locations, target-bound visual references and candidate-range hygiene | actual-command goldens, mutation negative, 24 scenarios, range diff | complete |
| C3.7 | exact integrated retest and corrected governance | full/build/package/two consumers, graph/context/handoff | complete |

## Invariants and non-goals

- keep one Markdown -> typed IR -> renderer registry -> Smart gateway ->
  LayoutComposer -> PageBuilder path;
- no second registry, renderer, gateway, composer, builder or preview engine;
- no mandatory Node/MCP/browser dependency for normal consumer build;
- no arbitrary template/PHP/HTML/CSS path and no security-policy weakening;
- no external Framework/site write, Goal 4, merge, push, tag, release or deploy;
- preserve historical evidence; rejected hashes are immutable negative history.

## Stop conditions

Stop only if containment requires changing the accepted project-root/preview
contract, a user change overlaps the correction, or an external owner/release
decision becomes mandatory. Local failing tests, stale hashes and bounded
implementation defects must be corrected inside this workflow.

## Current state

- current batch: independent Goal 3 reverse-outcome audit;
- C3.1 implementation boundary: `496df2cab420a5f93560ea376ae44417368d1ba4`;
- C3.2 implementation boundary: `ea6cead5cbb8a1d693aa1debcf6223633b5c323f`;
- C3.3 implementation/evidence runner boundary: `526beeee8882189e01e636e73c65c3ebd87b6b8b`;
- exact product candidate: `6f547810583a16114ed15a8199f698e1dadb70a9`;
- remaining implementation batches: none;
- next safe action: independently audit the exact correction candidate and
  evidence; Goal 4 remains unstarted and unauthorized;
- rejected audit candidates `8cd695ff…` and `a027c9ab…` remain history only.
- do not complete until the new exact candidate passes independent-ready gates.

## Kaizen

The prior Goal 3 matrix validated generated artifacts but did not independently
attack the generated-root ancestors before first mutation and did not require
real-command diagnostic goldens. These become permanent regression boundaries,
not optional evidence prose.
