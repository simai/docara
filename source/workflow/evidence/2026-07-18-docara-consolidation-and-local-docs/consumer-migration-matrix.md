# Docara consumer Vite migration matrix

Date: 2026-07-18
Status: isolated branch builds pass; merge and retirement gates remain open

All migrations were prepared in dedicated `codex/docara-vite-migration`
worktrees. The user's original dirty repositories were not changed. Install
checks used the frozen Yarn invocation:

```text
YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc install --frozen-lockfile --production=false --non-interactive
```

Production builds used the candidate Docara core rather than a copied
consumer-owned `_core` tree.

| Consumer | Base -> migration commit | Current lock | Result | HTML/files | Status boundary |
| --- | --- | --- | --- | ---: | --- |
| `publications` | `7eae56b5bb52…` -> `fa7cb4b324a0…` | `v1.3.46` / `a061960f…` | install and Vite production build PASS | 23 / 41 | clean committed local branch; exact candidate release lock pending |
| `sf4-doc` | `4ec2ad6101c8…` -> `39f8a6c98320…` | `v1.3.22` / `fc31c67d…` | install and Vite production build PASS | 141 / 159 | clean committed local branch; exact candidate release lock pending |
| `simai-env/docara` | `5d1a84e4eb45…` -> `4f2a83588073…` | `v1.3.39` / `2f2a4f4e…` | install and Vite production build PASS | 77 / 94 | clean committed local branch; exact candidate release lock pending |
| `sitepack/docara` | `245187eac523…` -> `c47f3bfffafe…` | `v1.3.39` / `2f2a4f4e…` | install and Vite production build PASS | 52 / 70 | clean committed local branch; exact candidate release lock pending |
| `ui-doc` | `dfba4ddea56b…` -> `fbe85d581eaa…` | `v1.3.65` / `ba7724ae…` | install and Vite production build PASS | 918 / 936 | clean committed local branch; exact candidate release lock pending |
| `ui-doc-core` | `32e2ac293c39…` unchanged; experimental worktree dirty | n/a | not accepted | n/a | legacy retirement candidate; default-branch references remain |

The five maintained consumer workflows pass `actionlint` with shellcheck
disabled for the bounded workflow syntax check; `ui-doc` also passed the full
available actionlint run. Sass emitted deprecation warnings but no build
failure.

## Merge boundary

The current Composer locks intentionally preserve each repository's previously
released Docara version; none points at this unreleased candidate. These
branches are build-proven against a temporary local exact-candidate injection,
but they must not be merged as independently deployable migrations until
Docara is released at an exact tag/revision and each Composer lock is updated
and rebuilt against that release. The commits are local and were not pushed.

This evidence does not claim that active default branches are Mix-free.
