# Requirement matrix

| Requirement | Planned proof | Status |
| --- | --- | --- |
| Reusable definitions | Registered Layout, Section, Block and View JSON; schema/runtime tests | PASS |
| Stable Section/Block call IDs | `site-header`, `docs-navigation`, `page-outline`; derived block IDs; duplicate-ID negatives | PASS |
| Named slots | Section definitions, plan and renderer use `content`; invalid/missing/duplicate slot negatives | PASS |
| Safe View Tree | Schema plus runtime tag, attribute and node allowlists; executable-surface negatives | PASS |
| Exact Framework utilities | Pinned projection `sf-v5.3.2-7e836d8a-dd786bba`; unknown utility negative | PASS |
| Registered Blade leaf | Navigation uses fixed registered Blade ID/path; arbitrary author templates rejected | PASS |
| Complete ResolvedRenderPlan | `docara.resolved_render_plan.v2`, expanded plans, diagnostics and deterministic hashes | PASS |
| Larena parity | Adapter emits the same composition semantics with `semantic_parity: pass` | PASS |
| Legacy renderer unchanged | SHA-256 `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0` | PASS |
| Demonstration | Identical builds, static verifier, local atomic deployment and desktop/mobile browser checks | PASS |
