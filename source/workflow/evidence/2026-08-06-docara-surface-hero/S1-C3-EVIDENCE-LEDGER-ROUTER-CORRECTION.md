# S1-C3 evidence ledger and router hygiene correction

Date: 2026-08-06
Status: `ready_for_independent_audit`
Frozen product/runtime/public-doc candidate:
`ac53ea4d372a47dc8278b595accca9e7b85c66a3`
Entry governance HEAD: `7e350d38feb5f692ce516ca4f72981f835f659e5`

## Audit finding and correction boundary

The S1-C2 runtime result remains frozen. The rejected executor ledger
`90bf637819d38456d745578011d2bf4f1e6e5cb6b349ebc230ee569da91f8b26`
was produced by sorting complete hash-first records. The canonical repository
implementation does something materially different: it keys records by
normalized relative path, sorts those paths with `SORT_STRING`, then hashes the
newline-terminated record stream. S1-C3 changes evidence/governance only.

## Canonical algorithm

Authority: `scripts/atomic-static-cutover.php::treeDigest()`.

```php
$lines[$relative] = hash_file('sha256', $file) . '  ' . $relative;
ksort($lines, SORT_STRING);
$digest = hash('sha256', implode("\n", $lines) . "\n");
```

Every filesystem entry must be a regular file; a symlink is a hard failure.
The final newline is part of the hashed stream.

## Fresh exact-candidate builds

From `docs/site` with ServBay PHP 8.4.20:

```bash
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build build_s1c3-ledger-a
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build build_s1c3-ledger-b
cp -R build_build_s1c3-ledger-a build_build_s1c3-ledger-single
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build build_s1c3-ledger-single --page=/ru/components/surface/
```

Canonical results:

| Root | Files | HTML | Tree SHA-256 |
| --- | ---: | ---: | --- |
| `docs/site/build_build_s1c3-ledger-a` | 393 | 266 | `650a678ccddcfac806e1c0c3b2d5327565a01ae06d0b77b5ed8a501c3118d10e` |
| `docs/site/build_build_s1c3-ledger-b` | 393 | 266 | `650a678ccddcfac806e1c0c3b2d5327565a01ae06d0b77b5ed8a501c3118d10e` |
| `docs/site/build_build_s1c3-ledger-single` | 393 | 266 | `650a678ccddcfac806e1c0c3b2d5327565a01ae06d0b77b5ed8a501c3118d10e` |

The selected Surface rebuild preserves the complete full-build tree.

Static verification:

```bash
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../scripts/verify-static-build.php build_build_s1c3-ledger-a
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../scripts/verify-static-build.php build_build_s1c3-ledger-b
```

Both results: schema `docara.static_build_verification.v1`, 266 HTML pages,
35,581 local references and `broken=[]`.

## Clean no-local clone proof

A fresh `git clone --no-local` was detached at the exact frozen candidate. It
used the already accepted local Composer dependency installation copied from
the clean executor workspace; no tracked source was copied or modified.

```bash
git clone --no-local /Users/rim/Documents/GitHub/docara-unified <temporary>/repo
git -C <temporary>/repo checkout --detach ac53ea4d372a47dc8278b595accca9e7b85c66a3
cp -R /Users/rim/Documents/GitHub/docara-unified/vendor <temporary>/repo/vendor
cd <temporary>/repo/docs/site
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build build_s1c3-clean-clone
```

The detached clone reports exact HEAD `ac53ea4...`, no tracked changes, 393
files, 266 HTML and the same canonical digest
`650a678ccddcfac806e1c0c3b2d5327565a01ae06d0b77b5ed8a501c3118d10e`.
Its static verifier also reports 266 / 35,581 / `broken=[]`.

## Router correction

- current batch: `docara.batch.s1.evidence_ledger_router_correction`;
- current roadmap marker: `docara.goal.s1_c3`, `audit_pending`, unauthorized;
- next action: `independent_goal_s1_reverse_outcome_audit`;
- S2 remains unstarted and unauthorized;
- generated context is regenerated only from the updated canonical graph.

The duplicate S1-C1/S1-C2 next-goal headers were removed. Historical runtime
correction evidence remains addressable and is not rewritten as a new product
result.

## Frozen-surface and rollback proof

The final governance range must satisfy:

```bash
git diff --quiet ac53ea4d372a47dc8278b595accca9e7b85c66a3..HEAD -- \
  src resources stubs docs/site tests scripts docara composer.json phpunit.xml
```

Rollback is one governance-only revert. Product commit `ac53ea4...` and its
runtime/public documentation stay unchanged. Disposable build and clone roots
are ignored evidence outputs, not repository source.

Goal S1 is ready for a new independent audit, not self-accepted. S2, release
review, merge, push, tag, release, deploy and external repository/site writes
were not performed or claimed.
