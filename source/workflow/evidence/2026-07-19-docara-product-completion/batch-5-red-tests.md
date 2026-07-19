# Batch 5 RED tests

Date: 2026-07-19
Runtime: ServBay PHP 8.2.29

Command:

```text
/Applications/ServBay/package/php/8.2/current/bin/php vendor/bin/phpunit \
  --filter 'PortableMarkdownRendererTest|PortableSiteBuilderTest|PortableInitCommandTest'
```

Result: expected `RED`.

- 66 tests executed;
- 6 failures and 1 error;
- `cta` and `features` were still rendered as literal text;
- malformed and nested forms were not rejected;
- the starter landing still used the old inert Smart button and old outer-card
  layout;
- the landing sidecar did not yet disable search.

The failures establish the implementation boundary. No production source,
starter or live documentation content had been changed before this run.
