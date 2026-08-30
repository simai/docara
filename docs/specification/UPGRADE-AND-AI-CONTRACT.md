# Upgrade and AI capability contract

## Purpose

Docara projects own one exact Composer runtime. An explicit `docara upgrade`
may update that runtime to a compatible stable patch/minor release only after
an isolated candidate proves that the existing project still validates,
builds, and passes static verification.

## Ownership

Project-owned immutable inputs during an upgrade are Markdown, examples,
assets, configuration, translations, Framework locks, Smart/design sources and
the pre-existing verified build. Package-owned mutable surfaces are the exact
dependency runtime and `.docara/engine`. Generated upgrade plans, candidates,
journals and rollback state live under `.docara/upgrades/`.

The physical Docara skill is never changed by a project command. Federation
installs an exact canonical skill revision. The package exposes its actual AI
surface through `capabilities --json`.

## Transaction

```text
explicit upgrade
-> validate project-local runtime and ownership
-> resolve exact stable same-major target
-> install independent Composer candidate
-> candidate doctor and project validation
-> candidate engine sync in isolated project
-> candidate production build and verify-static
-> re-hash all inputs
-> retain offline rollback state
-> promote dependencies, engine and verified build
-> journal applied state
```

Any failed promotion executes compensation. The previous dependency runtime,
lock, engine and verified build remain locally available. A changed input makes
the plan stale; a changed applied runtime makes automatic rollback stale.

## Version policy

- The implicit target is the highest stable version allowed by the current
  major and Composer constraint.
- `--to` accepts exactly `X.Y.Z`.
- Branches, moving references, prereleases and downgrades are forbidden.
- A different major returns `MAJOR_UPGRADE_REQUIRED`; migration is a separate
  project-owned decision.

## AI contract

`docara.capabilities.v1` is generated from the actual Symfony Application,
schema directory and package-owned `docara.ai_contract.v1` identity. It
contains the exact package version/revision, operations and parameters, schema
hashes, SDK types, receipts, tracking and lifecycle support, plus provenance
and one deterministic contract hash.

The canonical skill supports an `docara.ai_contract` range. It reads the exact
project-local contract first and uses a documented legacy path only when an old
package has no `capabilities` command. The skill does not copy product command
or schema registries.

## Release gate

A Docara release compares its AI contract to the previous release. A changed
public command, safety order, ownership rule or capability blocks package
publication until the canonical skill, its graph and Federation exact binding
pass Skill Sync Gate. Internal changes that do not alter the contract reuse the
same skill revision.

The first release with this contract uses the package-owned
`resources/release-baselines/docara-2.4.1-ai-contract-absent.json`. The baseline
does not invent a capabilities surface for 2.4.1: it records the exact tag and
revision where the command was absent and binds that one-time transition to AI
contract 1.1.0. A malformed baseline, another current contract version, or a
later attempt to reuse it fails closed. Every following release compares real
`docara.capabilities.v1` outputs from adjacent releases.

Build, serve and normal SDK operations never access the network. Only an
explicit upgrade may invoke Composer/network resolution.
