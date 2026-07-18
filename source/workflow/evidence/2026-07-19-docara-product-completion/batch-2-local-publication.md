# Batch 2 local publication and rollback evidence

Date: 2026-07-19
Accepted candidate: `df82a5fa82a96263c3f8af4e900a9c5a665f9412`
Candidate tree: `224e9acf512ec8d53f50a6275a594423e34ddf3c`
Target: `https://docara.test/`
Status: `PASS`

## Acceptance gate

The same immutable candidate passed all bounded Batch 2 gates:

- exact-archive tester: `PASS`;
- exact HCS/source review: `PASS`;
- exact UX/designer/browser reacceptance: `PASS`.

This accepts only deterministic local search. It does not accept the wider
Docara Goal, later product batches, public release or production readiness.

## Safety boundary

The action-gate report is
`source/output/action-gates/action-gate-report-20260718234232.json`. It returned
`warn` with no blockers. The warnings require explicit backup, rollback,
scope, smoke and secret boundaries, which are fixed below before any write.

Inventory before publication:

- accepted exact build:
  `/private/tmp/docara-search-candidate-df82a5f/docs/site/build_production`;
- current served build: `/Users/rim/Sites/docara.test/build_production`;
- backup root: `/Users/rim/Sites/docara.test/.docara-backups`;
- publication staging:
  `/Users/rim/Sites/docara.test/.docara-staging-df82a5f-20260719-024516`.

Allowed writes are limited to the staging directory, the new timestamped
backup directory and `/Users/rim/Sites/docara.test/build_production`.

Forbidden surfaces are `.env`, ServBay configuration, public release, tags,
default branches, remote deployment, `docara-mix` and every unrelated site
file. No credential or Access Center material is required; the local HTTPS
site is the already configured access alias. The environment path is the
existing `/Users/rim/Sites/docara.test`, but environment files remain unread
and unchanged.

The user has explicitly authorized local test-site publication in this Goal.
The change window is this bounded local batch. Repository status and source
policy checks are clean; no secret-bearing file is in the candidate diff.

## Verified source and rollback plan

Exact source verification before staging:

```text
42 HTML pages
3831 local references
0 broken
source digest: 81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f
```

Current served digest before publication:

`e14c35e5852e2ec44375d22621d6fe704b8270ec2eb5cf45cb6f262dc0cd5530`

The current served directory will be moved intact to:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-search-20260719-024516/build_production`

Rollback is the reverse same-filesystem rename: move a failed served tree out
of the way, restore this preserved `build_production`, then re-run static
verification, digest, HTTPS and browser smoke. The backup must not be edited.

## Stop conditions and smoke plan

Stop before replacement if staging verification fails, the source/staging
digests differ, backup creation is incomplete or the served directory changes
unexpectedly. Roll back immediately if served verification, digest equality,
HTTPS or browser search smoke fails.

Required post-publication smoke:

1. source, staging and served digests are identical;
2. static verifier reports 42 pages, 3831 references and zero broken links;
3. HTTPS home, search documentation and search index return `200`;
4. live browser search for `наследование` returns useful native links;
5. mobile viewport has no positive horizontal overflow and controls retain
   accessible target sizes.

Cleanup removes only an unused staging directory after successful publication.
The rollback copy is retained.

## Publication result

The accepted source was copied to the declared staging directory. Static
verification returned 42 HTML pages, 3831 checked local references and zero
broken references. The staging digest was:

`81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f`

The previous served tree was moved intact to:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-search-20260719-024516/build_production`

Its preserved digest is:

`e14c35e5852e2ec44375d22621d6fe704b8270ec2eb5cf45cb6f262dc0cd5530`

The staging directory was renamed to the served `build_production` directory
on the same filesystem. Post-publication static verification returned the same
42 pages, 3831 references and zero broken references. The served digest is:

`81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f`

Therefore `source = staging = served` is true.

HTTPS smoke:

```text
GET https://docara.test/                         200 text/html
GET https://docara.test/authoring/search/        200 text/html
GET https://docara.test/_docara/search-index.json 200 application/json
```

Live in-app browser smoke for query `наследование`:

```text
desktop 1296x657: 5 results; first /authoring/inheritance/; overflow -14px
desktop trigger: 221.67x50; close: 48x48
mobile 390x844: 5 results; first /authoring/inheritance/; overflow -12px
mobile trigger: 44x44; close: 44.08x44.08
mobile dialog: 344x640.63, contained inside the viewport
status: Найдено: 5
```

No rollback was needed. The timestamped previous build remains unchanged and
available for the next local batch. `.env`, ServBay configuration and all
forbidden surfaces were untouched.
