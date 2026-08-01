# M3.3 batch 19: Steps and Tree

Date: 2026-08-01

Parent: `5efab497e32e5c358670878ed72e16ceaa4f6369`

Candidate: commit containing this record

Verdict: PASS; the overall M3 Goal remains open.

## Ownership and runtime

- `/ru/components/steps/` is physically owned by
  `docs/site/content/ru/components/steps.md`;
- `/ru/components/tree/` is physically owned by
  `docs/site/content/ru/components/tree.md`;
- both portable starters have the same owners under `stubs/portable/content`;
- both catalog `docs_ref` values point to the physical owners;
- the two exact generated-route allowlist entries, Russian language-pack prose
  records and zero-reference localized examples were retired;
- the old localized example SHA-256 values were
  `96cdecda65886f04cba63898eef8c2e199c65ec19687387f6d2e31a9c0d839d0`
  (Steps) and
  `ce5d04fe90ee17f50795d523201a13f4a6bc58a64a57989a71187634595b3ccc`
  (Tree).

Steps and Tree reuse the existing generic `typed_directive` IR contract and
the same renderer registry, Smart gateway and PageBuilder. Tree adds keyboard
behavior to the shared declarative shell, not a Tree-specific build pipeline:
branch controls expose `aria-expanded`, retain focus, and respond to
ArrowLeft/ArrowRight. `interactive=false` emits no controls.

## Build and parity

```text
php ../../docara build m3-b19-final-full
PASS — 103 pages

php ../../docara build m3-b19-final-steps --page=/ru/components/steps/
PASS — 1 selected page; full/isolated route diff empty

php ../../docara build m3-b19-final-tree --page=/ru/components/tree/
PASS — 1 selected page; full/isolated route diff empty

php ../../docara verify-static build_m3-b19-final-full
PASS — 206 HTML pages, 18,944 local references, 0 broken
```

- content-addressed full tree SHA-256:
  `e23b37cc0de54749a5034222dfacb36584640cc27d5e76b9d1a74680e6388e93`;
- Steps HTML SHA-256:
  `b0576eb1fa8415130b750499eb611d291af6c833c41a380ab677a77f7d8c8cb9`;
- Tree HTML SHA-256:
  `3ee29d92c8e7e3b8e6b54a048f12f7e41b4a36708a1788e6665bef8dfac76200`;
- the projection receipt now contains only Tabs as a generated component
  detail page; the generated component boundary is Tabs plus the index;
- 30/32 Russian component routes are now physical, 16 generated public routes
  remain repository-wide, and the Russian pack maximum is 14.

## Browser evidence

Desktop-light Steps renders three blocks (two timeline presentations and one
list), with two complete, two current and two pending states, no horizontal
overflow and no console warnings/errors.

Mobile-dark Tree renders six interactive branch buttons across the visible
preview and authored example, zero leaf-file buttons and zero controls in the
static tree. ArrowLeft
sets `aria-expanded=false`, keeps keyboard focus, sets the child branch hidden
and makes it visually absent. ArrowRight restores the branch and focus remains
on the same button. There is no horizontal overflow or console issue.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| steps | desktop-light | `795ccc637267cfd9687d2811cba4aed9ea192ce83f9d101dd13221b49c617204` |
| tree | mobile-dark | `de2b2777d8805be29755dc85bef74d25127589ab98dfedc44bfc8f5bdb3c5aed` |

## Test and hygiene gate

The initial focused suite exposed one stale documentation test that still
classified the newly restored Steps owner as a retired manual page. After the
contract list was corrected, its focused regression passed with 166 assertions
and the complete suite passed:

- PHPUnit: 376 tests, 6,219 assertions, two inherited warnings;
- PHP lint: 222 source/test/stub PHP files;
- JSON validation: 403 repository JSON files;
- graph validation and `git diff --check`: PASS.

## Zero-reference and rollback

The successful physical owners suppress both projections; catalog receipt and
allowlist checks prove that only Tabs remains as a generated detail. No runtime,
test or public route refers to the deleted Russian examples after the typed
`docs_ref` changes. Reverting this checkpoint commit restores both generated
routes, language-pack records, examples and the pre-keyboard Tree behavior.

Batch 20 migrates Tabs. M3 readiness and completion are not claimed.
