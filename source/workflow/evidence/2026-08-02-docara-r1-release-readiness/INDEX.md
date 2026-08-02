# R1 release-readiness evidence

Goal status: `PASS_WITH_EXPLICIT_GAPS`

Input revision: `afc5e0477e0ba9f65765e1cb1016bd996cb8fa75`

| Checkpoint | Status | Evidence |
| --- | --- | --- |
| R1.1 independent M5 acceptance | PASS | [R1.1-M5-INDEPENDENT-ACCEPTANCE.md](R1.1-M5-INDEPENDENT-ACCEPTANCE.md) |
| R1.2 deterministic release package | PASS | [R1.2-DETERMINISTIC-PACKAGE.md](R1.2-DETERMINISTIC-PACKAGE.md) |
| R1.3 release verification and consumers | PASS_WITH_COMPATIBILITY_GAPS | [R1.3-CONSUMERS-BUILDS-COMPATIBILITY.md](R1.3-CONSUMERS-BUILDS-COMPATIBILITY.md) |
| R1.4 versioned update and rollback | PASS | [R1.4-VERSIONED-UPDATE-ROLLBACK.md](R1.4-VERSIONED-UPDATE-ROLLBACK.md) |
| R1.5 adoption, security and browser | PASS_WITH_EVIDENCE_NOTE | [R1.5-SECURITY-BROWSER-ADOPTION.md](R1.5-SECURITY-BROWSER-ADOPTION.md) |
| R1.6 release-readiness packet | PASS | [R1.6-RELEASE-READINESS-PACKET.md](R1.6-RELEASE-READINESS-PACKET.md) |

Exact local release candidate:

- source revision: `8c0d14566837b6e6f4552d14c656ea14b202cd18`;
- archive SHA-256: `83afd355436284a0040390c88e1d125f3e5648932a23ff324ba9afa9af5eb561`;
- manifest SHA-256: `77e781122cdc2bd5b6091fea74803ea26f5a0e4c8c8d0e9c3282cf0112c7a51a`;
- consumer lock SHA-256: `176748244149dcf5ddc41dfe6365c17e6547239cce18a5f5e5de6809bdbcacbb`.

PHP 8.2 and 8.4 are locally proved. PHP 8.3 and a complete Linux matrix are
CI-defined but were not executed in this local contour. Exact screenshots were
blocked by an external-font wait; exact browser assertions plus UI-equivalent
M5 screenshots are recorded without calling them exact captures.

Release, merge, push, tag and deployment remain separate user-approved actions.
