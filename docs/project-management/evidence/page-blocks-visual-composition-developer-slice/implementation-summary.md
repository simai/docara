# Implementation summary

Docara now adapts the typed `larena/layout` composition contract to Page
persistence. Administrators and Editors can save ordered draft blocks, protected
preview renders the draft, and publishing atomically snapshots the composition.
Anonymous rendering reads only the published snapshot and falls back to the
legacy Page body when no published composition exists.

The admin editor is schema-driven for Text, Image, Hero, Columns and CTA. It
uses external package assets, exposes the Smart-view mapping, supports ordering
and enable/disable settings, and validates all input fail closed before storage.
Public image fields accept only public image records. Audit events contain only
operation metadata, block count/types and version, never block values or file
references.

