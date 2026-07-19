# Batch 6 — effective component contract implementation

Date: 2026-07-19
Base revision: `8c241f5b088ed92934274cad28659060a892a514`
Status: `IMPLEMENTED_CANDIDATE_PREPARATION`

## Outcome

Portable Docara now derives one deterministic
`docara.effective_component_catalog.v1` from the authoring surfaces that the
build actually accepts:

- five bounded native Markdown capabilities;
- four Docara typed directives backed by executable renderer IDs;
- two Smart-components admitted by the exact Simai Framework lock;
- six non-executable requirement records with explicit lifecycle, owner,
  fallback and admission condition.

The generated catalogue contains 17 sorted records, 11 of them `supported`.
It carries the exact Framework pair, provider revision, canonical content hash
and explicit nonclaims. It is a consumer projection, not a second Framework
registry and not a production or public-release readiness statement.

## Single-source contracts

- `PortableMarkdownProfile` owns the enabled CommonMark extensions and native
  capability inventory.
- `TypedComponentDefinitionRepository` owns typed directive names and maps
  them to a closed executable `TypedRendererId` set.
- `FrameworkLock` owns Smart admission; manifest discovery, diagnostics and
  parser admission are derived from its exact records.
- `FrameworkManifestContract` verifies property schemas, presets, controls,
  mirrors, constraints, atlas states and readiness.
- `FrameworkConsumerPolicy` only narrows exact manifests. It owns bounded
  descriptions, managed properties, blocked values, omitted assets and state
  exclusions tied to schema-valid blocked property/value pairs.
- The `ui` prefix is reserved for the strict Framework identifier gate and
  cannot be shadowed by a typed Docara directive.

## Runtime and supply-chain gates

- every bundled manifest, runtime lock and projected Smart asset must be a
  contained regular single-link file;
- every manifest and asset byte must match its exact lock hash;
- the complete asset projection must equal the admitted dependency closure;
- missing and extra projection files fail before an existing destination is
  cleaned;
- malformed or unknown `ui.*` identifiers fail closed instead of becoming
  inert Markdown;
- unknown manifest schema fields, invalid property rules, mirrors, presets,
  constraints, readiness and consumer-policy values fail closed;
- the static verifier reconstructs the trusted catalogue and embedded lock,
  verifies the exact materialized asset set and rejects unsafe provenance.

## Author and operator surface

Every production build writes:

- `_docara/component-catalog.json`;
- the exact Framework assets named by the resolved page plans;
- unchanged deterministic page-plan and search receipts.

Portable projects can verify a build through the stable command:

```text
php vendor/bin/docara verify-static [build-directory]
```

The command defaults to `build_production`, does not execute project PHP
configuration and remains backed by the packaged verifier. Source-repository
maintainers use `php ./docara verify-static`.

## Goal reconciliation

The product capability matrix now has a reachable completion gate:

- `docara.card` satisfies the neutral panel job without a duplicate alias;
- `docara.columns` remains the one required executable addition for Batch 7;
- tabs, public icon authoring and badge admission are optional deferred owner
  contracts with honest fallbacks;
- requirement records never become executable in place and must be replaced
  by the appropriate authoritative native, typed or lock-admitted source.

`ui.button` is documented consistently as a render-only visual action control.
Portable Docara does not bind data, navigate or execute an effect; native links
and `docara.cta` own navigation.

## Verification

- Pint: `PASS`;
- complete sequential PHPUnit: `505 tests`, `2942 assertions`, `PASS`;
- focused component/runtime/static verifier suites: `PASS`;
- `git diff --check`: `PASS`;
- two mutable production builds: byte-identical digest
  `a4d1433fb933c94ffd522c974800d1f62f94d4b299544fbd1a7238eb7274831f`;
- packaged `verify-static`: 47 HTML pages, 4943 local references, zero broken;
- effective catalogue: 17 records, 11 supported, canonical content SHA-256
  `02d716492fffb02e801f2cabe0b2a2c763ffd6e8685c6b1489a6b5db03194d98`;
- independent mutable-tree static/security review: `PASS`; its isolated pair
  of builds was byte-identical and all bounded negative probes failed with the
  expected gates.

## Remaining acceptance

This evidence does not accept Batch 6. One immutable candidate must still pass:

- independent exact-archive PHPUnit/build/static verification;
- complete-diff Human-Centered Simplicity/source/security review;
- bounded native-Chrome regression of unchanged reader surfaces and the new
  catalogue artifact;
- safe local staging/publication with backup, rollback and matching digests.

Public release, default-branch migration, Framework owner writes and repository
retirement remain excluded.
