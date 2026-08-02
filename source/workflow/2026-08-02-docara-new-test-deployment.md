# docara-new.test exact rc.3 deployment

Status: `deployed_and_verified`

## Scope and identity

- repository: `/Users/rim/Documents/GitHub/docara-unified`;
- branch: `codex/docara-unified-architecture`;
- governance input: `a94b6967b0ef0748f1d9c700e0a018ea8b06fb7e`;
- product source: `be0ba2db5254e468c7c014016ade02e8b4f3f16c`;
- exact ZIP SHA-256:
  `630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0`;
- expected public tree SHA-256:
  `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`;
- live target: `/Users/rim/Sites/docara-new.test` only;
- `docara.test` is outside this deployment scope.

## Read-only inventory

Caddy serves `https://docara-new.test` directly from the target directory.
HTTP redirects to HTTPS; HTTPS currently returns 404 because the target is an
empty directory. Current empty-tree digest is
`01ba4719c80b6fe911b091a7c05124b64eeece964e09c058ef8f9805daca546b`.

The federation action gate returned warnings until backup, rollback and smoke
were made explicit. This record satisfies that preparation; the user's request
is the explicit authorization for this local test-site write.

## Safe change

1. Build from the accepted exact ZIP in a disposable source copy.
2. Require 103 routes, 305 files, static broken=0 and exact expected tree
   digest before the target is touched.
3. Copy the verified tree to sibling candidate
   `.docara-new.test-candidate-rc3-be0ba2d` on the same filesystem.
4. Use `scripts/atomic-static-cutover.php` to rename the empty current target to
   `.docara-new.test-backup-before-rc3-be0ba2d` and the candidate to
   `docara-new.test`. Caddy configuration and service state do not change.

## Rollback and smoke

Rollback uses the same helper and exact digests to restore the empty original
directory. The backup remains in place after successful smoke because it is
small and is the immediate recovery point.

Required verification:

- exact candidate digest before and after cutover;
- static verifier broken links/assets = 0;
- HTTPS canonical routes 103/103;
- representative home, component, authoring and asset responses;
- browser page/console errors and horizontal overflow = 0;
- repository worktree clean after evidence commit.

Stop immediately and roll back on digest mismatch, any required route 4xx/5xx,
broken asset, browser page/console error, overflow, or failed rollback
precondition.

## Result

Deployment completed without Caddy reload. The atomic helper passed preflight
and cutover with the exact empty-current and rc.3 candidate digests. The active
target now has 305 files and exact tree SHA-256
`425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`.

Post-cutover verification:

- HTTPS root: 200 over HTTP/2; HTTP redirects 301 to HTTPS;
- page metadata inventory: 103 routes; HTTP smoke: 103/103 passed;
- static verifier: 206 HTML, 21,437 local references, broken=0;
- representative home, Alert and metadata asset: 200;
- browser home and Alert: page errors=0, console errors=0, overflow=0;
- search/settings dialogs open and close with Escape;
- Alert Markdown tab and copy action work;
- mobile viewport 390x844 has zero horizontal overflow.

The home page emitted two non-error Highlight.js warnings for the unsupported
`shell` grammar and correctly fell back to unhighlighted code. The Alert page
console was clean. This does not affect content, navigation or smoke status.

Rollback remains immediately available at
`/Users/rim/Sites/.docara-new.test-backup-before-rc3-be0ba2d`. It contains the
exact original empty tree (zero files). The candidate is now active, so rollback
must use `scripts/atomic-static-cutover.php rollback` with current digest
`01ba4719…` and candidate digest `425da363…`; no manual deletion is required.

Machine-readable evidence:
`source/workflow/evidence/2026-08-02-docara-new-test-deployment/result.json`.
