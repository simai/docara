# Portable Docara GitHub install delivery

Date: 2026-07-20
Status: verified for feature-branch delivery
Branch: `codex/docara-consolidation`

## Problem

`composer require simai/docara` resolves the published legacy `1.x` package.
Its `init` command does not contain `--portable`, while the portable quick
start on the feature branch used the unconstrained stable Composer command.
The resulting error is:

```text
The "--portable" option does not exist.
```

Using ordinary `docara init` is not a workaround because it creates a legacy
Blade/Jigsaw project.

## Scope

- correct README, quick start, CLI reference and troubleshooting;
- pin the already accepted portable implementation candidate
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- use public Git VCS with `no-api: true` so the Docara repository itself does
  not require GitHub API authentication;
- verify install, init, build and static output from a clean directory;
- commit and push only the existing feature branch.

## Explicit non-actions

- no tag or GitHub Release;
- no default-branch merge or mutation;
- no Packagist publication;
- no stable, production or ecosystem readiness claim;
- no legacy repository retirement.

The release action gate remains closed because public release backup,
rollback, change-window and owner publication evidence were not supplied.

## Verification contract

1. Documentation regression test prevents pairing the legacy stable package
   with `init --portable`.
2. Clean Composer installation resolves the exact accepted commit.
3. `init --portable` creates the portable scaffold.
4. Production build succeeds.
5. `verify-static` reports no broken references.
6. Full project checks and repository hygiene pass before commit.

## Verification result

- public GitHub VCS install with `no-api: true`: `PASS`;
- Composer locked `simai/docara` as
  `dev-codex/docara-consolidation` at exact source reference
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- `init --portable`: `PASS`, 20 scaffold files created;
- clean candidate production build: `PASS`;
- clean candidate `verify-static`: 29 HTML, 585 references, zero broken;
- documentation regression plus portable init:
  14 tests / 374 assertions, `PASS`;
- full PHPUnit under deterministic UTC PHP configuration:
  549 tests / 4,488 assertions, `PASS`;
- documentation production build:
  66 HTML, 6,036 references, zero broken;
- Pint: `PASS`;
- Composer validation: `PASS`;
- `git diff --check`: `PASS`.

ServBay configures PHP with `date.timezone=Etc/GMT+5`. Two legacy date
snapshots consequently fail one day earlier if the suite is run without an
explicit deterministic timezone. They pass unchanged when the PHP child
processes receive the UTC ini override. No snapshots or runtime code were
changed to hide this environment condition.

## Rollback

This batch is a reversible feature-branch commit. Rollback is `git revert` of
the delivery commit. The accepted implementation candidate remains unchanged,
and no public package or default branch is modified.
