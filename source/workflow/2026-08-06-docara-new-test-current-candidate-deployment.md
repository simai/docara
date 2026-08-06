# docara-new.test accepted Goal C candidate deployment

Date: 2026-08-06
Status: `deployed_and_verified`
Mode: reversible local validation-site deployment

## Goal

Build the independently accepted Docara product/runtime candidate and publish
its verified static output to `https://docara-new.test` without changing Caddy,
the source candidate, `docara.test` or any external repository.

## Exact scope and authorization

- repository: `/Users/rim/Documents/GitHub/docara-unified`;
- branch: `codex/docara-unified-architecture`;
- product/runtime candidate: `eb35f5c6f18e5eb9be69e91887b09486f5703136`;
- deployment input HEAD: `3d3006d1579f9b879d83f934b9bfca4fa88287cc`;
- active target: `/Users/rim/Sites/docara-new.test`;
- URL: `https://docara-new.test`;
- authorization: direct user request on 2026-08-06 to rebuild the site;
- access: local filesystem and existing ServBay/Caddy configuration; no secret
  value or external credential is required.

## Read-only inventory

- Caddy root is exactly `/Users/rim/Sites/docara-new.test`;
- Caddy reload is unnecessary because the root path does not change;
- current active tree SHA-256:
  `5dc4112d3a0424ca74fca1b73a27392532cfdff84011369a5c3b38984fc32e9d`;
- current active output: 307 files / 208 HTML / 104 metadata routes;
- current HTTPS root: 200 over HTTP/2;
- current HTTP root: 301 redirect to HTTPS;
- target parent has 380 GiB available;
- existing historical backups are preserved and not removed.

## Change plan

1. Create a disposable detached worktree at exact product commit `eb35f5c6…`.
2. Use the unchanged Composer dependency tree to build the documentation site
   twice in clean output roots.
3. Require byte-identical outputs and `verify-static` with `broken=[]`.
4. Copy one verified output to the same-filesystem sibling
   `.docara-new.test-candidate-goalc-eb35f5c6-20260806`.
5. Run `scripts/atomic-static-cutover.php preflight` with exact old/new tree
   digests.
6. Atomically rename the current target to
   `.docara-new.test-backup-before-goalc-eb35f5c6-20260806` and the candidate to
   `docara-new.test`.
7. Verify the exact active digest, static output, all metadata routes, selected
   assets and browser behavior.

## Rollback

Rollback uses `scripts/atomic-static-cutover.php rollback` with the recorded
old/new digests. It atomically preserves the failed/new candidate as the
candidate sibling and restores the exact previous tree to `docara-new.test`.
No manual deletion and no Caddy reload are required.

## Stop conditions

- product worktree is not exact or is dirty;
- either full build differs from the other;
- static verification reports a broken reference;
- candidate, active or backup digest differs from the recorded digest;
- candidate or target contains a symlink;
- any required route or asset returns 4xx/5xx;
- browser reports page/console errors or horizontal overflow;
- rollback preconditions do not match exactly.

## Verification and evidence

Evidence directory:
`source/workflow/evidence/2026-08-06-docara-new-test-current-candidate-deployment/`.

Required evidence: source/output identities, build ledgers, static counts,
cutover preflight/result, 132-route HTTPS smoke, representative local assets,
browser console/overflow/interactions, rollback path, untouched surfaces and
final repository status.

## Result

- deployed at: `2026-08-06T12:03:03+03:00`;
- exact active product/runtime candidate:
  `eb35f5c6f18e5eb9be69e91887b09486f5703136`;
- active complete-tree SHA-256:
  `44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4`;
- active output: 391 files / 264 HTML / 132 metadata routes;
- two exact-candidate builds were byte-identical;
- static verification: 264 HTML / 35,044 local references / `broken=[]`;
- HTTPS smoke: 132/132 routes returned 200; HTTP root redirects to HTTPS;
- representative versioned CSS/JS, search index and page metadata returned
  200;
- desktop and mobile browser smoke found no page/console errors, no console
  warnings on the representative settings page and no horizontal overflow;
- search and settings dialogs close on Escape and return focus to their opener;
- settings expose no blur as the default and the accepted configurable UI
  radius options;
- no Caddy reload or configuration change was made.

The exact previous tree remains recoverable at
`/Users/rim/Sites/.docara-new.test-backup-before-goalc-eb35f5c6-20260806`
with SHA-256
`5dc4112d3a0424ca74fca1b73a27392532cfdff84011369a5c3b38984fc32e9d`.
The post-cutover inverse preflight confirmed both active and backup digests,
same-filesystem placement and rollback preconditions.

## Cleanup policy

- delete only disposable build worktree/output after evidence is complete;
- keep the immediate previous-tree backup for rollback;
- do not remove the two pre-existing historical backups in this batch;
- do not change Caddy configuration or reload the service.
