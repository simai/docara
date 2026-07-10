# Implementation summary

- Added a protected preview route and Admin-shell preview view for any saved state.
- Added visible current status, Preview, View live, Publish and Unpublish actions.
- Added explicit unpublish domain operation with persistent audit event.
- Made repeat publish/unpublish calls idempotent without duplicate audit events.
- Preserved published visibility and `published_at` when editing live content;
  state changes still require the explicit lifecycle actions.
- Kept anonymous drafts unavailable and escaped preview/public output.
