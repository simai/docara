# Kaizen

## What improved

- Content parsing, composition, presentation and publication now have explicit
  boundaries instead of one implicit rendering step.
- The same resolved object can be rendered by Docara and projected to Larena.
- Smart templates are no longer selected by author-controlled paths.
- A semantic comparison protects the migration while legacy HTML remains the
  published output.
- Build diagnostics expose plan hashes, asset provenance and parity instead of
  hiding the new path behind a visual demo.

## Problems discovered

1. The Homebrew PHP installation is broken by a missing ICU 73 library.
   ServBay PHP is the working project runtime.
2. ServBay CLI uses `Etc/GMT+5`, while legacy Jigsaw date snapshots assume UTC.
   Full deterministic acceptance must set PHP timezone explicitly.
3. `git archive` excludes tests by design, so it cannot be the only exact-tree
   acceptance mechanism for this repository.

## Proposed next improvements

- Pin a repository test command or wrapper that selects ServBay PHP and UTC
  explicitly without changing a developer's global PHP configuration.
- Add a machine-readable declarative registry generator so supported Smart
  coverage is derived rather than repeated in the builder.
- Extend the next vertical slice to one structural Smart-component, then move
  header/sidebar/outline composition out of the legacy shell.
- Keep removal or default-path switching as a separate Goal with independent
  visual, accessibility, responsive and migration acceptance.
