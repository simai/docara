# Batch 1 local publication and rollback evidence

Date: 2026-07-19
Accepted candidate: `d4ce688b38c6a29c2b57aaac2f8fe132f05b26b9`
Candidate tree: `a3eb29a7db0046e983f1a4c4c56aeb07bdae1024`
Target: `https://docara.test/`
Status: PASS

## Safety boundary

The action-gate preflight returned `success`, risk `low`, no warnings and no
blockers. Allowed writes were limited to:

- `/Users/rim/Sites/docara.test/build_production`;
- `/Users/rim/Sites/docara.test/.docara-backups`.

Public release, default branches, tags, `docara-mix`, ServBay configuration and
`.env` remained forbidden.

Stop conditions were: missing tester PASS, staging verifier failure, digest
mismatch, incomplete backup or failed HTTPS smoke. None occurred.

## Staging and backup

The accepted source build was rebuilt and verified before staging:

```text
41 HTML pages
3574 local references
0 broken
canonical tree digest: e14c35e5852e2ec44375d22621d6fe704b8270ec2eb5cf45cb6f262dc0cd5530
```

Verified staging path:

`/Users/rim/Sites/docara.test/.docara-staging-d4ce688-20260719-011225`

The previous served tree had digest:

`94872dc8627fac21cbc5c0fed8f6a9515b7fdb35d7e75580b49656ce4162eccf`

It was moved intact to the rollback path:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-menu-20260719-011225/build_production`

The verified staging directory was then renamed to the served
`build_production` directory on the same local filesystem.

## Post-publication verification

Served tree:

```text
41 HTML pages
3574 local references
0 broken
served digest: e14c35e5852e2ec44375d22621d6fe704b8270ec2eb5cf45cb6f262dc0cd5530
source = staging = served: true
```

HTTPS deep-route smoke:

```text
GET https://docara.test/authoring/layout-and-navigation/hierarchy/four-level/
HTTP 200
desktop page role: 1
desktop section role: 1
desktop ancestor roles: 2
desktop aria-current page: 1
```

Live in-app browser smoke after reload:

```text
title: Четыре уровня навигации — Docara
roles: ancestor, ancestor, section, page
level-4 rows in desktop sidebar: 1
aria-current page: 1
horizontal page overflow: 0
theme: dark
shell controller present: true
```

## Rollback

If a later local batch fails, replace the served `build_production` with the
preserved directory above and re-run the static verifier, digest and HTTPS
deep-route smoke. The backup was not modified after publication.

This local publication does not constitute a public release, production
readiness or completion of the active Docara product Goal.
