# Workflow: Symmetric locale routing for Docara

Date: 2026-07-21
Status: accepted locally
Workflow ID: `2026-07-21-docara-symmetric-locale-routing`
Process model: `full_qa`
Current state: `review_ready`
Target state: `review_ready`

## Current Goal

Move Docara to the symmetric locale model: isolated `content/<locale>` trees,
explicit `/<locale>/` public prefixes and a deterministic root route to the
configured default locale, while preserving the old unprefixed mode and every
current Docara documentation URL through exact redirects.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-21-docara-symmetric-locale-routing.md`;
- launch record:
  `source/workflow/2026-07-21-docara-symmetric-locale-routing.launch.yaml`.

## Result

Symmetric locale routing is implemented and accepted locally. Docara and the
portable starter use `content/ru` and `/ru/`; legacy URLs remain available,
the deterministic/static/full suite is green, and `docara.test` contains the
rollback-safe accepted artifact.

## Completion Guard

The completion guard is satisfied. Public push, merge, package release,
hosting-level redirects and production readiness remain explicitly excluded.
