# Baseline

- Date: 2026-07-21
- Branch: `codex/docara-consolidation`
- HEAD: `5823e5b974ceb4e26e8e7973903e1cfc87f37ce0`
- Worktree before workflow files: clean
- Accepted legacy renderer SHA-256:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`
- Previous accepted local demonstrator: `https://docara.test/examples/`
- Existing region calls are limited to `docara.header`,
  `docara.navigation` and `docara.outline`; footer has no callable section and
  authored region calls cannot yet provide block composition.
- The configuration merger already resolves `docara.json`, every ancestor
  `section.json`, and the page sidecar, with deterministic list replacement,
  object merge, explicit `$reset` and source provenance.
- Framework source is pinned by `framework.lock.json`; new presentation must
  use its registered utilities and Smart contracts.
- The repository-source doctor reports a project-profile mismatch because this
  worktree is the Docara package source rather than an installed project with
  root `config.php` and `source/docs`. This is not used as an acceptance gate.
