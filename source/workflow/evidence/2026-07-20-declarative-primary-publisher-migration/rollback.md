# Rollback

## Source-level publisher rollback

```bash
DOCARA_PORTABLE_PUBLISHER=legacy \
  /Applications/ServBay/package/php/8.2/8.2.29/bin/php ../../docara build production
```

The explicit rollback uses `LegacyPortablePagePublisher` and the byte-identical
`PortableHtmlRenderer`. Tests confirm identical URL keys and HTML output
inventory. It does not change authoring support or claim readiness.

## Local-site rollback

Accepted previous local build:

`/Users/rim/Sites/docara.test/.docara-backups/declarative-primary-20260720-230740/build_production`

Backup tree digest:

`6a2a97198b07a30326ad98237c381b2a8600b54a26141e9cba0be155508914e8`

Restore by staging that exact directory, comparing the digest and atomically
replacing `/Users/rim/Sites/docara.test/build_production`.
