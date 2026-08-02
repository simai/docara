# Docara 2.0.0-rc.3 — release notes draft

Status: unpublished draft. Tag `v2.0.0-rc.3` does not exist.

This candidate keeps the accepted content-first Docara 2 product and fixes one
release-blocking reproducibility defect: public page metadata no longer uses
filesystem extraction times when a dist package has no Git history. Two
independent consumers now produce byte-identical complete public trees.

The package continues to provide one Markdown owner per public page, shared UI
labels in `content/<locale>/lang.json`, in-memory typed Document IR, one Smart
gateway and one PageBuilder for full and selected-page builds.

Exact candidate source: `be0ba2db5254e468c7c014016ade02e8b4f3f16c`.
Exact ZIP SHA-256:
`630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0`.

This draft is not a release, deployment or compatibility promise beyond the
verified environments recorded in C6.
