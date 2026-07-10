# Implementation summary

- Inserted a Docara-owned observer immediately before the existing Access
  middleware in the protected Page route chain.
- The observer records only a 403 response to the named Page `PUT` update
  route; successful, anonymous, read-only and unrelated requests are ignored.
- The event type is `docara_page_update_denied` with actor, slug, denied status
  and fixed reason. Request title/body and all other input are excluded.
- The existing Access engine remains the sole authorization decision owner.
- The denial response and Page persistence paths are unchanged.
