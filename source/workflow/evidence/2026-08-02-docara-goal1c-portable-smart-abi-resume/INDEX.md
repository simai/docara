# Goal 1-C resumed acceptance evidence

Date: 2026-08-02
Status: `ready_for_independent_audit`
Branch: `codex/docara-unified-architecture`
Input Docara revision: `7ea63d797cd0de3aa424ea0a9279abaf22775908`
Runtime implementation revision: `94d2afd9cb71d6b02d8f4a63d4f807e127b1b190`
Verified governance revision: `46c9ac6ad99ec0b4bb72501ddab954925becf19c`
Rollback: revert Goal 1-C resume commits in reverse order; do not rewrite history

## Outcome

The external portable Smart ABI correction removes the former cross-host stop
condition. Docara now pins the accepted adapter exactly, exposes the neutral
Framework-owned ABI identity, and keeps the previous SF5 label only as an
explicit storage compatibility alias. One unchanged tracked artifact renders
byte-identically under Docara and exact SF5. Goal 1 runtime, build, security,
public parity and browser outcomes were then retested as one integrated gate.

Goal 1 is not independently accepted by this evidence. Goal 2 and Goal 3 were
not started.

## Evidence map

- `G1C-R1-REPINS-AND-CROSS-HOST.md` — immutable inputs, blob hashes and the
  permanent positive cross-host regression;
- `cross-host-report-v3.json` — machine-readable two-host report;
- `G1C-R2-RUNTIME-AND-SECURITY.md` — provider ownership, single gateway,
  generic runtime and fail-closed negatives;
- `G1C-R3-BUILD-PARITY-DETERMINISM.md` — public build equality and static
  verification;
- `G1C-R4-BROWSER.md` and `browser/` — fresh desktop/mobile interaction smoke;
- `G1C-R5-INTEGRATED-ACCEPTANCE.md` — final repository checks and exact
  candidate binding. The later evidence-only commit does not change runtime,
  public documentation, graph state or tests.

## Immutable external inputs

| Input | Exact revision/hash |
| --- | --- |
| Bitrix/SF5 host adapter | `b3cdff87563ff78e7eddf044048a4b298fc69036` |
| Adapter tree | `5b1e61012d7f3aa7202ec71b368dec9730d94bc8` |
| Framework method | `f15dcbf068a8006f27596b55227f1817658244d8` |
| Architecture provider | `e0cc049374585099eccc9aca17d1e436150f6b23` |
| Canonical graph proposal | `991ca2e33d67678420a3f57a0817c4f602abe4c9` |
| Compatibility state | `04da80ffa3f118cc1e03a63a1fcc1b7d2d0b931f` |
| Accepted handoff patch | `532165e5634061d85d178dd5670a02474f0a475662fc048582f4372bb9b77267` |

Historical pin `d6f90bba6a9a2f30ac41075d62cf51f1014b7e78`
and Goal 1 candidate `34496d49ce366f1108d2aed37c0adda35f6e5f58`
remain superseded evidence and were not rewritten.
