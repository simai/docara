# Docara starter

This repository is a generated mirror of `simai/docara:stubs/portable`.
Do not edit its site files directly; change the canonical starter in Docara and
regenerate the mirror from an exact reviewed commit.

The sync workflow creates a pull request rather than writing directly to the
default branch. Both manual and repository-dispatch runs require a full
40-character Docara commit SHA.

## Start

```bash
composer require simai/docara:{{DOCARA_PACKAGE_VERSION}}
php vendor/bin/docara build local
```

The generated manifest binds this mirror to the exact Docara version whose Git
tag resolves to the recorded source revision. An untagged commit cannot be
exported, and synchronization also verifies that Composer resolves the exact
version back to that revision.

The portable build uses PHP only. It does not require Node.js, npm, Yarn, Vite,
Laravel Mix, or webpack. Composer creates `composer.json` and `composer.lock` in
the new project; commit both files when the project is placed under version
control.

The generated `simai-framework.lock.json` pins the exact Simai Framework
runtime used by the starter. Site content lives in `content/`, while
`docara.json`, `_section.json`, and page sidecars control presentation and
navigation.

`docara-template-mirror.json` records every mirrored payload file, its exact
canonical source path, and its SHA-256.
