# Docara 2.0.0-rc.3 — changelog draft

- Fixed nondeterministic `updated_at` values in Composer dist consumers.
- Defined absent immutable Git/source audit metadata as `null`, never as an
  extraction-time fallback.
- Added complete-tree regression coverage with deliberately different source
  mtimes, including `_docara/page-metadata.json`.
- Repeated package, consumer, PHP 8.4/8.3, Linux, static, HTTP/browser,
  security and atomic rollback verification against the new exact artifact.

The former rc.2 identity remains immutable historical evidence and is not a
deployment candidate.
