# Workflow: generic fenced-code documentation clarification

Date: 2026-08-25
Status: completed

## Goal

Describe Docara's ordinary fenced-code rendering as a generic Markdown feature
instead of an HTML-specific component and avoid mixing it with Example.

## Decision

- A triple-backtick fence is documented as a generic code block.
- A language identifier controls the visible label and syntax highlighting.
- An omitted identifier produces the neutral `Code` label.
- Example is not used on this page because it represents result/source
  demonstration behavior, which is a different concept.

## Scope

Documentation source and generated local preview only. Product/runtime source,
Git history and public release state remain unchanged.
