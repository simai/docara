# Upgrade notes: superseded rc.2 to planned rc.3

Use the normal fail-closed lifecycle: `update --verify`, `update --dry-run`,
review the hash-bound plan, then `update --apply`. Keep the returned rollback
identifier until acceptance is complete. Use `update --rollback=<id>` if the
post-update checks fail.

The verified rc.2 -> rc.3 transition changes only engine-owned revision,
dependency-lock and ownership metadata in an initialized project. Project-owned
Markdown, assets, `docara.json`, section/page configuration and locale
`lang.json` remain untouched. Public output must be rebuilt after the engine
update so deterministic page metadata is regenerated.

Do not deploy the superseded rc.2 ZIP. The planned rc.3 tag is not created and
requires a separate release decision.
