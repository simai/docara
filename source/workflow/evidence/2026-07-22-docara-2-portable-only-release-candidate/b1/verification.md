# B1 — portable-only CLI boundary

## Outcome

- `docara init` creates the JSON/Markdown starter directly; `--portable` and
  legacy preset selection no longer exist.
- `docara build` invokes `PortableSiteBuilder` directly.
- `docara serve` serves only `build_<environment>` through the safe packaged
  router and never falls through to stale output after a failed build.
- `docara verify-static` runs without the legacy container.
- The executable is assembled by `Console\\ApplicationFactory`; it does not
  bootstrap `Container`, `Docara`, Jigsaw providers, translation, Azure or
  project-defined PHP commands.

## Verification

```text
phpunit tests/PortableInitCommandTest.php tests/ServeCommandTest.php
OK (14 tests, 144 assertions)

php docara list --raw
build
completion
help
init
list
serve
verify-static
```

The retained `completion`, `help` and `list` entries are Symfony Console
infrastructure, not Docara product commands.
