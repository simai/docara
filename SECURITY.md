# Security policy

## Supported versions

| Version | Supported |
|---|---|
| 3.x | Yes |
| Older | No |

Security fixes are released as patch versions of the latest 3.x release.

## Reporting a vulnerability

Please do not report security problems in public issues, pull requests or
discussions.

Report them privately through GitHub:
[Report a vulnerability](https://github.com/simai/docara/security/advisories/new)
(the **Security** tab of this repository, then **Report a vulnerability**).

Include what you can of:

- the Docara version (`php vendor/bin/docara --version`) and PHP version;
- what an attacker can do, and under which conditions;
- steps or a minimal project that reproduces it;
- any fix or mitigation you have in mind.

We acknowledge a report within five working days, keep you informed while we
work on it, and agree a disclosure date with you. We credit reporters in the
advisory unless you ask us not to.

## Scope

Docara builds static sites from content you trust. In scope, for example:

- a built site that runs script or loads content the author did not write or
  allow (for example, raw HTML getting past the Markdown policy, an unsafe
  embed, or an example frame escaping its sandbox);
- the build, `serve`, `upgrade`, `update` or the MCP adapter in
  `tools/mcp-docara/` reading or writing files outside the project, or running
  code the project did not ask for;
- `verify-static` accepting a build it should reject.

Out of scope: problems that need the attacker to already control the project's
files or build machine, and vulnerabilities in third-party dependencies that
Docara does not make worse (report those upstream).
