# Local deployment

Date: 2026-07-20
Status: PASS

## Candidate

- implementation: `57a22fdab44ae2456efb97f7c899fae1d0a67578`;
- browser contrast correction:
  `4fa4bbf7c0b6a0d59205fc8c58639f5709447a5b`;
- documentation clarification:
  `42f2bd76ece8b831fcbca8b4e1db65494d151d10` (exact pre-closure HEAD).

No push, merge, tag, release, public deployment or ServBay configuration
change was performed.

## Safety

The federation action gate returned `warn`, not `block`, with successful access,
environment, repository-hygiene and source-policy gates. Its report is:

`source/output/action-gates/action-gate-report-20260720151206.json`

The warnings were closed operationally by:

- isolated staging;
- full pre-switch backup;
- equal source/staging and active/backup digests;
- static verification before and after switch;
- move-based rollback without deletion;
- HTTP and browser smoke tests.

## Final switch

- source:
  `docs/site/build_production`;
- active document root:
  `/Users/rim/Sites/docara.test/build_production`;
- staging evidence:
  `/Users/rim/Sites/docara.test/.docara-staging/20260720T1527-declarative-preview-42f2bd7`;
- complete backup:
  `/Users/rim/Sites/docara.test/.docara-backups/20260720T1527-declarative-preview-42f2bd7`;
- previous active tree digest:
  `1e14abd2ff002da4f4895330011a64ea6ea9fb6b91a3837470542a270cf7a880`;
- backup tree digest:
  `1e14abd2ff002da4f4895330011a64ea6ea9fb6b91a3837470542a270cf7a880`;
- final source/staging/served tree digest:
  `d27e5d8c4514d290e1c26d377870a1001acb9a2716fbf518c352bdc5c5c10e2c`.

## Rollback

If rollback is required:

1. move the current
   `/Users/rim/Sites/docara.test/build_production` to a new quarantine path;
2. move
   `/Users/rim/Sites/docara.test/.docara-backups/20260720T1527-declarative-preview-42f2bd7/build_production.original`
   back to `/Users/rim/Sites/docara.test/build_production`;
3. verify `/`, the preview URL expected for that revision and static output.

The backup copy and the original moved directory are both preserved.

## HTTP proof

All responses matched the exact local files byte-for-byte:

| URL | Status | SHA-256 |
| --- | --- | --- |
| `/` | 200 | `22400253158960c42480035a5dc43ee6d7c8dd4d364f55beb688d54f5ea7048d` |
| `/_docara/declarative-preview/` | 200 | `17c09b5da5e85ad48421bc63c6978ec7468975cae568186a852fd951d522e578` |
| `/_docara/declarative-preview/pages/development/declarative-preview/` | 200 | `52bf5d78a7742c775e89e2dd87bc87260b16e293b72016771e00acdec45a3b19` |
| `/_docara/declarative-preview/index.json` | 200 | `bfce05e9fd418cced6da6bf61e99ae89cd6e53c5e88d36963d046a3126b36745` |

Final active static verification: 113 HTML pages, 10,920 checked local
references, zero broken.
