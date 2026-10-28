---
description: "Documentation freshness: an optional check of pages and examples against the project's public contract."
title: "Documentation freshness"
profile: reference
---

# Documentation freshness

Docara can show which reference pages and examples match the current public
API of the project. The mechanism is optional: without
`documentation_tracking` the site works as before.

```json
{
  "documentation_tracking": {
    "enabled": true,
    "source_locale": "ru",
    "mode": "report",
    "lock_file": "documentation.lock.json",
    "sources": [
      {
        "id": "simai-framework",
        "provider": "simai_framework",
        "framework_lock": "simai-framework.lock.json"
      }
    ]
  }
}
```

## Sources

The built-in `simai_framework` provider takes Core, utilities, ordinary and
Smart components from the exact hash-bound contract of the pinned Framework
runtime. An older runtime without such a pointer is read through a compatible
`rule.json` adapter and gets a warning about limited precision. For another
project use `provider: "contract_json"` and a file with the
`docara.documentation_source.v1` schema. The contract holds only stable keys
and public behaviour; internal formatting of the sources does not affect the
fingerprint.

## Statuses

- `current` — the source, the Markdown and the required examples are accepted;
- `new` and `changed` — an entity appeared or its public contract changed;
- `missing` and `missing_example` — a page or a required example is gone;
- `unverified` — an accepted Markdown page or example was edited;
- `orphan` — the source entity is gone;
- `excluded` — documentation is not needed on purpose, with a stated reason.

Technical key conflicts and ambiguous links stay diagnostics. An editorial
status does not hide them.

## Safe work for an agent

An agent first reads `documentation status` and the exact `inspect source`,
then edits the Markdown and the shared examples and runs `validate project`,
the build and `verify-static`. Acceptance is a separate `documentation accept
--dry-run`; any change of the inputs invalidates the prepared plan. Docara
does not call AI or the network and does not edit the documentation itself.

## See also

- [Page profiles](/en/authoring/page-profiles/) — another optional page check
- [Multilingual site](/en/authoring/multilingual-site/) — translation freshness
- [CLI commands](/ru/reference/cli/) (in Russian) — the `documentation` commands
