# M3.3 batch 20: Tabs

Date: 2026-08-02

Parent: `585eab0405c7a63df7add0c09cdd579ac25090a8`

Candidate: commit containing this record

Verdict: PASS; the overall M3 Goal remains open.

## Ownership and architecture

- `/ru/components/tabs/` is physically owned by
  `docs/site/content/ru/components/tabs.md`;
- the portable starter has the same owner under `stubs/portable/content`;
- `docara.tabs` points to that physical `docs_ref`;
- its exact generated-route allowlist entry and Russian language-pack prose
  record are removed;
- the retired localized example had SHA-256
  `7d3e2c7a93c7ed825973602b91872a1ac35601af019f8acd932ec056025a2080`.

Tabs reuses the generic `typed_directive` Document IR, renderer registry,
declarative shell, Smart gateway and PageBuilder. No Tabs-specific build or
projection path was introduced. The shared static verifier now accepts a valid,
hash-bound component-catalog receipt with zero generated detail records while
still verifying the generated index; other empty receipt contracts remain
fail-closed.

## Build and parity

```text
php ../../docara build m3-b20-full
PASS — 103 pages

php ../../docara build m3-b20-tabs --page=/ru/components/tabs/
PASS — 1 selected page; full/isolated route diff empty

php ../../docara verify-static build_m3-b20-full
PASS — 206 HTML pages, 18,949 local references, 0 broken
```

- content-addressed full tree SHA-256:
  `912cb6f68d4851f4d83eb64acd645e059166cc73a22e5eb0770fbe5c340fe9cb`;
- Tabs HTML SHA-256:
  `c6b9597e73d2a772719fa51fa83ca67c145b111b31f2dd12f9dc0d1307ddbb2f`;
- generated component detail receipt count: 0;
- 31/32 Russian component routes are physical; only `/ru/components/` remains
  a generated public route;
- repository-wide generated-route allowlist count: 15; Russian language-pack
  component record maximum: 13.

## Browser evidence

Desktop-light renders two Tabs blocks, six tabs, two selected tabs and exactly
one visible panel per block. On the authored block ArrowRight selects
`Проверка`, End selects `Публикация`, and Home returns to `Быстрый старт`;
`aria-selected`, panel visibility, roving `tabindex` and focus move together.

Mobile-dark at 390 px has zero page or tablist overflow, exactly one visible
panel per block and no console warnings/errors.

| Mode | Screenshot SHA-256 |
| --- | --- |
| desktop-light | `03e6cdd7b15c398caad3ad18fe42f7e6659178ec9fb1cf9a550d8f6c244300e5` |
| mobile-dark | `c316a61f976c9217aa9eee38002799e45674386ec893c904357b84ff3e0f5437` |

## Tests and rollback

- focused catalog/ownership suite: 38 tests, 3,100 assertions, two inherited
  warnings;
- focused static verifier: 21 tests, 243 assertions, PASS;
- complete PHPUnit: 376 tests, 6,173 assertions, two inherited warnings;
- the first complete run found one stale navigation fixture that omitted the
  new physical Tabs route; its focused regression and the final complete run
  pass;
- PHP lint: 225 source/test/stub/script files; JSON: 403 files; graph and diff
  hygiene: PASS.

Synthetic generated-detail verifier tests now use the remaining test-only Badge
projection rather than keeping a fake Tabs projection alive. A zero-reference
scan finds no active reference to the retired Russian Tabs example. Reverting
the checkpoint commit restores the projection, pack record, example and prior
static receipt boundary.

Batch 21 creates the physical `/ru/components/` index and converges its derived
view. M3 readiness and completion are not claimed.
